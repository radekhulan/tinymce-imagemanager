<#
.SYNOPSIS
    Builds (or updates) the "TinyMCE with modern File & Image Manager" bundle.

.DESCRIPTION
    Downloads, assembles and wires together — into this folder — three things:

      * TinyMCE 8 (community / GPL build) + all language packs
      * the custom Lucide icon pack and skin overrides (from .\custom)
      * Radek Hulan's File & Image Manager (prebuilt GitHub release — no build step)

    Re-running the script updates everything to the latest versions. Your uploads
    (fileimagemanager\public\media) and your edited config are preserved across updates.

.PARAMETER TinymceVersion
    TinyMCE version to install, e.g. 8.6.0. Default: latest (resolved from the npm registry).

.PARAMETER FimVersion
    File & Image Manager release tag, e.g. v1.0.24. Default: latest GitHub release.

.PARAMETER SkipLangs
    Do not download the TinyMCE language packs.

.PARAMETER Help
    Show this help and exit.

.EXAMPLE
    .\setup.ps1
    Install / update everything to the latest versions.

.EXAMPLE
    .\setup.ps1 -TinymceVersion 8.6.0 -FimVersion v1.0.24
    Pin specific versions.
#>
[CmdletBinding()]
param(
    [string]$TinymceVersion = 'latest',
    [string]$FimVersion     = 'latest',
    [switch]$SkipLangs,
    [switch]$Help
)

if ($Help) { Get-Help -Detailed $PSCommandPath; exit 0 }

