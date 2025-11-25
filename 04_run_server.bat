@echo off
echo Starting Python HTTP Server...
echo Open http://localhost:8000 in your browser.
python -m http.server 8000 --directory sd-card/html
pause
