$inputPath = "C:\xampp\htdocs\HealCare-main\table_content.txt"
$outputPath = "D:\JUNA ELSA MATHEW\final_table_format.docx"

$word = New-Object -ComObject Word.Application
$word.Visible = $false
$doc = $word.Documents.Add()

$content = Get-Content $inputPath
$currentTableData = @()

$tableTitles = @{
    1 = "Registrations table"
    2 = "Users table"
    3 = "Canteen_staff table"
    4 = "Patient_medical_records table"
    5 = "Doctors table"
    6 = "Nurses table"
    7 = "Lab_staff table"
    8 = "Pharmacists table"
    9 = "Receptionists table"
    10 = "Appointments table"
    11 = "Medical_records table"
    12 = "Prescriptions table"
    13 = "Lab_tests table"
    14 = "Billing table"
    15 = "Reports table"
    16 = "Payments table"
    17 = "Patient_profiles table"
}

function Add-FormattedTable {
    param($data, $docRef, $title)
    
    if ($data.Count -le 1) { return }
    
    $numRows = $data.Count
    $numCols = 5 # No, Field Name, Data Type (Size), Key Constraints, Description
    
    $range = $docRef.Range()
    $range.Collapse(0) # Collapse to end
    
    # Add table title
    $range.Text = $title
    $range.Font.Bold = $true
    $range.Font.Size = 13
    $range.InsertParagraphAfter()
    $range.Collapse(0)
    
    $table = $docRef.Tables.Add($range, $numRows, $numCols)
    $table.Borders.Enable = $true
    
    # Headers
    $table.Cell(1, 1).Range.Text = "No."
    $table.Cell(1, 2).Range.Text = "Field Name"
    $table.Cell(1, 3).Range.Text = "Data Type (Size)"
    $table.Cell(1, 4).Range.Text = "Key Constraints"
    $table.Cell(1, 5).Range.Text = "Description"
    
    $table.Rows.Item(1).Range.Font.Bold = $true
    $table.Rows.Item(1).Shading.BackgroundPatternColorIndex = 0 # 0 = wdAuto (No color)
    
    $pkList = @()
    $fkList = @()
    
    for ($i = 1; $i -lt $numRows; $i++) {
        $row = $data[$i] -split "\|"
        $fieldName = $row[0].Trim()
        $dataType = ""
        $keyConstraint = ""
        
        if ($row.Count -gt 1) {
            $rawDataType = $row[1].Trim()
            if ($rawDataType -match "\((PK|FK|Primary Key|Foreign Key.*)\)") {
                $keyMatch = $matches[1]
                $dataType = $rawDataType -replace "\s*\($keyMatch\)", ""
                $dataType = $dataType.Trim()
                
                if ($keyMatch -eq "PK") { 
                    $keyConstraint = "Primary key" 
                    $pkList += $fieldName
                }
                elseif ($keyMatch -eq "FK") { 
                    $keyConstraint = "Foreign key"
                    $fkList += $fieldName
                }
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
    
    $range = $docRef.Range()
    $range.Collapse(0)
    $range.InsertParagraphAfter()
    $range.Collapse(0)
    
    # Keys below table
    $keysText = ""
    if ($pkList.Count -gt 0) {
        $keysText += "Primary Key: " + ($pkList -join ", ") + "`r`n"
    } else {
        $keysText += "Primary Key: None`r`n"
    }

    if ($fkList.Count -gt 0) {
        $keysText += "Foreign Key: " + ($fkList -join ", ") + "`r`n"
    } else {
        $keysText += "Foreign Key: None`r`n"
    }
    
    $range.Text = $keysText
    $range.Font.Bold = $false
    $range.Font.Size = 11
    $range.InsertParagraphAfter()
    $range.InsertParagraphAfter()
    $range.Collapse(0)
}

$tableNumber = 1

foreach ($line in $content) {
    if ($line.StartsWith("Table ")) {
        if ($currentTableData.Count -gt 0) {
            $title = $tableTitles[$tableNumber]
            if ([string]::IsNullOrEmpty($title)) { $title = "Table $tableNumber" }
            Add-FormattedTable -data $currentTableData -docRef $doc -title $title
            $currentTableData = @()
            $tableNumber++
        }
    } elseif ($line -eq "---") {
        continue
    } else {
        if (-not [string]::IsNullOrWhiteSpace($line)) {
            $currentTableData += $line
        }
    }
}

if ($currentTableData.Count -gt 0) {
    $title = $tableTitles[$tableNumber]
    if ([string]::IsNullOrEmpty($title)) { $title = "Table $tableNumber" }
    Add-FormattedTable -data $currentTableData -docRef $doc -title $title
}

$doc.SaveAs([ref]$outputPath)
$doc.Close([ref]0)
$word.Quit()
[System.Runtime.Interopservices.Marshal]::ReleaseComObject($word) | Out-Null

Write-Host "Updated formatted tables successfully"
