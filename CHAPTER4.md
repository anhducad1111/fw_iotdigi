# CHAPTER 4. WEB APPLICATION AND SERVER

## 4.1. Server Environment and Deployment

### 4.1.1. Operating system and server setup

The system is hosted on a **Raspberry Pi 4/5** running **Raspberry Pi OS (Bookworm 64-bit)**. This environment was selected for its robustness, low power consumption, and native support for GPIO and peripherals.

- **Web Server**: **Apache 2.4** is deployed as the HTTP server. It is configured with `mod_rewrite` enabled to handle clean URLs for the API and dashboard routing.
- **PHP Runtime**: **PHP 8.2** is installed to execute server-side scripts. Important extensions such as `mysqli` (for database interaction) and `curl` (for proxying requests) are enabled.
- **Supervision**: Systemd services are configured to ensure critical background processes (like the AI Chatbot bridge) automatically restart on boot or failure.

### 4.1.2. Network configuration

The server operates within a Local Area Network (LAN) but is designed to be accessible via a static IP address to ensure trusted connectivity for the ESP32 devices.

- **Static IP Assignment**: The Pi is assigned a fixed IP (e.g., `192.168.1.100`) to provide a reliable endpoint for the `POST /webhook.php` requests from the edge nodes.
- **Port Forwarding**: For external access (optional), port 80/443 is forwarded through the router, protected by basic authentication or a VPN, though primary operation is intended for the local intranet for security.
- **Firewall Rules**: `ufw` is configured to allow traffic only on ports 80 (HTTP), 443 (HTTPS), and 22 (SSH), blocking all other ingress traffic to prevent unauthorized access.

### 4.1.3. Service deployment strategy

The application follows a monolithic service architecture where the frontend, backend, and database reside on the same host for simplicity and speed.

- **Directory Structure**: The web root `/var/www/html/` contains the PHP application (`server/`), while sensitive configuration files (like `db_config.php`) are secured with restricted file permissions (`640`).
- **AI Service Bridge**: The Llama 3.2 model runs as a separate service (`ollama serve`) on localhost port 11434. A PHP wrapper script communicates with this service via internal cURL calls, isolating the heavy AI processing from the public-facing web server.

## 4.2. Backend API and Database

### 4.2.1. RESTful API design

The backend exposes a hybrid API consisting of standard REST endpoints for the frontend and a specialized binary endpoint for the IoT devices.

- **`GET /history.php?type=day&date=...`**: Returns JSON arrays of water usage readings. This endpoint powers the charts on the dashboard.
- **`GET /settings_api.php`**: Retrieves the current `config.ini` settings for a specific device.
- **`POST /settings_api.php`**: Accepts JSON payloads to update device parameters (e.g., `MaxRateValue`), which are then queued for the device to download.
- **`POST /webhook.php`**: The critical ingestion point. It does not use JSON but parses a custom **Binary Packet** containing `MagicBytes`, `Timestamp`, and `Value`. This design choice reduces packet size by ~60% compared to JSON.

### 4.2.2. Database schema and data management

Data is persisted in a **MariaDB (MySQL)** relational database `iotdigi_db`. The schema is normalized to handle high-frequency time-series data efficiently.

- **`users` Table**: Managed device registry and authentication. Links `api_key` (Device ID) to user accounts.
- **`readings` Table**: A high-volume table storing every single valid transmission. Indexed by `(device_id, timestamp)` to speed up time-range queries.
- **`daily_usage` Table**: An aggregation table. Instead of summing millions of rows on the fly, a background trigger/process updates this table daily with `start_value`, `end_value`, and `total_consumption`.
- **`monthly_usage` Table**: Stores the billing-critical data. This table is updated in real-time to calculate costs based on the tiered pricing model.

### 4.2.3. Data access and control logic

The backend implements business logic to ensure data integrity before it reaches the database.

- **Authentication Middleware**: Every API request is verified against the `users` table using the `HTTP_APIKEY` header.
- **Tiered Billing Engine**: The `update_monthly_usage()` function in `webhook.php` contains the logic for the 4-tier pricing model. It automatically distributes usage into buckets (0-10, 10-20, 20-30, >30) and calculates the cumulative `cost` in VND.
- **Concurrency Control**: Database transactions are used when updating billing tables to ensure that simultaneous reporting from multiple devices does not corrupt the aggregate totals.

## 4.3. Web User Interface and Visualization

### 4.3.1. Dashboard architecture

The frontend is built using a modern, lightweight stack: **HTML5, PHP, and TailwindCSS**.

- **Server-Side Rendering (SSR)**: PHP generates the initial HTML structure, ensuring fast First Contentful Paint (FCP).
- **Client-Side Hydration**: Vanilla JavaScript (`script.js`, `history.js`) fetches JSON data from the API to re-render charts and tables dynamically without reloading the page.
- **Responsive Design**: TailwindCSS utility classes ensure the dashboard adapts seamlessly between desktop monitors and mobile screens, a critical requirement for field technicians.

### 4.3.2. Data visualization components

- **Chart.js Integration**: The history page utilizes `Chart.js` to render interactive line graphs.
  - **Day View**: Shows hourly consumption flow ($m^3/h$).
  - **Month/Year View**: Shows aggregated bar charts of total daily/monthly usage.
- **Real-time Status Cards**: The `top.php` header includes dynamic status pills showing the device connection state (Offline/Online) and current estimated monthly bill.

### 4.3.3. AI Chatbot Integration UI

A distinct feature of the UI is the floating **AI Assistant Widget**.

- **Interface**: A chat bubble anchored to the bottom-right corner expands into a conversation window.
- **Context Injection**: When a user opens the chat, the Javascript frontend silently grabs the current page context (e.g., "Current Bill: 159,000 VND").
- **Interaction**: The user asks "Why is it so high?". This query + the context is sent to the backend. The UI displays a "Thinking..." animation while the Llama 3.2 model generates a personalized answer explaining the tiered pricing logic, providing a high-tech user experience.
