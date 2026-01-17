<#
  Local smoke test script for Variation Product Options

  - Runs PHP syntax checks for all PHP files in the repo.
  - Optionally, if WP-CLI is available and you provide a local WP path, copies this plugin into that WP install's plugins folder, activates it, and verifies the main data class loads.

  Usage (PowerShell):
    ./scripts/smoke-test.ps1
    ./scripts/smoke-test.ps1 -WpPath "C:\path\to\wp-install"
#>

[CmdletBinding()]
param(
    [string]$WpPath = ''
)

Write-Output "== VPO Local Smoke Test =="

Write-Output "1) Running PHP syntax checks..."
$failed = $false
Get-ChildItem -Recurse -Filter *.php | ForEach-Object {
    $file = $_.FullName
    Write-Output "Checking: $file"
    $output = & php -l $file 2>&1
    if ($LASTEXITCODE -ne 0) {
        Write-Error $output
        $failed = $true
    } else {
        Write-Output $output
    }
}

if ($failed) {
    Write-Error "PHP syntax check failed. Fix errors before proceeding."
    exit 1
}

Write-Output "PHP syntax checks passed."

# WP-CLI integration (optional)
if (-not (Get-Command wp -ErrorAction SilentlyContinue)) {
    Write-Warning "WP-CLI not found in PATH. Skipping WordPress integration steps. To run integration, install WP-CLI and re-run with -WpPath <path>"
    exit 0
}

if (-not $WpPath) {
    $WpPath = Read-Host "Enter full path to your WordPress installation (or leave blank to skip)"
}

if (-not $WpPath) {
    Write-Output "Skipping WordPress integration steps. Local syntax checks finished."
    exit 0
}

if (-not (Test-Path $WpPath)) {
    Write-Error "Provided WP path not found: $WpPath"
    exit 1
}

$pluginsDir = Join-Path $WpPath 'wp-content\plugins'
if (-not (Test-Path $pluginsDir)) {
    Write-Error "Could not find plugins directory at $pluginsDir"
    exit 1
}

Write-Output "2) Installing plugin to $pluginsDir (will overwrite existing folder 'variation-product-options' if present)"

$repoRoot = Resolve-Path (Join-Path $PSScriptRoot '..')
$dest = Join-Path $pluginsDir 'variation-product-options'

if (Test-Path $dest) {
    Write-Output "Removing existing plugin folder at $dest"
    Remove-Item -Recurse -Force $dest
}

Copy-Item -Recurse -Force $repoRoot.Path $dest

Write-Output "Plugin files copied. Activating plugin..."

& wp plugin activate variation-product-options --path=$WpPath 2>&1 | Write-Output

Write-Output "Verifying core class availability via wp eval..."
$check = & wp eval "echo class_exists('VPO_Data_Handler') ? 'VPO_DATA_HANDLER_OK' : 'VPO_DATA_HANDLER_MISSING';" --path=$WpPath 2>&1
Write-Output $check

if ($check -match 'VPO_DATA_HANDLER_OK') {
    Write-Output "Integration smoke passed: core class available."
    exit 0
} else {
    Write-Warning "Integration smoke did not find the core class. Check plugin activation and WP debug logs."
    exit 2
}
