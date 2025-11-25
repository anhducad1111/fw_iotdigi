@echo off
for /f "tokens=2-4 delims=/ " %%a in ('date /t') do (set mydate=%%c-%%a-%%b)
for /f "tokens=1-2 delims=/" %%a in ('time /t') do (set mytime=%%a:%%b)
echo "=== BUILD START - %mydate% %mytime% ===" > log/01_build_log.txt
pio run -s >> log/01_build_log.txt 2>&1
if %ERRORLEVEL% EQU 0 (
  echo "=== BUILD SUCCESS - %mydate% %mytime% ===" >> log/01_build_log.txt
) else (
  echo "=== BUILD FAILED - %mydate% %mytime% ===" >> log/01_build_log.txt
)