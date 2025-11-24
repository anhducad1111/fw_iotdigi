@echo off
echo "=== UPLOAD START ===" > upload_log.txt
pio run -t upload > upload_log.txt 2>&1
echo "=== UPLOAD END ===" >> upload_log.txt
