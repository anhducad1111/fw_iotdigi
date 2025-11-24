@echo off
echo "=== UPLOAD START ===" > 02_upload_log.txt
pio run -t upload > 02_upload_log.txt 2>&1
echo "=== UPLOAD END ===" >> 02_upload_log.txt
