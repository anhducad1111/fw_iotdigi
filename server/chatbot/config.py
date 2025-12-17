# server/chatbot/config.py

# Configuration for Chatbot

# API URL to the PHP Backend
# Adjust this based on your actual Web Server path
# If your project is at /var/www/html/iotdigi, then URL is likely:
API_BASE_URL = "http://localhost/iotdigi/chatbot/api.php"

# Ollama Configuration
OLLAMA_API_URL = "http://localhost:11434/api/generate"
OLLAMA_MODEL = "llama3.2:1b"

# Timeout settings
TIMEOUT_API = 5
TIMEOUT_LLM = 300

# Path to Knowledge Base
KB_PATH = "kb"
