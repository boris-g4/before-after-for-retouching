param(
    [string]$ProjectRoot = (Split-Path -Parent $PSScriptRoot)
)

$ErrorActionPreference = 'Stop'
$LanguageDirectory = Join-Path $ProjectRoot 'languages'
Add-Type -AssemblyName System.Web.Extensions
$JsonSerializer = New-Object System.Web.Script.Serialization.JavaScriptSerializer
$TranslationData = $JsonSerializer.DeserializeObject((Get-Content -Raw -Encoding UTF8 (Join-Path $LanguageDirectory 'translations.json')))
$Utf8 = New-Object System.Text.UTF8Encoding($false)

function Escape-Po([string]$Value) {
    return $Value.Replace('\', '\\').Replace('"', '\"').Replace("`r", '').Replace("`n", '\n')
}

function Write-Mo([string]$Path, $Messages, [string]$Locale) {
    $PluralForms = if ($Locale -eq 'uk') { 'nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);' } else { 'nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);' }
    $Header = "Project-Id-Version: Before & After for Retouching 1.0.0`nLanguage: $Locale`nMIME-Version: 1.0`nContent-Type: text/plain; charset=UTF-8`nContent-Transfer-Encoding: 8bit`nPlural-Forms: $PluralForms`n"
    $All = [System.Collections.Generic.SortedDictionary[string,string]]::new([System.StringComparer]::Ordinal)
    $All[''] = $Header
    foreach ($Key in $Messages.Keys) { $All[$Key] = $Messages[$Key] }
    $Keys = @($All.Keys | Sort-Object)
    $OriginalBytes = @($Keys | ForEach-Object { ,$Utf8.GetBytes([string]$_) })
    $TranslatedBytes = @($Keys | ForEach-Object { ,$Utf8.GetBytes([string]$All[$_]) })
    $Count = $Keys.Count
    $OriginalTableOffset = 28
    $TranslationTableOffset = $OriginalTableOffset + (8 * $Count)
    $OriginalDataOffset = $TranslationTableOffset + (8 * $Count)
    $TranslationDataOffset = $OriginalDataOffset
    foreach ($Bytes in $OriginalBytes) { $TranslationDataOffset += $Bytes.Length + 1 }

    $Stream = New-Object System.IO.MemoryStream
    $Writer = New-Object System.IO.BinaryWriter($Stream)
    $Writer.Write([uint32]::Parse('950412de', [System.Globalization.NumberStyles]::HexNumber))
    $Writer.Write([uint32]0)
    $Writer.Write([uint32]$Count)
    $Writer.Write([uint32]$OriginalTableOffset)
    $Writer.Write([uint32]$TranslationTableOffset)
    $Writer.Write([uint32]0)
    $Writer.Write([uint32]0)

    $Offset = $OriginalDataOffset
    foreach ($Bytes in $OriginalBytes) {
        $Writer.Write([uint32]$Bytes.Length)
        $Writer.Write([uint32]$Offset)
        $Offset += $Bytes.Length + 1
    }

    $Offset = $TranslationDataOffset
    foreach ($Bytes in $TranslatedBytes) {
        $Writer.Write([uint32]$Bytes.Length)
        $Writer.Write([uint32]$Offset)
        $Offset += $Bytes.Length + 1
    }

    foreach ($Bytes in $OriginalBytes) { $Writer.Write($Bytes); $Writer.Write([byte]0) }
    foreach ($Bytes in $TranslatedBytes) { $Writer.Write($Bytes); $Writer.Write([byte]0) }
    [System.IO.File]::WriteAllBytes($Path, $Stream.ToArray())
    $Writer.Dispose()
    $Stream.Dispose()
}

$TemplateMessages = @($TranslationData.Values)[0].Keys | Sort-Object
$Pot = @(
    'msgid ""',
    'msgstr ""',
    '"Project-Id-Version: Before & After for Retouching 1.0.0\n"',
    '"MIME-Version: 1.0\n"',
    '"Content-Type: text/plain; charset=UTF-8\n"',
    ''
)
foreach ($Message in $TemplateMessages) {
    $Pot += 'msgid "' + (Escape-Po $Message) + '"'
    $Pot += 'msgstr ""'
    $Pot += ''
}
[System.IO.File]::WriteAllLines((Join-Path $LanguageDirectory 'before-after-for-retouching.pot'), $Pot, $Utf8)

foreach ($Locale in $TranslationData.Keys) {
    $Messages = [System.Collections.Generic.SortedDictionary[string,string]]::new([System.StringComparer]::Ordinal)
    foreach ($Message in $TranslationData[$Locale].Keys) {
        $Messages[$Message] = [string]$TranslationData[$Locale][$Message]
    }

    $Po = @(
        'msgid ""',
        'msgstr ""',
        '"Project-Id-Version: Before & After for Retouching 1.0.0\n"',
        ('"Language: ' + $Locale + '\n"'),
        '"MIME-Version: 1.0\n"',
        '"Content-Type: text/plain; charset=UTF-8\n"',
        ''
    )
    foreach ($Message in $Messages.Keys) {
        $Po += 'msgid "' + (Escape-Po $Message) + '"'
        $Po += 'msgstr "' + (Escape-Po $Messages[$Message]) + '"'
        $Po += ''
    }
    [System.IO.File]::WriteAllLines((Join-Path $LanguageDirectory "before-after-for-retouching-$Locale.po"), $Po, $Utf8)
    Write-Mo (Join-Path $LanguageDirectory "before-after-for-retouching-$Locale.mo") $Messages $Locale

    $LocaleData = [System.Collections.Generic.SortedDictionary[string,object]]::new([System.StringComparer]::Ordinal)
    $LocaleData[''] = [ordered]@{ domain = 'messages'; lang = $Locale; 'plural-forms' = 'nplurals=3; plural=(n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2);' }
    foreach ($Message in $Messages.Keys) { $LocaleData[$Message] = @($Messages[$Message]) }
    $Jed = [ordered]@{
        'translation-revision-date' = (Get-Date).ToUniversalTime().ToString('yyyy-MM-dd HH:mm+0000')
        generator = 'Before & After for Retouching translation builder'
        source = 'assets/js/before-after-for-retouching-block.js'
        domain = 'messages'
        locale_data = [ordered]@{ messages = $LocaleData }
    }
    $SourcePath = 'assets/js/before-after-for-retouching-block.js'
    $Md5 = [System.Security.Cryptography.MD5]::Create()
    $Hash = ([System.BitConverter]::ToString($Md5.ComputeHash($Utf8.GetBytes($SourcePath)))).Replace('-', '').ToLowerInvariant()
    $JsonPath = Join-Path $LanguageDirectory "before-after-for-retouching-$Locale-$Hash.json"
    $Json = $Jed | ConvertTo-Json -Depth 8
    [System.IO.File]::WriteAllText($JsonPath, $Json, $Utf8)
    [System.IO.File]::WriteAllText((Join-Path $LanguageDirectory "before-after-for-retouching-$Locale-before-after-for-retouching-block-editor.json"), $Json, $Utf8)
}
