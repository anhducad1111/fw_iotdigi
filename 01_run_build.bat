@echo off
echo "=== BUILD START ===" > 01_build_log.txt
pio run -s >> 01_build_log.txt 2>&1
echo "=== BUILD END ===" >> 01_build_log.txt
