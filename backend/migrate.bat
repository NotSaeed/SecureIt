@echo off
REM SecureIt Database Migration Script for Windows
REM Make sure XAMPP is running before executing this script

echo.
echo ========================================
echo    SecureIt Database Migration Tool
echo ========================================
echo.

REM Check if PHP is available
php --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: PHP is not found in PATH
    echo Please make sure XAMPP is installed and PHP is in your PATH
    echo Or run this script from XAMPP's php directory
    pause
    exit /b 1
)

REM Check if XAMPP MySQL is running
echo Checking if MySQL is running...
netstat -an | find "3306" >nul
if errorlevel 1 (
    echo WARNING: MySQL might not be running on port 3306
    echo Please make sure XAMPP MySQL service is started
    echo.
)

if "%1"=="" (
    echo Usage: migrate.bat [command]
    echo.
    echo Available commands:
    echo   up     - Run all pending migrations
    echo   down   - Rollback last migration  
    echo   fresh  - Drop all tables and re-run migrations
    echo   status - Show migration status
    echo.
    pause
    exit /b 1
)

echo Running migration command: %1
echo.

REM Change to backend directory
cd /d "%~dp0"

REM Run the PHP migration script
php migrate.php %1

echo.
echo Migration command completed.
pause
