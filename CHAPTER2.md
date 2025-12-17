# CHAPTER 2. SYSTEM REQUIREMENTS

## 2.1. System Requirements

### 2.1.1. Functional requirements

The **IoT Digi** system is designed to automate the collection of utility meter data. To achieve this, the system must fulfill the following core functional requirements:

1.  **Automated Image Acquisition**: The system must autonomously capture high-resolution images of the analog meter face using an integrated camera module. This process must be scheduled at configurable intervals (e.g., hourly, daily) or triggered on-demand.
2.  **Edge AI Inference**: The device must process the captured image locally ("on the edge") to extract numerical usage data. It must be capable of recognizing:
    - Mechanical rolling digits (0-9).
    - Specific "NaN" states for digits in transition.
    - Analog dial positions for fractional readings (if applicable).
3.  **Data Transmission**: Upon successful reading, the system must package the telemetry (meter value, timestamp, device health status) into a binary payload and transmit it to a central server via Wi-Fi.
4.  **Billing & Analytics**: The central server must receive the raw data, validate it for consistency, and automatically calculate costs based on a tiered pricing model. It should provide user interfaces for viewing historical consumption and current billing estimates.
5.  **Offline Capability**: In the event of network failure, the device must store readings and logs locally on an SD card and attempt re-transmission when connectivity is restored.
6.  **Configuration Interface**: The system must provide a local web portal (SoftAP mode) allows users to configure Wi-Fi credentials, set Regions of Interest (ROIs) for the AI, and debug system performance.

### 2.1.2. Non-functional requirements

Non-functional requirements define the quality attributes of the system:

1.  **Accuracy**: The AI recognition algorithm must achieve a digit recognition accuracy of at least 95% under standard lighting conditions to ensure billing reliability.
2.  **Power Efficiency**: As a retrofit device potentially powered by batteries, the firmware must utilize deep sleep modes effectively. The device should remain in a low-power state for the majority of the time, waking only for the brief duration of acquisition and processing.
3.  **Performance & Latency**: The complete "Wake-to-Sleep" cycle (Capture -> Inference -> Transmission) should ideally complete within 10-15 seconds to minimize active power consumption.
4.  **Reliability**: The system must be robust against harsh environmental factors typical of utility closets, capable of auto-recovering from software crashes (via Watchdog Timers) or network disconnects.
5.  **Scalability**: The backend server must be capable of handling simultaneous connection requests from multiple metering units without data loss or significant latency.

### 2.1.3. Operational constraints

The system operates within strict physical and environmental boundaries:

1.  **Lighting Conditions**: Utility meters are often located in dark, unlit areas (basements, cabinets). The system must rely on its own artificial illumination (Flash LED) and cannot assume ambient light.
2.  **Physical Alignment**: The camera is mounted externally on existing meters. Vibrations from water flow or external bumps may shift the camera view. The software must mathematically compensate for these shifts (Alignment) rather than requiring physical rigid re-mounting.
3.  **Hardware Limitations**: The Edge recognition must run entirely within the constraints of the ESP32 architecture (limited RAM for image buffers, limited clock speed for CNN operations). The models must be quantized to fit within these limits.

## 2.2. Hardware and Software Requirements

### 2.2.1. ESP32-CAM and peripheral components

The edge node is built upon the **ESP32-CAM (AI-Thinker)** platform, selected for its balance of cost, connectivity, and performance.

- **Microcontroller**: ESP32-S SoC (System on Chip), dual-core 32-bit LX6 microprocessor, operating at 240 MHz.
- **Memory**:
  - **Internal SRAM**: 520 KB (insufficient for full frame buffering).
  - **External PSRAM**: 4 MB (Crucial for storing the 1600x1200 image buffers and tensor arenas for TFLite).
- **Camera Sensor**: **OV2640** Module. Capable of up to 2 Megapixel resolution. configured for UXGA (1600x1200) or SVGA (800x600) depending on ROI size requirements.
- **Storage**: MicroSD Card interface for "Black Box" logging (saving images, error logs, and configuration `ini` files).
- **Illumination**: On-board high-intensity white LED (Flash) to ensure consistent image exposure.

### 2.2.2. Raspberry Pi server platform

The central aggregation and chatbot server acts as the local gateway/fog node.

- **Unit**: Raspberry Pi 4 Model B (or Pi 5).
- **Processing Power**: Quad-core Cortex-A72 (ARM v8), sufficient for running the LAMP stack and the lightweight LLM (Llama 3.2).
- **RAM**: Minimum 4GB LPDDR4 recommended to support the database and concurrent AI Chatbot requests.
- **Storage**: 32GB+ High-speed microSD or SSD for database persistence and OS.

