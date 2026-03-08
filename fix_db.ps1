$dataDir = "C:\xampp\mysql\data"
$backupDir = "C:\xampp\mysql\backup"
$oldDir = "C:\xampp\mysql\data_old_" + (Get-Date -Format "yyyyMMddHHmmss")

if (Test-Path $dataDir) {
    Rename-Item -Path $dataDir -NewName $oldDir
    Write-Host "Renamed data to $oldDir"
}

if (Test-Path $backupDir) {
    Copy-Item -Path $backupDir -Destination $dataDir -Recurse
    Write-Host "Copied backup to data"
}

if (Test-Path "$oldDir\healcare") {
    Copy-Item -Path "$oldDir\healcare" -Destination "$dataDir\healcare" -Recurse
    Write-Host "Copied healcare"
}

if (Test-Path "$oldDir\ibdata1") {
    Copy-Item -Path "$oldDir\ibdata1" -Destination "$dataDir\ibdata1" -Force
    Write-Host "Copied ibdata1"
}