$ErrorActionPreference = 'Stop'
$ProgressPreference    = 'SilentlyContinue'   # speeds up Invoke-WebRequest enormously
try { [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12 } catch {}

# ---------------------------------------------------------------------------
#  Paths & helpers
# ---------------------------------------------------------------------------
$Root       = $PSScriptRoot
$Custom     = Join-Path $Root 'custom'
$TinymceDir = Join-Path $Root 'wysiwyg\tinymce'
$FimDir     = Join-Path $Root 'wysiwyg\fileimagemanager'
$Tmp        = Join-Path ([IO.Path]::GetTempPath()) ('tinymce-imgmgr-' + [Guid]::NewGuid().ToString('N'))

$UA = 'tinymce-imagemanager-setup'

function Step($m) { Write-Host "`n==> $m" -ForegroundColor Cyan }
function Ok($m)   { Write-Host "    $([char]0x2713) $m" -ForegroundColor Green }
function Note($m) { Write-Host "    $m" -ForegroundColor DarkGray }
function Warn($m) { Write-Host "    ! $m" -ForegroundColor Yellow }

function Get-Php {
    foreach ($c in @('c:\inetpub\php\php.exe', 'php', 'php.exe')) {
        $cmd = Get-Command $c -ErrorAction SilentlyContinue
        if ($cmd) { return $cmd.Source }
    }
    return $null
}

function Fetch($url, $outFile) {
    $headers = @{ 'User-Agent' = $UA }
    if ($url -like 'https://api.github.com/*' -and $env:GITHUB_TOKEN) {
        $headers['Authorization'] = "Bearer $($env:GITHUB_TOKEN)"
    }
    Invoke-WebRequest -Uri $url -OutFile $outFile -Headers $headers -UseBasicParsing
}

function FetchJson($url) {
    $headers = @{ 'User-Agent' = $UA }
    if ($url -like 'https://api.github.com/*' -and $env:GITHUB_TOKEN) {
        $headers['Authorization'] = "Bearer $($env:GITHUB_TOKEN)"
    }
    return Invoke-RestMethod -Uri $url -Headers $headers -UseBasicParsing
}

# ---------------------------------------------------------------------------
#  Sanity checks
# ---------------------------------------------------------------------------
foreach ($f in @('icons.js', 'custom.css', 'default-icons.min.js', 'patch-config.php')) {
    if (-not (Test-Path (Join-Path $Custom $f))) {
        throw "Missing custom asset: custom\$f — is the repository intact?"
    }
}

New-Item -ItemType Directory -Force -Path $Tmp | Out-Null

try {
    Write-Host "TinyMCE with modern File & Image Manager — setup" -ForegroundColor White
    Note "Target folder: $Root"

    # -----------------------------------------------------------------------
    #  1) TinyMCE core (GPL community build)
    # -----------------------------------------------------------------------
    Step 'TinyMCE core'
    if ($TinymceVersion -eq 'latest') {
        $TinymceVersion = (FetchJson 'https://registry.npmjs.org/tinymce/latest').version
        Note "Latest TinyMCE is $TinymceVersion"
    }
    $tmceZip = Join-Path $Tmp 'tinymce.zip'
    Fetch "https://download.tiny.cloud/tinymce/community/tinymce_$TinymceVersion.zip" $tmceZip
    Expand-Archive -Path $tmceZip -DestinationPath (Join-Path $Tmp 'tinymce') -Force
    $srcTmce = Join-Path $Tmp 'tinymce\tinymce\js\tinymce'
    if (-not (Test-Path $srcTmce)) { throw "Unexpected TinyMCE archive layout (no js\tinymce)" }

    if (Test-Path $TinymceDir) { Remove-Item $TinymceDir -Recurse -Force }
    New-Item -ItemType Directory -Force -Path $TinymceDir | Out-Null
    Copy-Item (Join-Path $srcTmce '*') $TinymceDir -Recurse -Force
    Ok "TinyMCE $TinymceVersion installed to .\tinymce"

    # -----------------------------------------------------------------------
    #  2) Language packs
    # -----------------------------------------------------------------------
    if (-not $SkipLangs) {
        Step 'TinyMCE language packs'
        $langZip = Join-Path $Tmp 'langs.zip'
        Fetch 'https://download.tiny.cloud/tinymce/community/languagepacks/8/langs.zip' $langZip
        Expand-Archive -Path $langZip -DestinationPath (Join-Path $Tmp 'langs') -Force
        New-Item -ItemType Directory -Force -Path (Join-Path $TinymceDir 'langs') | Out-Null
        Copy-Item (Join-Path $Tmp 'langs\langs\*') (Join-Path $TinymceDir 'langs') -Recurse -Force
        $langCount = (Get-ChildItem (Join-Path $TinymceDir 'langs') -Filter *.js).Count
        Ok "$langCount language files installed"
    } else {
        Warn 'Skipping language packs (-SkipLangs)'
    }

    # -----------------------------------------------------------------------
    #  3) Custom Lucide icons, skin overrides, empty default pack
    # -----------------------------------------------------------------------
    Step 'Custom icons & skin'
    $lucideDir  = Join-Path $TinymceDir 'icons\lucide'
    $defaultDir = Join-Path $TinymceDir 'icons\default'
    New-Item -ItemType Directory -Force -Path $lucideDir  | Out-Null
    New-Item -ItemType Directory -Force -Path $defaultDir | Out-Null
    # TinyMCE (min build) loads icons/<pack>/icons.min.js; icons.js is kept alongside it.
    Copy-Item (Join-Path $Custom 'icons.js') (Join-Path $lucideDir 'icons.js')     -Force
    Copy-Item (Join-Path $Custom 'icons.js') (Join-Path $lucideDir 'icons.min.js') -Force
    Copy-Item (Join-Path $Custom 'custom.css') (Join-Path $TinymceDir 'custom.css') -Force
    # Neutralise the stock default pack (it errors once the Lucide pack is active).
    Copy-Item (Join-Path $Custom 'default-icons.min.js') (Join-Path $defaultDir 'icons.min.js') -Force
    Ok 'Lucide pack + custom.css installed; default pack neutralised'

    # -----------------------------------------------------------------------
    #  4) File & Image Manager (prebuilt release — no composer/npm needed)
    # -----------------------------------------------------------------------
    Step 'File & Image Manager'
    if ($FimVersion -eq 'latest') {
        $rel = FetchJson 'https://api.github.com/repos/radekhulan/fileimagemanager/releases/latest'
    } else {
        $rel = FetchJson "https://api.github.com/repos/radekhulan/fileimagemanager/releases/tags/$FimVersion"
    }
    $FimTag = $rel.tag_name
    $asset  = $rel.assets | Where-Object { $_.name -like '*.zip' } | Select-Object -First 1
    if (-not $asset) { throw "No .zip asset found on release $FimTag" }
    Note "Release $FimTag — $($asset.name)"

    $fimZip = Join-Path $Tmp 'fim.zip'
    Fetch $asset.browser_download_url $fimZip
    Expand-Archive -Path $fimZip -DestinationPath (Join-Path $Tmp 'fim') -Force
    $srcFim = Join-Path $Tmp 'fim\fileimagemanager'
    if (-not (Test-Path $srcFim)) { throw "Unexpected release layout (no fileimagemanager\)" }

    # Preserve a user-edited config across updates.
    $cfgPath   = Join-Path $FimDir 'config\filemanager.php'
    $cfgBackup = $null
    if (Test-Path $cfgPath) { $cfgBackup = Get-Content $cfgPath -Raw }

    # Remove stale code (hashed asset names accumulate). Media lives at the repo root, untouched.
    if (Test-Path $FimDir) {
        foreach ($d in @('src', 'vendor', 'lang', 'public\assets', 'public\tinymce')) {
            $p = Join-Path $FimDir $d
            if (Test-Path $p) { Remove-Item $p -Recurse -Force }
        }
    }
    New-Item -ItemType Directory -Force -Path $FimDir | Out-Null
    Copy-Item (Join-Path $srcFim '*') $FimDir -Recurse -Force

    if ($null -ne $cfgBackup) {
        Set-Content -Path $cfgPath -Value $cfgBackup -NoNewline -Encoding UTF8
        Note 'Preserved your existing config\filemanager.php'
    }
    Ok "File & Image Manager $FimTag installed to .\fileimagemanager"

    # -----------------------------------------------------------------------
    #  5) Patch config: self-contained media paths + disabled security gate
    # -----------------------------------------------------------------------
    Step 'Configuring File & Image Manager'
    $php = Get-Php
    if (-not $php) { throw "PHP not found (looked for c:\inetpub\php\php.exe and php on PATH)." }
    & $php (Join-Path $Custom 'patch-config.php') $cfgPath | ForEach-Object { Note $_ }
    if ($LASTEXITCODE -ne 0) { throw "patch-config.php failed (exit $LASTEXITCODE)" }

    # Wire the manager's TinyMCE plugin into the editor as an internal plugin, so it loads
    # via the `plugins` list at tinymce/plugins/fileimagemanager/plugin.min.js (required —
    # otherwise TinyMCE can't find it and the editor falls back to a plain textarea).
    $pluginDst = Join-Path $TinymceDir 'plugins\fileimagemanager'
    New-Item -ItemType Directory -Force -Path $pluginDst | Out-Null
    Copy-Item (Join-Path $FimDir 'public\tinymce\plugin.js') (Join-Path $pluginDst 'plugin.min.js') -Force

    # Media is stored at the repository root (committed): .\media\source and .\media\thumbs
    foreach ($d in @('media\source', 'media\thumbs')) {
        $mp = Join-Path $Root $d
        New-Item -ItemType Directory -Force -Path $mp | Out-Null
        $keep = Join-Path $mp '.gitkeep'
        if (-not (Test-Path $keep)) { New-Item -ItemType File -Path $keep | Out-Null }
    }
    Ok 'Config patched; plugin wired into TinyMCE; media folders ready (.\media)'

    # -----------------------------------------------------------------------
    #  6) Version marker (read by the demo page)
    # -----------------------------------------------------------------------
    $marker = [ordered]@{
        tinymce          = $TinymceVersion
        fileimagemanager = $FimTag
        updated          = (Get-Date).ToString('yyyy-MM-dd HH:mm:ss')
    }
    ($marker | ConvertTo-Json) | Set-Content -Path (Join-Path $Root '.bundle-version.json') -Encoding UTF8

    # -----------------------------------------------------------------------
    #  Done
    # -----------------------------------------------------------------------
    $parts = $Root -split '[\\/]'
    $idx   = [array]::IndexOf($parts, 'wwwroot')
    if ($idx -ge 0 -and $idx -lt $parts.Count - 1) {
        $urlBase = '/' + (($parts[($idx + 1)..($parts.Count - 1)]) -join '/')
    } else {
        $urlBase = '/' + (Split-Path $Root -Leaf)
    }

    Write-Host "`n========================================================================" -ForegroundColor Green
    Write-Host " Done. TinyMCE $TinymceVersion + File & Image Manager $FimTag" -ForegroundColor Green
    Write-Host "========================================================================" -ForegroundColor Green
    Write-Host " Demo page :  http://localhost$urlBase/"
    Write-Host " Manager   :  http://localhost$urlBase/wysiwyg/fileimagemanager/public/"

    Write-Host "`n------------------------------------------------------------------------" -ForegroundColor Yellow
    Write-Host " SECURITY WARNING" -ForegroundColor Yellow
    Write-Host "------------------------------------------------------------------------" -ForegroundColor Yellow
    Write-Host " The File & Image Manager ships with NO authentication so the demo works"
    Write-Host " immediately. As installed, ANYONE who can reach the URL can upload,"
    Write-Host " rename and DELETE files on your server."
    Write-Host ""
    Write-Host " Before production, lock it down — see the SECURITY section in README.md"
    Write-Host " and the commented gate at the top of:"
    Write-Host "   wysiwyg\fileimagemanager\config\filemanager.php" -ForegroundColor White
    Write-Host "------------------------------------------------------------------------`n" -ForegroundColor Yellow
}
finally {
    if (Test-Path $Tmp) { Remove-Item $Tmp -Recurse -Force -ErrorAction SilentlyContinue }
}
