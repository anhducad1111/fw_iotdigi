@echo off
for /f "tokens=2-4 delims=/ " %%a in ('date /t') do (set mydate=%%c-%%a-%%b)
for /f "tokens=1-2 delims=/" %%a in ('time /t') do (set mytime=%%a:%%b)
echo "=== COPY SD CARD START - %mydate% %mytime% ===" > log/03_copy_sd_card_log.txt
for /D %%d in ("F:\*") do rmdir /S /Q "%%d" >> log/03_copy_sd_card_log.txt 2>&1
del /S /Q "F:\*" >> log/03_copy_sd_card_log.txt 2>&1
xcopy sd-card\* "F:\" /E /I /Y >> log/03_copy_sd_card_log.txt 2>&1
echo "=== COPY SD CARD END - %mydate% %mytime% ===" >> log/03_copy_sd_card_log.txt