param([string]$PostgresBin = 'C:\Program Files\PostgreSQL\18\bin')
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$port = 55433
$dbUser = 'bibliotequinha_test'
$sourceDb = 'bibliotequinha_test'
function Check-SqlExit([string]$action) { if ($LASTEXITCODE -ne 0) { throw "Falha: $action" } }
$settings = @{}
Get-Content -LiteralPath (Join-Path $projectRoot '.env.testing') | ForEach-Object { if ($_ -match '^([^#=]+)=(.*)$') { $settings[$matches[1]] = $matches[2].Trim() } }
if ($settings.DB_HOST -ne '127.0.0.1' -or $settings.DB_PORT -ne "$port" -or $settings.DB_DATABASE -ne $sourceDb -or $settings.DB_USERNAME -ne $dbUser -or $settings.DB_URL -or $env:PGPASSWORD -ne $settings.DB_PASSWORD) { throw 'Use Test-Postgres.ps1 -Entrega: alvo local isolado obrigatório.' }
$schemaFile = Join-Path $projectRoot 'database\sql\00_estrutura.sql'
& (Join-Path $PostgresBin 'pg_dump.exe') -w -h 127.0.0.1 -p $port -U $dbUser -d $sourceDb --schema-only --no-owner --no-acl -f $schemaFile
Check-SqlExit 'exportação da estrutura criada por migrations'
php (Join-Path $PSScriptRoot 'generate-demo-sql.php')
Check-SqlExit 'dados exclusivamente fictícios'
$stamp = Get-Date -Format 'yyyyMMddHHmmss'
$sqlDb = 'bibliotequinha_sql_test_' + $stamp
$restoreDb = 'bibliotequinha_sql_restore_' + $stamp
& (Join-Path $PostgresBin 'createdb.exe') -w -h 127.0.0.1 -p $port -U $dbUser $sqlDb
Check-SqlExit 'novo banco vazio para scripts SQL'
foreach ($sqlFile in @('00_estrutura.sql', '03_dados_ficticios.sql', '04_validacao_local.sql')) {
    & (Join-Path $PostgresBin 'psql.exe') -w -h 127.0.0.1 -p $port -U $dbUser -d $sqlDb -v ON_ERROR_STOP=1 -f (Join-Path $projectRoot ('database\sql\' + $sqlFile))
    Check-SqlExit "execução $sqlFile"
}
$deliveryDir = Join-Path $projectRoot 'storage\app\entrega-local'
[IO.Directory]::CreateDirectory($deliveryDir) | Out-Null
$fakeDump = Join-Path $deliveryDir 'backup-ficticio.dump'
& (Join-Path $PostgresBin 'pg_dump.exe') -w -h 127.0.0.1 -p $port -U $dbUser -d $sqlDb -Fc --no-owner --no-acl -f $fakeDump
Check-SqlExit 'backup exclusivamente fictício'
& (Join-Path $PostgresBin 'createdb.exe') -w -h 127.0.0.1 -p $port -U $dbUser $restoreDb
Check-SqlExit 'novo destino vazio de restauração'
& (Join-Path $PostgresBin 'pg_restore.exe') -w -h 127.0.0.1 -p $port -U $dbUser -d $restoreDb --no-owner --no-acl --exit-on-error --single-transaction $fakeDump
Check-SqlExit 'restauração fictícia'
$query = "SELECT string_agg(entity || ':' || signature, '|' ORDER BY entity) FROM (SELECT 'users' AS entity, md5(coalesce(jsonb_agg(to_jsonb(t) ORDER BY id)::text,'[]')) AS signature FROM users t UNION ALL SELECT 'categories',md5(coalesce(jsonb_agg(to_jsonb(t) ORDER BY id)::text,'[]')) FROM categories t UNION ALL SELECT 'books',md5(coalesce(jsonb_agg(to_jsonb(t) ORDER BY id)::text,'[]')) FROM books t UNION ALL SELECT 'loans',md5(coalesce(jsonb_agg(to_jsonb(t) ORDER BY id)::text,'[]')) FROM loans t UNION ALL SELECT 'audits',md5(coalesce(jsonb_agg(to_jsonb(t) ORDER BY id)::text,'[]')) FROM loan_audits t) snapshots;"
$original = & (Join-Path $PostgresBin 'psql.exe') -w -h 127.0.0.1 -p $port -U $dbUser -d $sqlDb -tAc $query
Check-SqlExit 'assinaturas fictícias de origem'
$restored = & (Join-Path $PostgresBin 'psql.exe') -w -h 127.0.0.1 -p $port -U $dbUser -d $restoreDb -tAc $query
Check-SqlExit 'assinaturas fictícias restauradas'
if ($original -ne $restored) { throw 'Conteúdo restaurado divergente.' }
Write-Output 'Backup fictício restaurado: 3 usuários, 4 categorias, 16 livros, 2 empréstimos e 3 auditorias; conteúdo idêntico.'
& (Join-Path $PostgresBin 'psql.exe') -w -h 127.0.0.1 -p $port -U $dbUser -d $restoreDb -v ON_ERROR_STOP=1 -f (Join-Path $projectRoot 'database\sql\04_validacao_local.sql')
Check-SqlExit 'operações SQL após restauração'
$hash = (Get-FileHash -LiteralPath $fakeDump -Algorithm SHA256).Hash
Write-Output "SHA256 backup-ficticio.dump: $hash"
[IO.File]::WriteAllText((Join-Path $deliveryDir 'backup-ficticio.sha256'), ($hash + '  backup-ficticio.dump' + "`n"))
