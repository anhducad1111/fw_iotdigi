# IoT Digi Chatbot Setup Guide

This folder contains the implementation of the RAG Chatbot using Ollama (Qwen 2.5) + Python + PHP.

## 1. Prerequisites
*   **Python 3.8+** installed and added to System PATH.
*   **Ollama** installed and running on port 11434.
    *   Install from [ollama.com](https://ollama.com).
    *   Pull the model: `ollama pull qwen2.5:3b`

## 2. Installation
1.  Install Python dependencies:
    ```bash
    pip install -r requirements.txt
    ```

## 3. Configuration
Edit `config.py` if needed:
*   `API_BASE_URL`: URL to `api.php` (default: http://localhost/fw_iotdigi/server/chatbot/api.php)
*   `OLLAMA_MODEL`: Model name (default: qwen2.5:3b)

## 4. Testing
### Backend API Test
Open in browser:
`http://localhost/fw_iotdigi/server/chatbot/api.php?action=get_monthly_summary&api_key=YOUR_DEVICE_ID`

### Python CLI Test
Run in terminal:
```bash
# Interactive Mode
python chat_core.py

# Single Shot Mode
python chat_core.py "DEVICE_ID_HERE" "How much water did I use today?"
```

### Web Integration
The `chat_interface.php` file is ready to be called via AJAX POST from your Dashboard.
Payload: `{"message": "Hello"}`
Cookie: Must have valid PHPSESSID logged in.

## 5. Deployment Notes
*   Ensure PHP `exec()` or `proc_open()` functions are enabled in `php.ini`.
*   If hosting on Raspberry Pi, ensure the user running Apache/PHP has permission to execute python script.
