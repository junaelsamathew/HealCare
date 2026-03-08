$filePath = "D:\JUNA ELSA MATHEW\table.docx"
$word = New-Object -ComObject Word.Application
$word.Visible = $false
$doc = $word.Documents.Open($filePath)

$output = "C:\xampp\htdocs\HealCare-main\table_content.txt"
if (Test-Path $output) { Remove-Item $output }

for ($i = 1; $i -le $doc.Tables.Count; $i++) {
    $table = $doc.Tables.Item($i)
    Add-Content -Path $output -Value "Table $i"
    for ($r = 1; $r -le $table.Rows.Count; $r++) {
        $rowText = ""
        for ($c = 1; $c -le $table.Columns.Count; $c++) {
            try {
                $cellText = $table.Cell($r, $c).Range.Text
                # Clean up Word's carriage returns and bell characters
                $cellText = $cellText -replace "`r", "" -replace "`a", ""
                $rowText += $cellText + "| "
            } catch {
            }
        }
        Add-Content -Path $output -Value $rowText
    }
    Add-Content -Path $output -Value "---"
}

$doc.Close([ref]0)
$word.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($word) | Out-Null
Write-Host "Read tables to table_content.txt"
