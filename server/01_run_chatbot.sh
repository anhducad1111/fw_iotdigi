#!/bin/bash

# Script: run_chatbot.sh
# Usage: ./run_chatbot.sh <device_id> "<question>"
# Example: ./run_chatbot.sh "599" "Why is my bill high?"

# 1. Define paths (Auto-detect relative path)
BASE_DIR=$(pwd)
VENV_DIR="$BASE_DIR/env"
CHAT_SCRIPT="$BASE_DIR/chatbot/chat_core.py"


if [ ! -f "$CHAT_SCRIPT" ]; then
    echo "❌ Error: Could not find chat_core.py in '$BASE_DIR/chatbot/'"
    exit 1
fi

# 2. Check and Start Ollama (If not running)
echo "🔍 Checking Ollama status..."
if ! pgrep -x "ollama" > /dev/null; then
    echo "🚀 Starting Ollama service..."
    sudo systemctl start ollama
    sleep 2 # Wait for it to initialize
else
    echo "✅ Ollama is running."
fi

# 3. Activate Virtual Environment
if [ -d "$VENV_DIR" ]; then
    # echo "🐍 Activating Python Env..."
    source "$VENV_DIR/bin/activate"
else
    echo "⚠️  Virtual environment 'env' not found. Using system Python."
fi

# 4. Run Chatbot
echo "💬 Sending query to AI..."
echo "---------------------------------------------------"
python3 "$CHAT_SCRIPT" "$@"
echo "---------------------------------------------------"
