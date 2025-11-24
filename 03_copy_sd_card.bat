@echo off
echo "=== COPY SD CARD START ===" > 03_copy_sd_card_log.txt
for /D %%d in ("F:\*") do rmdir /S /Q "%%d" >> 03_copy_sd_card_log.txt 2>&1
del /S /Q "F:\*" >> 03_copy_sd_card_log.txt 2>&1
xcopy sd-card\* "F:\" /E /I /Y >> 03_copy_sd_card_log.txt 2>&1
echo "=== COPY SD CARD END ===" >> 03_copy_sd_card_log.txt