param(
    [string]$ProjectRoot = (Split-Path -Parent $PSScriptRoot),
    [string]$Version = '1.0.0'
)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$ProjectRoot = (Resolve-Path $ProjectRoot).Path
$DistDirectory = Join-Path $ProjectRoot 'dist'
$OutputPath = Join-Path $DistDirectory "before-after-for-retouching-$Version.zip"
$IncludedFiles = @('before-after-for-retouching.php', 'LICENSE.txt', 'readme.txt')
$IncludedDirectories = @('assets', 'block', 'includes', 'languages')

New-Item -ItemType Directory -Force $DistDirectory | Out-Null
if (Test-Path -LiteralPath $OutputPath) {
    Remove-Item -LiteralPath $OutputPath -Force
}

$Files = @()
foreach ($File in $IncludedFiles) {
    $Files += Get-Item -LiteralPath (Join-Path $ProjectRoot $File)
}
foreach ($Directory in $IncludedDirectories) {
    $Files += Get-ChildItem -LiteralPath (Join-Path $ProjectRoot $Directory) -Recurse -File |
        Where-Object { $_.Name -ne 'translations.json' }
}

$Stream = [System.IO.File]::Open($OutputPath, [System.IO.FileMode]::CreateNew)
$Archive = New-Object System.IO.Compression.ZipArchive($Stream, [System.IO.Compression.ZipArchiveMode]::Create)

try {
    foreach ($File in $Files) {
        $RelativePath = $File.FullName.Substring($ProjectRoot.Length).TrimStart('\', '/').Replace('\', '/')
        $Entry = $Archive.CreateEntry("before-after-for-retouching/$RelativePath", [System.IO.Compression.CompressionLevel]::Optimal)
        $EntryStream = $Entry.Open()
        $FileStream = [System.IO.File]::OpenRead($File.FullName)
        try {
            $FileStream.CopyTo($EntryStream)
        } finally {
            $FileStream.Dispose()
            $EntryStream.Dispose()
        }
    }
} finally {
    $Archive.Dispose()
    $Stream.Dispose()
}

Get-Item -LiteralPath $OutputPath
