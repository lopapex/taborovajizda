[CmdletBinding(SupportsShouldProcess)]
param(
    [Parameter(Mandatory)]
    [ValidateRange(2000, 2200)]
    [int]$Year,

    [Parameter(Mandatory)]
    [ValidatePattern('^[a-z0-9]+(?:-[a-z0-9]+)*$')]
    [string]$Slug,

    [Parameter(Mandatory)]
    [ValidateNotNullOrEmpty()]
    [string]$Title,

    [AllowNull()]
    [string]$StartDate,

    [AllowNull()]
    [string]$EndDate,

    [AllowNull()]
    [Nullable[int]]$Price,

    [ValidateSet('hidden', 'coming-soon', 'open', 'full', 'closed')]
    [string]$RegistrationStatus = 'coming-soon',

    [AllowNull()]
    [string]$ExternalSeasonKey,

    [switch]$Apply
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$repositoryRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$seasonPath = Join-Path $repositoryRoot 'theme\config\season.json'
$assetDirectory = Join-Path $repositoryRoot 'theme\resources\assets\season'
$branchSlug = "$Year-$Slug"
$branchName = "season/$branchSlug"

function ConvertTo-IsoDateOrNull {
    param([AllowNull()][string]$Value, [string]$Name)
    if ([string]::IsNullOrWhiteSpace($Value)) { return $null }
    $parsed = [datetime]::MinValue
    if (-not [datetime]::TryParseExact($Value, 'yyyy-MM-dd', [Globalization.CultureInfo]::InvariantCulture, [Globalization.DateTimeStyles]::None, [ref]$parsed)) {
        throw "$Name must use YYYY-MM-DD or be omitted."
    }
    return $parsed.ToString('yyyy-MM-dd')
}

$normalisedStart = ConvertTo-IsoDateOrNull -Value $StartDate -Name 'StartDate'
$normalisedEnd = ConvertTo-IsoDateOrNull -Value $EndDate -Name 'EndDate'
if ($normalisedStart -and $normalisedEnd -and $normalisedStart -gt $normalisedEnd) {
    throw 'StartDate must not be after EndDate.'
}

$configuration = [ordered]@{
    '$schema' = './season.schema.json'
    year = $Year
    slug = $branchSlug
    title = $Title
    startDate = $normalisedStart
    endDate = $normalisedEnd
    price = [ordered]@{
        amount = if ($null -eq $Price) { $null } else { [int]$Price }
        currency = 'CZK'
    }
    registration = [ordered]@{
        status = $RegistrationStatus
        externalSeasonKey = if ([string]::IsNullOrWhiteSpace($ExternalSeasonKey)) { $null } else { $ExternalSeasonKey }
    }
    assets = [ordered]@{
        hero = $null
        logo = 'web_images/logo_color.svg'
    }
}

$previous = Get-Content -Raw -LiteralPath $seasonPath | ConvertFrom-Json
$previousYear = [string]$previous.year
$references = @(& rg -n --glob '!node_modules/**' --glob '!public/build/**' $previousYear (Join-Path $repositoryRoot 'theme'))
if ($LASTEXITCODE -gt 1) { throw 'Unable to scan previous-season references.' }

[pscustomobject]@{
    Mode = if ($Apply) { 'APPLY' } else { 'PREVIEW' }
    Branch = $branchName
    SeasonFile = $seasonPath
    AssetDirectory = $assetDirectory
    PreviousYear = $previousYear
    PreviousYearReferences = $references.Count
} | Format-List

$configuration | ConvertTo-Json -Depth 10
if ($references.Count) {
    "`nReferences to $previousYear that require review:"
    $references
}

if (-not $Apply) {
    "`nPreview only. Re-run with -Apply after reviewing the values and references."
    return
}

$status = @(& git -C $repositoryRoot status --porcelain --untracked-files=all)
if ($LASTEXITCODE -ne 0) { throw 'Unable to read Git working tree status.' }
if ($status.Count) { throw "A new season requires a clean working tree.`n$($status -join "`n")" }

$currentBranch = (& git -C $repositoryRoot branch --show-current).Trim()
if ($currentBranch -eq 'main') {
    & git -C $repositoryRoot switch -c $branchName
    if ($LASTEXITCODE -ne 0) { throw "Unable to create branch '$branchName'." }
} elseif ($currentBranch -ne $branchName) {
    throw "Run from main or the intended '$branchName' branch. Current branch: '$currentBranch'."
}

New-Item -ItemType Directory -Path $assetDirectory -Force | Out-Null
$json = $configuration | ConvertTo-Json -Depth 10
Set-Content -LiteralPath $seasonPath -Value $json -Encoding UTF8

"Updated $seasonPath. Add confirmed season artwork, update theme.json tokens, then run npm run check from theme/."
