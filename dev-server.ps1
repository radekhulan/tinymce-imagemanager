<#
.SYNOPSIS
    Runs the demo page (index.php) and the File & Image Manager in PHP's built-in web server.

.DESCRIPTION
    Starts `php -S` with router.php, which routes the demo and the manager just like IIS/Apache
    would. By default it listens on every interface (0.0.0.0) and prints both the localhost URL
    and your machine's LAN IP, so you can open the project from another device on the network.

.PARAMETER Port
    TCP port to listen on. Default: 8080.

.PARAMETER LocalOnly
    Bind to localhost only (not reachable from the network).

.PARAMETER NoBrowser
    Do not open the default browser automatically.

.EXAMPLE
    .\dev-server.ps1
.EXAMPLE
    .\dev-server.ps1 -Port 9000 -LocalOnly
#>
[CmdletBinding()]
param(
    [int]$Port = 8080,
    [switch]$LocalOnly,
    [switch]$NoBrowser
)

$ErrorActionPreference = 'Stop'
$root = $PSScriptRoot

function Get-Php {
    foreach ($c in @('c:\inetpub\php\php.exe', 'php', 'php.exe')) {
        $cmd = Get-Command $c -ErrorAction SilentlyContinue
        if ($cmd) { return $cmd.Source }
    }
    return $null
}

function Get-LanIPv4 {
    $ips = @()
    try {
        $ips = Get-NetIPAddress -AddressFamily IPv4 -ErrorAction Stop |
            Where-Object {
                $_.IPAddress -notlike '127.*' -and
                $_.IPAddress -notlike '169.254.*' -and
                $_.PrefixOrigin -ne 'WellKnown'
            } |
            Sort-Object InterfaceMetric |
            Select-Object -ExpandProperty IPAddress
    } catch {
        $ips = [System.Net.Dns]::GetHostAddresses([System.Net.Dns]::GetHostName()) |
            Where-Object { $_.AddressFamily -eq 'InterNetwork' -and $_.IPAddressToString -ne '127.0.0.1' } |
            ForEach-Object { $_.IPAddressToString }
    }
    return @($ips | Select-Object -Unique)
}

$php = Get-Php
if (-not $php) { throw "PHP not found (looked for c:\inetpub\php\php.exe and php on PATH)." }

if (-not (Test-Path (Join-Path $root 'tinymce\tinymce.min.js'))) {
    Write-Host "Note: the bundle isn't assembled yet — run .\setup.ps1 first." -ForegroundColor Yellow
}

if ($LocalOnly) { $bind = '127.0.0.1' } else { $bind = '0.0.0.0' }
$localUrl = "http://localhost:$Port/"

Write-Host "`nPHP dev server" -ForegroundColor White
Write-Host "  root    : $root"
Write-Host "  Local   : $localUrl" -ForegroundColor Cyan
if (-not $LocalOnly) {
    $lan = Get-LanIPv4
    if ($lan.Count -gt 0) {
        foreach ($ip in $lan) {
            Write-Host "  Network : http://$($ip):$Port/" -ForegroundColor Green
        }
    } else {
        Write-Host "  Network : (no LAN IPv4 detected)" -ForegroundColor DarkGray
    }
    Write-Host "  manager : http://localhost:$Port/wysiwyg/fileimagemanager/public/"
    Write-Host "  ! Reachable on your network — the file manager has no auth. Use -LocalOnly to restrict." -ForegroundColor Yellow
} else {
    Write-Host "  manager : ${localUrl}wysiwyg/fileimagemanager/public/"
}
Write-Host "  (press Ctrl+C to stop)`n"

if (-not $NoBrowser) {
    Start-Job -ScriptBlock { param($u) Start-Sleep -Milliseconds 900; Start-Process $u } -ArgumentList $localUrl | Out-Null
}

& $php -S "$($bind):$Port" -t $root (Join-Path $root 'router.php')
