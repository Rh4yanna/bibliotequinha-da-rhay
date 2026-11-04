param([string]$PostgresBin = 'C:\Program Files\PostgreSQL\18\bin')
# Apoio local da apresentação. Não altera .env, não se conecta ao Neon e não apaga bancos.
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$privatePath = Join-Path $projectRoot '.env.gravacao'
if (Test-Path -LiteralPath $privatePath) { throw '.env.gravacao já existe. Preserve o ambiente preparado; este script não o sobrescreve.' }
if (Test-Path -LiteralPath (Join-Path $projectRoot 'bootstrap/cache/config.php')) { throw 'Há configuração em cache. Execute php artisan config:clear antes e repita.' }
$sourcePath = Join-Path $projectRoot '.env.testing'
if (!(Test-Path -LiteralPath $sourcePath)) { throw 'Execute scripts/Test-Postgres.ps1 para preparar a infraestrutura isolada e repita.' }
$settings = @{}
$source = [IO.File]::ReadAllText($sourcePath)
$source -split "`n" | ForEach-Object { if ($_ -match '^([^#=]+)=(.*)$') { $settings[$matches[1]] = $matches[2].Trim() } }
if ($settings.DB_HOST -ne '127.0.0.1' -or $settings.DB_PORT -ne '55433' -or $settings.DB_DATABASE -ne 'bibliotequinha_test' -or $settings.DB_USERNAME -ne 'bibliotequinha_test' -or $settings.DB_URL) { throw 'Configuração local de testes inesperada. Nada foi alterado.' }
$dataPath = Join-Path $projectRoot 'storage/app/postgres-test/data'
if (!(Test-Path -LiteralPath (Join-Path $dataPath 'PG_VERSION'))) { throw 'Cluster isolado ausente.' }
$previousPassword = $env:PGPASSWORD
$previousEncoding = $env:PGCLIENTENCODING
function Check-RecordingExit { if ($LASTEXITCODE -ne 0) { throw 'Preparação interrompida; preserve os arquivos/bancos criados para inspeção.' } }
try {
    & (Join-Path $PostgresBin 'pg_ctl.exe') -D $dataPath status | Out-Null
    if ($LASTEXITCODE -ne 0) {
        $logPath = Join-Path $projectRoot 'storage/app/postgres-test/gravacao.log'
        $arguments = '-D "{0}" -l "{1}" -o "-p 55433 -h 127.0.0.1" -w start' -f $dataPath,$logPath
        $process = Start-Process -FilePath (Join-Path $PostgresBin 'pg_ctl.exe') -ArgumentList $arguments -WindowStyle Hidden -PassThru
        $process.WaitForExit()
        if ($process.ExitCode -ne 0) { throw 'Não foi possível iniciar o PostgreSQL local isolado.' }
    }
    $env:PGPASSWORD = $settings.DB_PASSWORD
    $env:PGCLIENTENCODING = 'UTF8'
    $database = 'bibliotequinha_gravacao_' + (Get-Date -Format 'yyyyMMddHHmmss')
    Write-Output "Destino confirmado: 127.0.0.1:55433 / $database (NOVO, LOCAL)."
    & (Join-Path $PostgresBin 'createdb.exe') -w -h 127.0.0.1 -p 55433 -U bibliotequinha_test $database
    Check-RecordingExit
    foreach ($file in @('00_estrutura.sql','03_dados_ficticios.sql')) {
        & (Join-Path $PostgresBin 'psql.exe') -w -h 127.0.0.1 -p 55433 -U bibliotequinha_test -d $database -v ON_ERROR_STOP=1 -1 -f (Join-Path $projectRoot ('database/sql/' + $file)) | Out-Null
        Check-RecordingExit
    }
    $content = $source
    $overrides = @{ APP_ENV='gravacao'; DB_DATABASE=$database; APP_DEBUG='false'; SESSION_DRIVER='file'; CACHE_STORE='array'; QUEUE_CONNECTION='sync'; DB_SSLMODE='disable' }
    foreach ($entry in $overrides.GetEnumerator()) {
        $pattern = '(?m)^' + [regex]::Escape($entry.Key) + '=.*$'
        if ([regex]::IsMatch($content,$pattern)) { $content = [regex]::Replace($content,$pattern,($entry.Key + '=' + $entry.Value)) }
        else { $content += "`n" + $entry.Key + '=' + $entry.Value + "`n" }
    }
    # URL explícita local evita qualquer fallback para a URL Neon do .env principal.
    $localUrl = 'postgresql://' + [Uri]::EscapeDataString($settings.DB_USERNAME) + ':' + [Uri]::EscapeDataString($settings.DB_PASSWORD) + '@127.0.0.1:55433/' + $database + '?sslmode=disable'
    $content += "`nDB_URL=" + $localUrl + "`n"
    [IO.File]::WriteAllText($privatePath,$content,(New-Object Text.UTF8Encoding($false)))
    Write-Output 'Ambiente privado .env.gravacao criado. Não abra esse arquivo na gravação.'
    Write-Output 'O cluster local permanece iniciado. Nenhum banco existente foi apagado ou restaurado por cima.'
} finally { $env:PGPASSWORD=$previousPassword; $env:PGCLIENTENCODING=$previousEncoding }
