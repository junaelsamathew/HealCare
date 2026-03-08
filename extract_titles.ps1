$filePath = "D:\JUNA ELSA MATHEW\table.docx"
$word = New-Object -ComObject Word.Application
$word.Visible = $false
$doc = $word.Documents.Open($filePath)

$output = "C:\xampp\htdocs\HealCare-main\table_titles.txt"
if (Test-Path $output) { Remove-Item $output }

for ($i = 1; $i -le $doc.Tables.Count; $i++) {
    $table = $doc.Tables.Item($i)
    $range = $table.Range
    $range.Collapse(1)
    $range.Move(-4, -1) | Out-Null
    $range.Expand(4) | Out-Null
    $title = $range.Text -replace "`r", "" -replace "`a", "" -replace "`n", ""
    Add-Content -Path $output -Value "Table $($i): $title"
}

$doc.Close([ref]0)
$word.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($word) | Out-Null
Write-Host "Extracted titles"
