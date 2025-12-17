# User Interface & Experience (UI/UX) Flows

## 1. User Journey Overview

This flowchart illustrates the typical user interaction path, from logging in to analyzing data and seeking AI support.

```mermaid
graph TD
    Start([User Access]) --> Login{Authenticated?}
    Login -- No --> PageLogin[login.php]
    PageLogin --> Login
    Login -- Yes --> Dash[Dashboard/index.php]

    Dash --> ViewStats[View Statistics]
    Dash --> ViewBill[Check Billing]

    ViewStats --> Charts[Render Charts (Chart.js)]
    ViewBill --> Payment[Payment Gateway]

    subgraph "AI Assistant Loop"
        Dash --> AskAI[Open Chat Widget]
        AskAI --> Query[Ask: 'Why is bill high?']
        Query --> AI_Proc[AI Analysis]
        AI_Proc --> Explain[Receive Explanation]
    end

    Explain --> Dash
```

## 2. Data Visualization Sequence

This diagram details how the system renders the "Daily Consumption" and "Cost Estimates" charts.

```mermaid
sequenceDiagram
    participant User
    participant Browser
    participant API as fetch_data.php
    participant DB as MySQL Database

    User->>Browser: Select "Month View"
    Browser->>API: GET /fetch_data.php?type=month&year=2024
    activate API

    API->>DB: SELECT * FROM daily_usage WHERE ...
    activate DB
    DB-->>API: Return Rows (Date, Consumption)
    deactivate DB

    API-->>Browser: JSON Payload
    deactivate API

    Browser->>Browser: Parse JSON
    Browser->>Browser: Update Chart.js Instance
    Browser-->>User: Display Bar Chart
```

## 3. AI Chatbot Support Workflow

This sequence demonstrates the "Context-Aware" support flow where the Chatbot uses real billing data to answer user questions.

```mermaid
sequenceDiagram
    participant User
    participant UI as Chat Widget
    participant PHP as chatbot/api.php
    participant Py as chat_core.py
    participant LLM as Ollama (Llama 3.2)
    participant DB as MySQL

    User->>UI: "Why is my bill so high?"

    Note right of UI: Javascript captures current<br/>dashboard context (Usage, Cost)

    UI->>PHP: POST {query, context_data}
    activate PHP

    PHP->>Py: Exec(python3 chat_core.py)
    activate Py

        Py->>DB: Fetch detailed billing history (RAG)
        DB-->>Py: Billing Rows

        Py->>Py: Construct Prompt with Data

        Py->>LLM: Generate Response
        activate LLM
        LLM-->>Py: "Your usage spiked on the 15th..."
        deactivate LLM

        Py-->>PHP: JSON Response
    deactivate Py

    PHP-->>UI: Return Answer
    deactivate PHP

    UI-->>User: Display AI Explanation
```
