# Installs the Laravel skeleton around the MonColis code already present in this folder.
# Prerequisites: PHP >= 8.3 (extensions: pdo_mysql, pdo_sqlite, mbstring, openssl, fileinfo, gd, zip) and Composer.
# Usage (from the project root): powershell -ExecutionPolicy Bypass -File scripts\setup.ps1

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$skeleton = Join-Path $env:TEMP 'moncolis-laravel-skeleton'

Set-Location $root

if (-not (Test-Path (Join-Path $root 'artisan'))) {
    Write-Host '==> Downloading the Laravel skeleton'
    if (Test-Path $skeleton) { Remove-Item -Recurse -Force $skeleton }
    composer create-project laravel/laravel $skeleton --prefer-dist --no-interaction
    if ($LASTEXITCODE -ne 0) { throw 'composer create-project failed' }

    Write-Host '==> Merging the skeleton (existing MonColis files are kept)'
    # /XC /XN /XO: never overwrite a file that already exists in the project.
    robocopy $skeleton $root /E /XC /XN /XO /XF .env /NFL /NDL /NJH /NJS /NP | Out-Null
    if ($LASTEXITCODE -ge 8) { throw "robocopy failed ($LASTEXITCODE)" }
    Remove-Item -Recurse -Force $skeleton

    composer config name kutiwa/moncolis-particulier-api
    composer config description 'Backend API de MonColis Particulier'
}

Write-Host '==> Sanctum (API tokens)'
composer require laravel/sanctum --no-interaction
if ($LASTEXITCODE -ne 0) { throw 'composer require laravel/sanctum failed' }
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --no-interaction

if (-not (Test-Path (Join-Path $root '.env'))) {
    Copy-Item (Join-Path $root '.env.example') (Join-Path $root '.env')
    php artisan key:generate --no-interaction
}

php artisan storage:link --no-interaction

Write-Host '==> Running the test suite (SQLite in memory)'
php artisan test

Write-Host ''
Write-Host 'Done. Next: create the MySQL database "moncolis_particulier", then:'
Write-Host '  php artisan migrate --seed'
Write-Host '  php artisan serve --host=0.0.0.0'
