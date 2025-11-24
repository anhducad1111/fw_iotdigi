@echo off
echo "=== BUILD START ===" > build_log.txt
pio run -s >> build_log.txt 2>&1
echo "=== BUILD END ===" >> build_log.txt
