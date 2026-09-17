param([int]$Port = 8001)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$oldScanDir = $env:PHP_INI_SCAN_DIR
$hasDriver = php -r "echo extension_loaded('pdo_pgsql') ? 'yes' : 'no';"
try {
    if ($hasDriver -ne 'yes') { $env:PHP_INI_SCAN_DIR = Join-Path $PSScriptRoot 'php-conf' }
    Push-Location $projectRoot
    try { php artisan serve --host=127.0.0.1 --port=$Port --no-reload }
    finally { Pop-Location }
} finally { $env:PHP_INI_SCAN_DIR = $oldScanDir }
