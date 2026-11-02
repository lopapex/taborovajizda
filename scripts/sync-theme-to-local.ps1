[CmdletBinding(SupportsShouldProcess = $true)]
param(
    [Parameter(Mandatory = $true)]
    [ValidateNotNullOrEmpty()]
    [string]$LocalPublicPath
)

$ErrorActionPreference = 'Stop'

$repositoryRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$sourceTheme = Join-Path $repositoryRoot 'theme'

if (-not (Test-Path -LiteralPath (Join-Path $sourceTheme 'style.css') -PathType Leaf)) {
    throw "Source theme is incomplete: '$sourceTheme\style.css' does not exist."
}

$resolvedPublicPath = (Resolve-Path -LiteralPath $LocalPublicPath -ErrorAction Stop).Path
$requiredWordPressPaths = @(
    (Join-Path $resolvedPublicPath 'wp-admin'),
    (Join-Path $resolvedPublicPath 'wp-content'),
    (Join-Path $resolvedPublicPath 'wp-includes'),
    (Join-Path $resolvedPublicPath 'wp-config.php')
)

foreach ($requiredPath in $requiredWordPressPaths) {
    if (-not (Test-Path -LiteralPath $requiredPath)) {
        throw "The supplied path is not a WordPress public directory. Missing: '$requiredPath'."
    }
}

$themesDirectory = Join-Path $resolvedPublicPath 'wp-content\themes'
if (-not (Test-Path -LiteralPath $themesDirectory -PathType Container)) {
    throw "WordPress themes directory does not exist: '$themesDirectory'."
}

$resolvedThemesDirectory = (Resolve-Path -LiteralPath $themesDirectory).Path
$targetTheme = [System.IO.Path]::GetFullPath((Join-Path $resolvedThemesDirectory 'taborovajizda'))
$expectedTarget = [System.IO.Path]::GetFullPath((Join-Path $resolvedPublicPath 'wp-content\themes\taborovajizda'))

if ($targetTheme -ne $expectedTarget) {
    throw "Refusing to synchronize to an unexpected target: '$targetTheme'."
}

if ($sourceTheme.TrimEnd('\') -eq $targetTheme.TrimEnd('\')) {
    throw 'Source and target theme paths are identical.'
}

if (-not $PSCmdlet.ShouldProcess($targetTheme, "Mirror theme from '$sourceTheme'")) {
    return
}

New-Item -ItemType Directory -Path $targetTheme -Force | Out-Null

& robocopy.exe $sourceTheme $targetTheme /MIR /R:2 /W:1 /COPY:DAT /DCOPY:DAT /XJ /NFL /NDL /NJH /NJS /NP
$robocopyExitCode = $LASTEXITCODE

if ($robocopyExitCode -ge 8) {
    throw "Theme synchronization failed. Robocopy exit code: $robocopyExitCode."
}

Write-Host "Theme synchronized to: $targetTheme"
Write-Host "Robocopy exit code: $robocopyExitCode"