### 2.2.3. Software frameworks and development tools

- **Firmware Development**:
  - **Platform**: Visual Studio Code with **PlatformIO**.
  - **Framework**: **ESP-IDF** (Espressif IoT Development Framework) for low-level hardware control.
  - **AI Engine**: **TensorFlow Lite for Microcontrollers**. Models are trained in Python (TensorFlow/Keras) and converted to C++ byte arrays.
- **Backend Development**:
  - **Server Stack**: Apache Web Server, PHP 8.x for API logic.
  - **Database**: MySQL / MariaDB for structured data storage (`users`, `readings`, `billing`).
  - **AI Chatbot**: **Ollama** framework running **Llama 3.2** for natural language processing.

## 2.3. Overall System Architecture

### 2.3.1. ESP32-CAM and peripheral components (Edge Layer)

In the architectural topology, the ESP32-CAM serves as the **Perception and Edge processing Layer**. It is not merely a sensor but a smart node.

- **Role**: To abstract the physical world (analog visuals) into digital data. Ideally, it acts as a "black box" that outputs clean telemetry.
- **Interaction**: It operates usually as a Client. It initiates connections to the server to push data (POST requests). It does not maintain a persistent connection (like WebSocket) to save energy, instead using a "Store-and-Forward" or "Publish" architecture.

### 2.3.2. Raspberry Pi server platform (Fog/Server Layer)

The Raspberry Pi functions as the **Application and Data Layer**.

- **Role**: It is the central authority. It accepts "untrusted" data from edge nodes, validates it against business logic (Max Flow, Payment Status), and commits it to the database.
- **Services**: It hosts the Unified Interface (Web Dashboard) accessible to users via browser. It also acts as the computational host for the AI Assistant (Chatbot), bridging the gap between raw database statistics and natural language user queries.

### 2.3.3. Software frameworks and development tools (Integration Layer)

The software architecture follows a **Decoupled Model**.

- **Communication Interface**: The interaction between the C++ Firmware (ESP32) and the PHP Backend (Raspberry Pi) is defined strictly by the **Binary Webhook Protocol**. This ensures that changes in the server logic (e.g., changing the database from MySQL to PostgreSQL) do not require Over-The-Air (OTA) firmware updates, as long as the binary packet structure remains constant.

## 2.4. Data Flow and System Operation

### 2.4.1. ESP32-CAM and peripheral components (The Reading Flow)

The operational flow within the edge device is linear and deterministic:

1.  **Deep Sleep Wake-up**: Timer triggers system boot.
2.  **Sensor Init**: Camera warms up, generic settings loaded.
3.  **Acquisition Loop**: Flash ON -> Capture Frame -> Flash OFF to minimize thermal noise and power drain.
4.  **Processing Pipeline**:
    - _Alignment_: Image is shifted to match coordinates.
    - _Inference_: Digits are cropped and classified by TFLite.
    - _Validation_: Logic checks for negative flow or impossible jumps.
5.  **Uplink**: Validated integer is packed into `DIGI` binary struct and POSTed to the Server URL.
6.  **Teardown**: System enters Deep Sleep.

### 2.4.2. Raspberry Pi server platform (The Ingestion Flow)

The server handles the lifecycle of the data once it leaves the device:

1.  **Ingestion**: `webhook.php` receives the binary stream.
2.  **Authentication**: Checks API Key against `users` table.
3.  **Parsing**: Decodes the binary struct (Device ID, Reading, Battery Voltage).
4.  **Billing Logic Trigger**:
    - Calculates usage since last reading.
    - Updates `daily_usage` and `monthly_usage` tables.
    - Applies Tiered Rates (Tier 1-4) to update the realtime `cost`.
5.  **Response**: Sends a `200 OK` (or configuration updates) back to the ESP32.

### 2.4.3. Software frameworks and development tools (The User Flow)

From the user's perspective, the flow is analytical:

1.  **Access**: User logs into the Web Dashboard.
2.  **Visualization**: PHP scripts query the SQL database to render charts of "Daily Consumption" and "Cost Estimates."
3.  **Support**: If the user asks "Why is my bill high?", the **Chatbot Widget passes this context** to the Ollama/Llama 3.2 instance on the Pi. The LLM analyzes the SQL data (e.g., "Usage spiked on the 15th") and generates a natural language explanation, completing the information loop.
