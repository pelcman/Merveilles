# Merveilles - Database Setup (PowerShell)

Write-Host "================================" -ForegroundColor Cyan
Write-Host " Merveilles - Database Setup"     -ForegroundColor Cyan
Write-Host "================================" -ForegroundColor Cyan
Write-Host ""

$Root = Resolve-Path (Join-Path $PSScriptRoot "..\..")

$DbHost = Read-Host "MySQL Host [localhost]"
if ([string]::IsNullOrWhiteSpace($DbHost)) { $DbHost = "localhost" }

$DbUser = Read-Host "MySQL User [root]"
if ([string]::IsNullOrWhiteSpace($DbUser)) { $DbUser = "root" }

$DbPass = Read-Host "MySQL Password (empty for none)"

$SchemaPath = Join-Path $Root "sql\schema.sql"

if (-not (Test-Path $SchemaPath)) {
    Write-Host "[ERROR] Schema file not found: $SchemaPath" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "Importing schema from sql\schema.sql ..." -ForegroundColor Yellow

$mysqlArgs = @("-h", $DbHost, "-u", $DbUser)
if (-not [string]::IsNullOrWhiteSpace($DbPass)) {
    $mysqlArgs += "-p$DbPass"
}

try {
    Get-Content $SchemaPath -Raw | & mysql @mysqlArgs
    if ($LASTEXITCODE -eq 0) {
        Write-Host ""
        Write-Host "[OK] Database 'merveilles' created successfully." -ForegroundColor Green
        Write-Host "     - players table"
        Write-Host "     - monsters table"
        Write-Host "     - specials table"
    } else {
        Write-Host "[ERROR] mysql exited with code $LASTEXITCODE" -ForegroundColor Red
    }
} catch {
    Write-Host "[ERROR] mysql command not found. Ensure MySQL client is in PATH." -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
}

Write-Host ""
Read-Host "Press Enter to exit"
