@echo off
REM Script to compress all HTML files in sd-card\html to .html.gz format
REM Usage: 04_zip.bat

echo ============================================
echo Compressing HTML files to .gz format
echo ============================================
echo.

REM Set the HTML directory path
set "HTML_DIR=%~dp0sd-card\html"

REM Check if directory exists
if not exist "%HTML_DIR%" (
    echo ERROR: Directory not found: %HTML_DIR%
    echo.
    pause
    exit /b 1
)

echo Working directory: %HTML_DIR%
echo.

REM Change to HTML directory
cd /d "%HTML_DIR%"

REM Count HTML files
set count=0
for %%f in (*.html) do set /a count+=1
echo Found %count% HTML files to compress
echo.

REM Use PowerShell to compress each HTML file
for %%f in (*.html) do (
    echo Compressing: %%f
    powershell -Command "$input = [System.IO.File]::ReadAllBytes('%%f'); $output = New-Object System.IO.FileStream('%%f.gz', [System.IO.FileMode]::Create); $gzip = New-Object System.IO.Compression.GZipStream($output, [System.IO.Compression.CompressionMode]::Compress); $gzip.Write($input, 0, $input.Length); $gzip.Close(); $output.Close()"
    if exist "%%f.gz" (
        echo   - Created: %%f.gz
    ) else (
        echo   - ERROR: Failed to create %%f.gz
    )
)

echo.
echo ============================================
echo Compression complete!
echo ============================================
echo.

REM Show summary of compressed files
echo Compressed files:
dir /b *.html.gz 2>nul
if errorlevel 1 (
    echo No .gz files found!
) else (
    echo.
    echo File sizes:
    dir *.html.gz | find ".html.gz"
)

echo.
pause
