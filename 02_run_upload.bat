@echo off
for /f "tokens=2-4 delims=/ " %%a in ('date /t') do (set mydate=%%c-%%a-%%b)
for /f "tokens=1-2 delims=/" %%a in ('time /t') do (set mytime=%%a:%%b)
echo "=== UPLOAD START - %mydate% %mytime% ===" > log/02_upload_log.txt
pio run -t upload >> log/02_upload_log.txt 2>&1
echo "=== UPLOAD END - %mydate% %mytime% ===" >> log/02_upload_log.txt