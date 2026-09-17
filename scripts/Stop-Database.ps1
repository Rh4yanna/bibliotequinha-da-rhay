param([string]$PostgresBin = 'C:\Program Files\PostgreSQL\18\bin')
$projectRoot = Split-Path $PSScriptRoot -Parent
$dataPath = Join-Path $projectRoot 'storage\app\postgres\data'
if (!(Test-Path -LiteralPath (Join-Path $dataPath 'PG_VERSION'))) { throw 'O banco local deste projeto ainda não foi inicializado.' }
& (Join-Path $PostgresBin 'pg_ctl.exe') -D $dataPath -m fast -w stop
if ($LASTEXITCODE -ne 0) { throw 'Não foi possível parar a instância local.' }
