param([string]$PostgresBin = 'C:\Program Files\PostgreSQL\18\bin', [switch]$Backup, [switch]$Entrega)
$ErrorActionPreference = 'Stop'
if ($Entrega) { $Backup = $true }
$projectRoot = Split-Path $PSScriptRoot -Parent
$dataPath = Join-Path $projectRoot 'storage\app\postgres-test\data'
$testEnvPath = Join-Path $projectRoot '.env.testing'
$port = 55433
$database = 'bibliotequinha_test'
$dbUser = 'bibliotequinha_test'
$oldScan = $env:PHP_INI_SCAN_DIR
$oldPassword = $env:PGPASSWORD
$oldEncoding = $env:PGCLIENTENCODING
$started = $false
function Check-Exit([string]$action) { if ($LASTEXITCODE -ne 0) { throw "Falha: $action" } }
function New-RandomSecret {
    $bytes = New-Object byte[] 32
    $random = [Security.Cryptography.RandomNumberGenerator]::Create()
    try { $random.GetBytes($bytes) } finally { $random.Dispose() }
    return [Convert]::ToBase64String($bytes)
}
try {
    $env:PHP_INI_SCAN_DIR = Join-Path $PSScriptRoot 'php-conf'
    $env:PGCLIENTENCODING = 'UTF8'
    if (!(Test-Path -LiteralPath (Join-Path $PostgresBin 'pg_ctl.exe'))) { throw 'PostgreSQL não encontrado.' }
    if (!(Test-Path -LiteralPath (Join-Path $dataPath 'PG_VERSION'))) {
        if (Test-Path -LiteralPath $testEnvPath) { throw '.env.testing existente: preserve-o e configure manualmente o banco isolado.' }
        [IO.Directory]::CreateDirectory((Split-Path $dataPath -Parent)) | Out-Null
        $secret = New-RandomSecret
        $passwordPath = Join-Path (Split-Path $dataPath -Parent) 'password.tmp'
        [IO.File]::WriteAllText($passwordPath, $secret)
        try {
            & (Join-Path $PostgresBin 'initdb.exe') -D $dataPath -U $dbUser --pwfile=$passwordPath --auth=scram-sha-256 --encoding=UTF8 --locale=C
            Check-Exit 'initdb isolado'
        } finally { Remove-Item -LiteralPath $passwordPath -ErrorAction SilentlyContinue }
        $key = 'base64:' + (New-RandomSecret)
        $settings = "APP_ENV=testing`nAPP_KEY=$key`nAPP_DEBUG=false`nDB_CONNECTION=pgsql`nDB_HOST=127.0.0.1`nDB_PORT=$port`nDB_DATABASE=$database`nDB_USERNAME=$dbUser`nDB_PASSWORD=$secret`nDB_SSLMODE=disable`nCACHE_STORE=array`nSESSION_DRIVER=array`nQUEUE_CONNECTION=sync`nMAIL_MAILER=array`n"
        [IO.File]::WriteAllText($testEnvPath, $settings, (New-Object Text.UTF8Encoding($false)))
    }
    $testSettings = @{}
    Get-Content -LiteralPath $testEnvPath | ForEach-Object { if ($_ -match '^([^#=]+)=(.*)$') { $testSettings[$matches[1]] = $matches[2].Trim() } }
    if ($testSettings.DB_HOST -ne '127.0.0.1' -or $testSettings.DB_PORT -ne "$port" -or $testSettings.DB_DATABASE -ne $database -or $testSettings.DB_USERNAME -ne $dbUser -or $testSettings.DB_URL) { throw 'Alvo de teste inesperado; execução cancelada.' }
    $env:PGPASSWORD = $testSettings.DB_PASSWORD
    & (Join-Path $PostgresBin 'pg_ctl.exe') -D $dataPath status | Out-Null
    if ($LASTEXITCODE -ne 0) {
        $logPath = Join-Path (Split-Path $dataPath -Parent) 'postgres.log'
        $startArgs = '-D "{0}" -l "{1}" -o "-p {2} -h 127.0.0.1" -w start' -f $dataPath, $logPath, $port
        $process = Start-Process -FilePath (Join-Path $PostgresBin 'pg_ctl.exe') -ArgumentList $startArgs -WindowStyle Hidden -PassThru
        $process.WaitForExit()
        if ($process.ExitCode -ne 0) { throw 'Falha ao iniciar PostgreSQL de testes.' }
        $started = $true
    }
    $exists = & (Join-Path $PostgresBin 'psql.exe') -w -h 127.0.0.1 -p $port -U $dbUser -d postgres -tAc "SELECT 1 FROM pg_database WHERE datname = '$database'"
    Check-Exit 'verificação do banco'
    if ($exists -ne '1') { & (Join-Path $PostgresBin 'createdb.exe') -w -h 127.0.0.1 -p $port -U $dbUser $database; Check-Exit 'criação do banco de testes' }
    Push-Location $projectRoot
    try {
        php vendor/phpunit/phpunit/phpunit --configuration=phpunit.pgsql.xml
        Check-Exit 'testes PostgreSQL'
        if ($Backup) {
            php artisan migrate --env=testing --force
            Check-Exit 'migrations incrementais'
            php artisan migrate --env=testing --force
            Check-Exit 'segunda execução incremental'
            php artisan db:seed --env=testing --force
            Check-Exit 'seed'
            if ($Entrega) {
                & (Join-Path $PSScriptRoot 'Validate-SqlDelivery.ps1') -PostgresBin $PostgresBin
            }
            $readerId = & (Join-Path $PostgresBin 'psql.exe') -w -h 127.0.0.1 -p $port -U $dbUser -d $database -tAc "SELECT id FROM users WHERE email = 'consulta@biblioteca.test'"
            Check-Exit 'leitor de demonstração'
            $bookId = & (Join-Path $PostgresBin 'psql.exe') -w -h 127.0.0.1 -p $port -U $dbUser -d $database -tAc "SELECT id FROM books WHERE code = 'LIV-0002'"
            Check-Exit 'exemplar de demonstração'
            if ($readerId -notmatch '^\d+$' -or $bookId -notmatch '^\d+$') { throw 'IDs de demonstração ausentes.' }
            & (Join-Path $PostgresBin 'psql.exe') -w -h 127.0.0.1 -p $port -U $dbUser -d $database -v ON_ERROR_STOP=1 -c "CALL sp_registrar_emprestimo($readerId, $bookId, CURRENT_DATE + 14);"
            Check-Exit 'empréstimo para evidência'
            $loanId = & (Join-Path $PostgresBin 'psql.exe') -w -h 127.0.0.1 -p $port -U $dbUser -d $database -tAc "SELECT id FROM loans WHERE user_id = $readerId AND book_id = $bookId AND returned_at IS NULL"
            Check-Exit 'empréstimo criado'
            if ($loanId -notmatch '^\d+$') { throw 'Empréstimo de demonstração não encontrado.' }
            & (Join-Path $PostgresBin 'psql.exe') -w -h 127.0.0.1 -p $port -U $dbUser -d $database -v ON_ERROR_STOP=1 -f database/sql/02_demonstracao.sql
            Check-Exit 'demonstração SQL'
            $backupDir = Join-Path $projectRoot 'storage\app\backups'
            [IO.Directory]::CreateDirectory($backupDir) | Out-Null
            $dump = Join-Path $backupDir ('bibliotequinha-test-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '.dump')
            & (Join-Path $PostgresBin 'pg_dump.exe') -w -h 127.0.0.1 -p $port -U $dbUser -d $database -Fc -f $dump
            Check-Exit 'pg_dump'
            $restoreDb = 'bibliotequinha_restore_test_' + (Get-Date -Format 'yyyyMMddHHmmss')
            & (Join-Path $PostgresBin 'createdb.exe') -w -h 127.0.0.1 -p $port -U $dbUser $restoreDb
            Check-Exit 'criação do destino de restauração'
            & (Join-Path $PostgresBin 'pg_restore.exe') -w -h 127.0.0.1 -p $port -U $dbUser -d $restoreDb --no-owner --no-acl --exit-on-error $dump
            Check-Exit 'pg_restore'
            $query = "SELECT (SELECT count(*) FROM users) AS users, (SELECT count(*) FROM books) AS books, (SELECT count(*) FROM categories) AS categories, (SELECT count(*) FROM loans) AS loans, (SELECT count(*) FROM loan_audits) AS audits;"
            $sourceCounts = & (Join-Path $PostgresBin 'psql.exe') -w -h 127.0.0.1 -p $port -U $dbUser -d $database -tAc $query
            Check-Exit 'contagens da origem'
            $restoredCounts = & (Join-Path $PostgresBin 'psql.exe') -w -h 127.0.0.1 -p $port -U $dbUser -d $restoreDb -tAc $query
            Check-Exit 'contagens restauradas'
            if ($sourceCounts -ne $restoredCounts) { throw 'Contagens divergentes.' }
            Write-Output "Backup/restauração: origem=$sourceCounts destino=$restoredCounts"
            $fingerprintQuery = "SELECT md5(string_agg(data, '|' ORDER BY entity)) FROM (SELECT 'books' AS entity, coalesce(jsonb_agg(to_jsonb(b) ORDER BY id)::text, '[]') AS data FROM books b UNION ALL SELECT 'users', coalesce(jsonb_agg(to_jsonb(u) ORDER BY id)::text, '[]') FROM users u UNION ALL SELECT 'categories', coalesce(jsonb_agg(to_jsonb(c) ORDER BY id)::text, '[]') FROM categories c UNION ALL SELECT 'loans', coalesce(jsonb_agg(to_jsonb(l) ORDER BY id)::text, '[]') FROM loans l UNION ALL SELECT 'audits', coalesce(jsonb_agg(to_jsonb(a) ORDER BY id)::text, '[]') FROM loan_audits a) snapshots;"
            $sourceFingerprint = & (Join-Path $PostgresBin 'psql.exe') -w -h 127.0.0.1 -p $port -U $dbUser -d $database -tAc $fingerprintQuery
            Check-Exit 'assinatura dos dados de origem'
            $restoreFingerprint = & (Join-Path $PostgresBin 'psql.exe') -w -h 127.0.0.1 -p $port -U $dbUser -d $restoreDb -tAc $fingerprintQuery
            Check-Exit 'assinatura dos dados restaurados'
            if ($sourceFingerprint -ne $restoreFingerprint) { throw 'Dados restaurados divergentes.' }
            Write-Output "Dados conferidos: assinatura=$sourceFingerprint"
            & (Join-Path $PostgresBin 'psql.exe') -w -h 127.0.0.1 -p $port -U $dbUser -d $restoreDb -v ON_ERROR_STOP=1 -c "CALL sp_registrar_devolucao($loanId); SELECT fn_quantidade_emprestimos_ativos($readerId); SELECT operation FROM loan_audits ORDER BY id;"
            Check-Exit 'objetos SQL restaurados'
        }
    } finally { Pop-Location }
} finally {
    if ($started) { & (Join-Path $PostgresBin 'pg_ctl.exe') -D $dataPath -m fast -w stop }
    $env:PHP_INI_SCAN_DIR = $oldScan
    $env:PGPASSWORD = $oldPassword
    $env:PGCLIENTENCODING = $oldEncoding
}
