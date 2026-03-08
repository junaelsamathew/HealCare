$inputPath = "C:\xampp\htdocs\HealCare-main\table_content.txt"
$outputPath = "D:\JUNA ELSA MATHEW\formatted_tables.docx"

$word = New-Object -ComObject Word.Application
$word.Visible = $false
$doc = $word.Documents.Add()

$content = Get-Content $inputPath
$currentTableData = @()
$isFirstTable = $true

function Add-FormattedTable {
    param($data, $docRef)
    
    if ($data.Count -le 1) { return }
    
    $numRows = $data.Count
    $numCols = 5 # No, Field Name, Data Type (Size), Key Constraints, Description
    
    # Add a paragraph break before the table if not the first table
    $range = $docRef.Range()
    $range.Collapse(0) # Collapse to end
    
    $table = $docRef.Tables.Add($range, $numRows, $numCols)
    $table.Borders.Enable = $true
    
    # Set Headers
    $table.Cell(1, 1).Range.Text = "No."
    $table.Cell(1, 2).Range.Text = "Field Name"
    $table.Cell(1, 3).Range.Text = "Data Type (Size)"
    $table.Cell(1, 4).Range.Text = "Key Constraints"
    $table.Cell(1, 5).Range.Text = "Description"
    
    $table.Rows.Item(1).Range.Font.Bold = $true
    $table.Rows.Item(1).Shading.BackgroundPatternColorIndex = 15 # Gray-25%
    
    for ($i = 1; $i -lt $numRows; $i++) {
        $row = $data[$i] -split "\|"
        $fieldName = $row[0].Trim()
        $dataType = ""
        $keyConstraint = ""
        
        # Parse Data Type and Key Constraint
        if ($row.Count -gt 1) {
            $rawDataType = $row[1].Trim()
            if ($rawDataType -match "\((PK|FK|Primary Key|Foreign Key.*)\)") {
                $keyMatch = $matches[1]
                $dataType = $rawDataType -replace "\s*\($keyMatch\)", ""
                
                if ($keyMatch -eq "PK") { $keyConstraint = "Primary Key" }
                elseif ($keyMatch -eq "FK") { $keyConstraint = "Foreign Key" }
                else { $keyConstraint = $keyMatch }
            } else {
                $dataType = $rawDataType
            }
        }
        
        $description = if ($row.Count -gt 2) { $row[2].Trim() } else { "" }
        
        $table.Cell($i + 1, 1).Range.Text = "$i"
        $table.Cell($i + 1, 2).Range.Text = $fieldName
        $table.Cell($i + 1, 3).Range.Text = $dataType
        $table.Cell($i + 1, 4).Range.Text = $keyConstraint
        $table.Cell($i + 1, 5).Range.Text = $description
    }
    
    # Move range to end of document and add a paragraph
    $range = $docRef.Range()
    $range.Collapse(0)
    $range.InsertParagraphAfter()
}

$tableNumber = 1

foreach ($line in $content) {
    if ($line.StartsWith("Table ")) {
        if ($currentTableData.Count -gt 0) {
            # Add header for table
            $paramRange = $doc.Range()
            $paramRange.Collapse(0)
            $paramRange.Text = "Table $tableNumber"
            $paramRange.InsertParagraphAfter()
            $paramRange.Font.Bold = $true
            
            Add-FormattedTable -data $currentTableData -docRef $doc
            $currentTableData = @()
            $tableNumber++
        }
    } elseif ($line -eq "---") {
        # End of a table block, let the start handle adding to document
        continue
    } else {
        if (-not [string]::IsNullOrWhiteSpace($line)) {
            $currentTableData += $line
        }
    }
}

if ($currentTableData.Count -gt 0) {
    $paramRange = $doc.Range()
    $paramRange.Collapse(0)
    $paramRange.Text = "Table $tableNumber"
    $paramRange.InsertParagraphAfter()
    $paramRange.Font.Bold = $true
    
    Add-FormattedTable -data $currentTableData -docRef $doc
}

$doc.SaveAs([ref]$outputPath)
$doc.Close([ref]0)
$word.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($word) | Out-Null

Write-Host "Created formatted tables at $outputPath"
