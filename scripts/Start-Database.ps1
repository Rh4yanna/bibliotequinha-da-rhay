param(
    [string]$PostgresBin = 'C:\Program Files\PostgreSQL\18\bin',
    [int]$Port = 55432
)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$dataPath = Join-Path $projectRoot 'storage\app\postgres\data'
$logPath = Join-Path $projectRoot 'storage\logs\postgres.log'
$passwordPath = Join-Path $projectRoot 'storage\app\postgres\password.tmp'
$envPath = Join-Path $projectRoot '.env'

if (!(Test-Path -LiteralPath (Join-Path $PostgresBin 'pg_ctl.exe'))) { throw 'PostgreSQL não encontrado. Informe -PostgresBin com o caminho da pasta bin.' }
if (!(Test-Path -LiteralPath $envPath)) { throw 'Copie .env.example para .env e gere a chave antes de iniciar.' }

if (!(Test-Path -LiteralPath (Join-Path $dataPath 'PG_VERSION'))) {
    $envText = [IO.File]::ReadAllText($envPath)
    if ($envText -match '(?m)^DB_PASSWORD=.+$') { throw 'O .env já possui senha. Para preservar sua configuração, use o PostgreSQL existente ou prepare um .env separado para esta instância local.' }
    $secretBytes = New-Object byte[] 32
    $random = [Security.Cryptography.RandomNumberGenerator]::Create()
    $random.GetBytes($secretBytes)
    $random.Dispose()
    $dbSecret = [Convert]::ToBase64String($secretBytes)
    [IO.Directory]::CreateDirectory([IO.Path]::GetDirectoryName($dataPath)) | Out-Null
    [IO.File]::WriteAllText($passwordPath, $dbSecret, (New-Object Text.UTF8Encoding($false)))
    try {
        & (Join-Path $PostgresBin 'initdb.exe') -D $dataPath -U campus --pwfile=$passwordPath --auth=scram-sha-256 --encoding=UTF8 --locale=C
        if ($LASTEXITCODE -ne 0) { throw 'Não foi possível inicializar o banco local.' }
    } finally {
        if (Test-Path -LiteralPath $passwordPath) { Remove-Item -LiteralPath $passwordPath }
    }
    $settings = @{ DB_CONNECTION = 'pgsql'; DB_HOST = '127.0.0.1'; DB_PORT = "$Port"; DB_DATABASE = 'campus'; DB_USERNAME = 'campus'; DB_PASSWORD = $dbSecret }
    foreach ($setting in $settings.GetEnumerator()) { $envText = [regex]::Replace($envText, "(?m)^$($setting.Key)=.*$", "$($setting.Key)=$($setting.Value)") }
    [IO.File]::WriteAllText($envPath, $envText, (New-Object Text.UTF8Encoding($false)))
}

& (Join-Path $PostgresBin 'pg_ctl.exe') -D $dataPath status | Out-Null
if ($LASTEXITCODE -ne 0) {
    $startArgs = '-D "{0}" -l "{1}" -o "-p {2} -h 127.0.0.1" -w start' -f $dataPath, $logPath, $Port
    $process = Start-Process -FilePath (Join-Path $PostgresBin 'pg_ctl.exe') -ArgumentList $startArgs -WindowStyle Hidden -PassThru
    $process.WaitForExit()
    if ($process.ExitCode -ne 0) { throw "Falha ao iniciar PostgreSQL. Consulte $logPath" }
}

$envText = [IO.File]::ReadAllText($envPath)
$match = [regex]::Match($envText, '(?m)^DB_PASSWORD=(.*)$')
$previousPassword = $env:PGPASSWORD
try {
    $env:PGPASSWORD = $match.Groups[1].Value.Trim()
    $exists = & (Join-Path $PostgresBin 'psql.exe') -w -h 127.0.0.1 -p $Port -U campus -d postgres -tAc "SELECT 1 FROM pg_database WHERE datname = 'campus'"
    if ($LASTEXITCODE -ne 0) { throw 'Não foi possível conectar ao PostgreSQL local.' }
    if ($exists -ne '1') {
        & (Join-Path $PostgresBin 'createdb.exe') -w -h 127.0.0.1 -p $Port -U campus campus
        if ($LASTEXITCODE -ne 0) { throw 'Não foi possível criar o banco campus.' }
    }
} finally { $env:PGPASSWORD = $previousPassword }
Write-Host "PostgreSQL do projeto disponível em 127.0.0.1:$Port. Banco: campus."
