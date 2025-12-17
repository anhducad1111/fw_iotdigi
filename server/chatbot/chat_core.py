import requests
import json
import os
import datetime
import time
try:
    from config import API_BASE_URL, OLLAMA_API_URL, OLLAMA_MODEL, KB_PATH
except ImportError:
    # Fallback if run directly
    API_BASE_URL = "http://localhost/iotdigi/chatbot/api.php"
    OLLAMA_API_URL = "http://localhost:11434/api/generate"
    OLLAMA_MODEL = "llama3.2:1b"
    KB_PATH = "kb"

class IoTChatbot:
    def __init__(self, api_key):
        self.api_key = api_key
        self.context_data = {}
        self.kb_content = ""

    def load_kb(self, filename):
        """Load a specific markdown file from KB directory"""
        try:
            path = os.path.join(os.path.dirname(__file__), KB_PATH, filename)
            with open(path, 'r', encoding='utf-8') as f:
                return f.read() + "\n\n"
        except Exception as e:
            # print(f"Error loading KB {filename}: {e}")
            return ""

    def detect_intent(self, query):
        """Strict Intent Mapping based on keywords"""
        q = query.lower()
        
        if any(w in q for w in ["bill", "cost", "money", "price", "pay"]):
            return "get_monthly_summary"
        
        if any(w in q for w in ["today", "yesterday", "daily", "day usage"]):
            return "get_daily_usage"
        
        # Check if query contains a date pattern, treat as daily usage request
        import re
        if re.search(r'\d{4}-\d{2}-\d{2}', q) or re.search(r'\d{1,2}/\d{1,2}/\d{4}', q):
             return "get_daily_usage"

        if any(w in q for w in ["chart", "trend", "graph", "history"]):
            return "get_usage_chart"
        
        if any(w in q for w in ["alert", "error", "leak", "problem", "broken"]):
            return "get_active_alerts"
            
        if any(w in q for w in ["status", "online", "offline"]):
            return "get_device_status"
            
        return "general_explanation"

    def extract_date(self, query):
        """Extract date YYYY-MM-DD, DD/MM/YYYY or handle today/yesterday"""
        import re
        today = datetime.date.today()
        
        # Check specific format YYYY-MM-DD
        match_iso = re.search(r'(\d{4})-(\d{2})-(\d{2})', query)
        if match_iso:
            return match_iso.group(0)

        # Check specific format DD/MM/YYYY
        match_vn = re.search(r'(\d{1,2})/(\d{1,2})/(\d{4})', query)
        if match_vn:
            # Convert to YYYY-MM-DD
            day, month, year = match_vn.groups()
            return f"{year}-{month.zfill(2)}-{day.zfill(2)}"
            
        # Check keywords
        if "yesterday" in query.lower():
            return (today - datetime.timedelta(days=1)).strftime("%Y-%m-%d")
            
        return today.strftime("%Y-%m-%d")

    def _direct_api_call(self, action, extra_params={}):
        """Helper for Agent Direct Call"""
        params = {
            'action': action,
            'api_key': self.api_key
        }
        params.update(extra_params)
        try:
            response = requests.get(API_BASE_URL, params=params, timeout=5)
            response.raise_for_status()
            return response.json()
        except:
            return {}

    def build_prompt(self, query, intent, data, kb_text):
        """Construct the System Prompt for Qwen/Llama"""
        
        system_prompt = f"""You are an IoT Water Assistant.
ROLE: Explain water usage data clearly and concisely.

RULES:
1. Answer strictly based on the provided JSON DATA.
2. NEVER output the raw JSON or code blocks.
3. Do NOT say "Based on the JSON data...". Just state the facts directly.
4. Keep answers short (1-2 sentences).
5. Format dates nicely (e.g., "On Dec 12, 2025").

EXAMPLE:
Input Data: {{"consumption": 0.5}}
Bad Answer: Based on the json {{...}}, the value is 0.5.
Good Answer: Your water consumption was 0.5 m³.

CONTEXT:
Intent: {intent}
Time: {datetime.datetime.now().strftime("%Y-%m-%d %H:%M")}

JSON DATA:
{json.dumps(data, indent=2)}

KNOWLEDGE BASE:
{kb_text}
"""
        return f"{system_prompt}\nUSER QUESTION: {query}\nASSISTANT:"

    def chat(self, user_query):
        # 1. Fast Intent Detection (Rule-based)
        intent = self.detect_intent(user_query)
        # print(f"DEBUG: Detected Intent: {intent}")
        
        # 2. Fetch Data & Select KB
        api_data = {}
        kb_text = ""
        
        if intent == "get_monthly_summary":
            api_data = self._direct_api_call("get_monthly_summary") # TODO extract month/year if needed
            kb_text += self.load_kb("billing_calculation.md") + self.load_kb("high_bill_reasons.md")
            
        elif intent == "get_daily_usage":
            # Smart Date Extraction
            date_str = self.extract_date(user_query)
            api_data = self._direct_api_call("get_daily_usage", {"date": date_str})
            kb_text += self.load_kb("usage_vs_value.md")
            
        elif intent == "get_usage_chart":
            api_data = self._direct_api_call("get_usage_chart")
            
        elif intent == "get_active_alerts":
            api_data = self._direct_api_call("get_active_alerts")
            kb_text += self.load_kb("alerts_explanation.md")
            
        elif intent == "get_device_status":
            api_data = self._direct_api_call("get_device_status")

        else:
            # Fallback / General intent - maybe load everything or minimal?
            # User might be asking "How is bill calculated?" without wanting current data
            # Check if query is purely educational
            if "how" in user_query.lower() and "bill" in user_query.lower():
                kb_text += self.load_kb("billing_calculation.md")
            elif "why" in user_query.lower():
                kb_text += self.load_kb("high_bill_reasons.md")
                # Also fetch summary to give context
                api_data = self._direct_api_call("get_monthly_summary")
        
        # 3. Final Answer (Call Ollama ONCE)
        prompt = self.build_prompt(user_query, intent, api_data, kb_text)
        
        payload = {
            "model": OLLAMA_MODEL,
            "prompt": prompt,
            "stream": False
        }
        
        start_time = time.time()
        try:
            # print("DEBUG: Sending to Ollama...")
            # Ensure TIMEOUT_LLM is imported or defined
            timeout_val = globals().get('TIMEOUT_LLM', 500) 
            r = requests.post(OLLAMA_API_URL, json=payload, timeout=timeout_val)
            r.raise_for_status()
            result = r.json()
            
            end_time = time.time()
            duration = round(end_time - start_time, 2)
            
            final_response = result.get("response", "Error: No response from model.")
            return f"{final_response}\n\n*(Generated in {duration}s)*"
            
        except Exception as e:
            return f"Error connecting to AI Model: {str(e)}. (Check if Ollama is running)"

# Simple CLI test
if __name__ == "__main__":
    import sys
    
    # Usage: python chat_core.py <api_key> <"quoted query">
    if len(sys.argv) > 2:
        key = sys.argv[1]
        query = sys.argv[2]
        bot = IoTChatbot(key)
        # Check if output has encoding issues in Windows console
        try:
            print(bot.chat(query))
        except UnicodeEncodeError:
            print(bot.chat(query).encode('utf-8').decode('cp1252', 'ignore'))
    else:
        # Interactive mode
        key = "TEST_DEVICE_ID"
        bot = IoTChatbot(key)
        print("--- IoT Digi Chatbot CLI (Type 'quit' to exit) ---")
        while True:
            try:
                q = input("You: ")
                if q.lower() in ["quit", "exit"]:
                    break
                ans = bot.chat(q)
                print(f"Bot: {ans}\n")
            except EOFError:
                break
