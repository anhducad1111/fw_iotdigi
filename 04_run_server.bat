@echo off
echo Starting Python HTTP Server...
echo Open http://localhost:8080 in your browser.
python -m http.server 8080 --bind 127.0.0.1 --directory sd-card/html
pause
