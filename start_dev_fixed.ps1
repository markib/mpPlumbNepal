# PlumbNepal Development Startup Script for PowerShell (Working Version)

Write-Host "--- Starting PlumbNepal Development Environment ---" -ForegroundColor Cyan

# Check if we're on Windows
if ($IsWindows -or $env:OS) {
    Write-Host "Running on Windows..." -ForegroundColor Yellow
} else {
    Write-Host "This script is designed for Windows PowerShell" -ForegroundColor Red
    exit 1
}

# Start the development servers
Write-Host "Starting development servers..." -ForegroundColor Yellow

# Create jobs for each service
$jobs = @()

# Start Laravel server
Write-Host "Starting Laravel server..." -ForegroundColor Green
$jobs += Start-Job -ScriptBlock {
    Set-Location $using:PSScriptRoot
    php artisan serve
} -Name "LaravelServer"

# Start Vite dev server
Write-Host "Starting Vite dev server..." -ForegroundColor Green
$jobs += Start-Job -ScriptBlock {
    Set-Location $using:PSScriptRoot
    npm run dev
} -Name "ViteServer"

# Start Reverb server
Write-Host "Starting Reverb server..." -ForegroundColor Green
$jobs += Start-Job -ScriptBlock {
    Set-Location $using:PSScriptRoot
    php artisan reverb:start
} -Name "ReverbServer"

# Start Queue worker
Write-Host "Starting Queue worker..." -ForegroundColor Green
$jobs += Start-Job -ScriptBlock {
    Set-Location $using:PSScriptRoot
    php artisan queue:work
} -Name "QueueWorker"

Write-Host "--- All services started as background jobs. ---" -ForegroundColor Cyan
Write-Host "Laravel: http://localhost:8000" -ForegroundColor White
Write-Host "Vite: http://localhost:5173" -ForegroundColor White
Write-Host "Use 'Stop-Job *; Remove-Job *' to stop all processes." -ForegroundColor Yellow
Write-Host "Press Ctrl+C to stop this script." -ForegroundColor Yellow

# Display job status
Write-Host "`nCurrent Jobs:" -ForegroundColor Cyan
$jobs | ForEach-Object {
    $job = Get-Job -Name $_.Name
    Write-Host "  $($_.Name): $($job.State)" -ForegroundColor White
}

# Keep script running to maintain jobs
Write-Host "`nServices are now running in the background." -ForegroundColor Green
Write-Host "Press Ctrl+C to stop all services and exit." -ForegroundColor Yellow

try {
    while ($true) {
        Start-Sleep -Seconds 5
        # Check if any jobs have failed
        $jobs | ForEach-Object {
            $job = Get-Job -Name $_.Name
            if ($job.State -eq "Failed") {
                Write-Host "Job $($_.Name) has failed!" -ForegroundColor Red
                $job | Receive-Job
            }
        }
    }
}
finally {
    Write-Host "`nStopping all background jobs..." -ForegroundColor Yellow
    $jobs | ForEach-Object {
        $job = Get-Job -Name $_.Name -ErrorAction SilentlyContinue
        if ($job) {
            if ($job.State -eq "Running") {
                Stop-Job -Name $_.Name -ErrorAction SilentlyContinue
            }
            Remove-Job -Name $_.Name -ErrorAction SilentlyContinue
        }
    }
    Write-Host "All jobs stopped." -ForegroundColor Green
}