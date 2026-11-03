[CmdletBinding()]
param(
    [string]$ConfigPath,
    [switch]$Apply
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$repositoryRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$repositoryTheme = (Resolve-Path (Join-Path $repositoryRoot 'theme')).Path
if (-not (Test-Path -LiteralPath (Join-Path $repositoryTheme 'style.css') -PathType Leaf)) {
    throw "Repository theme is incomplete: '$repositoryTheme\style.css' does not exist."
}

if ([string]::IsNullOrWhiteSpace($ConfigPath)) {
    $ConfigPath = Join-Path $repositoryRoot '.localwp.json'
}
if (-not (Test-Path -LiteralPath $ConfigPath -PathType Leaf)) {
    throw "LocalWP configuration does not exist: '$ConfigPath'. Copy .localwp.example.json to .localwp.json and set localPublicPath."
}
try {
    $configuration = Get-Content -LiteralPath $ConfigPath -Raw -Encoding UTF8 | ConvertFrom-Json -ErrorAction Stop
}
catch {
    throw "LocalWP configuration is not valid JSON: '$ConfigPath'. $($_.Exception.Message)"
}
if (-not ($configuration.PSObject.Properties.Name -contains 'localPublicPath') -or
    [string]::IsNullOrWhiteSpace([string]$configuration.localPublicPath)) {
    throw "LocalWP configuration must contain a non-empty localPublicPath: '$ConfigPath'."
}

$publicPath = [System.IO.Path]::GetFullPath([Environment]::ExpandEnvironmentVariables([string]$configuration.localPublicPath))
if (-not (Test-Path -LiteralPath $publicPath -PathType Container)) {
    throw "LocalWP public directory does not exist: '$publicPath'."
}
$publicPath = (Resolve-Path -LiteralPath $publicPath).Path
foreach ($relativePath in @('wp-admin', 'wp-content', 'wp-includes', 'wp-config.php')) {
    $requiredPath = Join-Path $publicPath $relativePath
    if (-not (Test-Path -LiteralPath $requiredPath)) {
        throw "The configured path is not a WordPress public directory. Missing: '$requiredPath'."
    }
}

$themesDirectory = (Resolve-Path (Join-Path $publicPath 'wp-content\themes')).Path
$junctionPath = [System.IO.Path]::GetFullPath((Join-Path $themesDirectory 'taborovajizda'))
$expectedPath = [System.IO.Path]::GetFullPath((Join-Path $publicPath 'wp-content\themes\taborovajizda'))
if ($junctionPath -ne $expectedPath) {
    throw "Refusing unexpected LocalWP theme path: '$junctionPath'."
}

if (Test-Path -LiteralPath $junctionPath) {
    $existing = Get-Item -LiteralPath $junctionPath -Force
    if ($existing.LinkType -eq 'Junction') {
        $resolvedTarget = [System.IO.Path]::GetFullPath([string]$existing.Target)
        if ($resolvedTarget -eq $repositoryTheme) {
            Write-Host "LocalWP is already connected: $junctionPath -> $repositoryTheme"
            return
        }
        throw "LocalWP theme is already a junction to a different target: '$resolvedTarget'."
    }
    if (-not (Test-Path -LiteralPath (Join-Path $junctionPath 'style.css') -PathType Leaf)) {
        throw "Existing LocalWP theme is incomplete and will not be moved automatically: '$junctionPath'."
    }
}

$timestamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$backupPath = Join-Path $themesDirectory "taborovajizda.pre-junction-$timestamp"
Write-Host "Repository theme: $repositoryTheme"
Write-Host "LocalWP junction: $junctionPath"
if (Test-Path -LiteralPath $junctionPath) {
    Write-Host "Existing LocalWP theme backup: $backupPath"
}
if (-not $Apply) {
    Write-Host 'Preview only. Run again with -Apply to move the existing local theme to the backup path and create the junction.'
    return
}

if (Test-Path -LiteralPath $backupPath) {
    throw "Backup path already exists: '$backupPath'."
}
if (Test-Path -LiteralPath $junctionPath) {
    Move-Item -LiteralPath $junctionPath -Destination $backupPath
}

try {
    New-Item -ItemType Junction -Path $junctionPath -Target $repositoryTheme -ErrorAction Stop | Out-Null
}
catch {
    if ((Test-Path -LiteralPath $backupPath) -and -not (Test-Path -LiteralPath $junctionPath)) {
        Move-Item -LiteralPath $backupPath -Destination $junctionPath
    }
    throw "Junction creation failed; the original theme was restored. $($_.Exception.Message)"
}

$created = Get-Item -LiteralPath $junctionPath -Force
if ($created.LinkType -ne 'Junction' -or [System.IO.Path]::GetFullPath([string]$created.Target) -ne $repositoryTheme) {
    throw 'Junction verification failed.'
}

Write-Host "Connected: $junctionPath -> $repositoryTheme"
if (Test-Path -LiteralPath $backupPath) {
    Write-Host "Original LocalWP theme preserved at: $backupPath"
}
