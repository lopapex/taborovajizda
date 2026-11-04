[CmdletBinding()]
param([string]$OutputDirectory)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$repositoryRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$themeRoot = Join-Path $repositoryRoot 'theme'
if (-not (Test-Path -LiteralPath (Join-Path $themeRoot 'style.css') -PathType Leaf)) {
    throw "Repository theme is incomplete: '$themeRoot\style.css' does not exist."
}

$status = & git -C $repositoryRoot status --porcelain --untracked-files=all
if ($LASTEXITCODE -ne 0) { throw 'Unable to read Git working tree status.' }
if ($status) { throw "Release requires a clean Git working tree.`n$($status -join "`n")" }

$trackedThemeFiles = @(& git -C $repositoryRoot ls-files -- theme)
if ($LASTEXITCODE -ne 0) { throw 'Unable to inspect tracked theme files.' }
$forbidden = $trackedThemeFiles | Where-Object {
    $name = [System.IO.Path]::GetFileName($_)
    $name -eq '.tags' -or $name -like '.env*' -or $name -like 'wp-config*.php' -or
    $name -match '\.(sql|sql\.gz|log|wpress|bm|tar|tar\.gz|tgz)$' -or
    $name -match '(?i)backup.*\.zip$'
}
if ($forbidden) {
    throw "Release refused because forbidden files are tracked in theme/:`n$($forbidden -join "`n")"
}

$commit = (& git -C $repositoryRoot rev-parse HEAD).Trim()
$shortCommit = (& git -C $repositoryRoot rev-parse --short=8 HEAD).Trim()
$tags = @(& git -C $repositoryRoot tag --points-at HEAD | Where-Object { $_ })
$tagText = if ($tags.Count) { $tags -join ', ' } else { '(none)' }
$date = Get-Date -Format 'yyyy-MM-dd'
$createdAt = Get-Date -Format 'o'

if ([string]::IsNullOrWhiteSpace($OutputDirectory)) {
    $OutputDirectory = Join-Path $repositoryRoot 'releases'
}
$OutputDirectory = [System.IO.Path]::GetFullPath($OutputDirectory)
New-Item -ItemType Directory -Path $OutputDirectory -Force | Out-Null

$baseName = "taborovajizda-theme-$date-$shortCommit"
$zipPath = Join-Path $OutputDirectory "$baseName.zip"
$manifestPath = Join-Path $OutputDirectory "$baseName-manifest.txt"
if (Test-Path -LiteralPath $zipPath) { Remove-Item -LiteralPath $zipPath -Force }
if (Test-Path -LiteralPath $manifestPath) { Remove-Item -LiteralPath $manifestPath -Force }

$temporaryRoot = Join-Path ([System.IO.Path]::GetTempPath()) ("tj-release-" + [guid]::NewGuid().ToString('N'))
$packageTheme = Join-Path $temporaryRoot 'taborovajizda'
$sourceArchive = Join-Path $temporaryRoot 'source.zip'
$sourceRoot = Join-Path $temporaryRoot 'source'
$sourceTheme = Join-Path $sourceRoot 'theme'
try {
    New-Item -ItemType Directory -Path $sourceRoot -Force | Out-Null
    & git -C $repositoryRoot archive --format=zip --output=$sourceArchive HEAD theme
    if ($LASTEXITCODE -ne 0) { throw 'Unable to export the committed theme source.' }
    Expand-Archive -LiteralPath $sourceArchive -DestinationPath $sourceRoot -Force

    Push-Location $sourceTheme
    try {
        & npm ci
        if ($LASTEXITCODE -ne 0) { throw 'npm ci failed while preparing the release.' }
        & npm run build
        if ($LASTEXITCODE -ne 0) { throw 'The production theme build failed.' }
    }
    finally {
        Pop-Location
    }

    New-Item -ItemType Directory -Path $packageTheme -Force | Out-Null

    Get-ChildItem -LiteralPath $sourceTheme -File -Filter '*.php' | ForEach-Object {
        Copy-Item -LiteralPath $_.FullName -Destination $packageTheme -Force
    }
    foreach ($file in @('style.css', 'theme.json')) {
        Copy-Item -LiteralPath (Join-Path $sourceTheme $file) -Destination $packageTheme -Force
    }
    foreach ($directory in @('app', 'config', 'fonts', 'languages', 'patterns', 'template-parts', 'web_images', 'public\build')) {
        $source = Join-Path $sourceTheme $directory
        if (Test-Path -LiteralPath $source) {
            $destination = Join-Path $packageTheme $directory
            New-Item -ItemType Directory -Path (Split-Path $destination -Parent) -Force | Out-Null
            Copy-Item -LiteralPath $source -Destination $destination -Recurse -Force
        }
    }

    Compress-Archive -LiteralPath $packageTheme -DestinationPath $zipPath -CompressionLevel Optimal
}
finally {
    if (Test-Path -LiteralPath $temporaryRoot) { Remove-Item -LiteralPath $temporaryRoot -Recurse -Force }
}

Add-Type -AssemblyName System.IO.Compression.FileSystem
$archive = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
try {
    $entries = @($archive.Entries)
    if (@($entries | Where-Object { $_.FullName -notlike 'taborovajizda/*' }).Count) {
        throw 'Release ZIP contains entries outside the taborovajizda/ root directory.'
    }
    $entryCount = $entries.Count
}
finally {
    $archive.Dispose()
}

$zipInfo = Get-Item -LiteralPath $zipPath
$sha256 = (Get-FileHash -Algorithm SHA256 -LiteralPath $zipPath).Hash
$manifest = @"
Taborova jizda - WordPress theme release

Created: $createdAt
Commit: $commit
Tag: $tagText
ZIP: $($zipInfo.Name)
Size: $($zipInfo.Length) bytes
SHA-256: $sha256
Archive entries: $entryCount
Archive root: taborovajizda/
Build: npm ci + npm run build from committed source

Deployment
1. WordPress -> Vzhled -> Sablony -> Instalovat sablonu -> Nahrat sablonu.
2. Select $($zipInfo.Name) and click Instalovat.
3. Choose to replace the existing installed theme with the uploaded version.
4. Do not activate another theme; taborovajizda should remain active.
5. Clear WordPress/cache layers and hard refresh the browser with Ctrl+F5.
6. Verify the homepage and every element affected by the release.

Rollback
Upload the previously working theme ZIP in the same way, or use the verified pre-deployment backup.
"@
Set-Content -LiteralPath $manifestPath -Value $manifest -Encoding UTF8

[pscustomobject]@{
    Zip = $zipPath; Manifest = $manifestPath; Commit = $commit; Tags = $tagText
    Bytes = $zipInfo.Length; SHA256 = $sha256; Entries = $entryCount
} | Format-List
