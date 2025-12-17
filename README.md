# IoT Digi - Intelligent AI-Powered Utility Meter Reader

## 📖 Project Overview

**IoT Digi** represents a cutting-edge leap in bridging the gap between legacy utility infrastructure and the modern Internet of Things (IoT). Designed as a comprehensive end-to-end solution, this project aims to digitize traditional mechanical utility meters (water, gas, electricity) solely through non-invasive visual recognition.

In an era where data is the new oil, manual meter reading is obsolete, prone to human error, and costly. **IoT Digi** solves this by retrofitting existing meters with a smart, AI-on-the-Edge camera module. Unlike reliable but expensive smart meters that require total replacement, **IoT Digi** creates a "Digital Twin" of the analog face, converting needle positions and rolling digits into precise digital telemetry.

---

## 🎯 Key Design Philosophy

The system is built upon three core pillars:

1.  **Edge Intelligence**
    By deploying lightweight Convolutional Neural Networks (CNNs) directly on the **ESP32-CAM (240MHz)**, we eliminate the need to transmit high-bandwidth video streams. Only the extracted data (the meter reading) is sent, ensuring ultra-low power consumption and minimal network footprint.

2.  **Autonomous Operation**
    The device manages its own alignment, lighting conditions, and error correction (e.g., handling "NaN" states during digit rollover) without human intervention.

3.  **Actionable Inspectability & Financial Insight**
    The backend doesn't just store numbers; it acts as a billing engine. It detects flow anomalies and processes complex, tiered pricing models in real-time, providing immediate financial insights alongside consumption data.

---

## 🌟 Device Capabilities & Firmware Specifications

### 🛠 Hardware & Firmware Architecture

The firmware is engineered for high-performance edge computing using the **Espressif IoT Development Framework (ESP-IDF)** via PlatformIO, ensuring maximum control over hardware resources.

- **Core Hardware**: ESP32-CAM (AI-Thinker) with external PSRAM.
- **Clock Speed**: Optimized at **240MHz** to handle intensive TensorFlow Lite inference.
- **Operating System**: FreeRTOS with custom task scheduling for Camera, AI Inference, and Network stacks.
- **Power Management**: `CONFIG_PM_ENABLE` implemented with Dynamic Frequency Scaling (DFS) to minimize power usage during idle states.
- **Connectivity**:
  - **Station Mode**: Connects to WiFi for data uplink.
  - **SoftAP Mode**: Broadcasts a configuration hotspot for initial setup (WiFi creds, ROI alignment).

### 🧠 Advanced AI Recognition System (The Core)

The device implements a highly sophisticated **multi-stage pipeline** to convert visual analog meter data into precise digital telemetry. This process runs **entirely on the edge** (ESP32-CAM) in a deterministic, real-time sequence.

#### 🏗️ Phase 1: Image Acquisition & Preprocessing

The system interfaces directly with the **OV2640 Camera Sensor** to acquire raw visual data.

- **Adaptive Flash Control**: To handle varying lighting conditions (e.g., dark utility shafts), the system triggers a high-intensity LED flash synchronized with the shutter.
- **Buffer Allocation**: The raw image is captured into PSRAM.
- **Format Conversion**: The image is processed from raw RGB/Bayer format into a optimized color space suitable for Computer Vision tasks, preparing it for the alignment engine.

#### 🎯 Phase 2: Robust Auto-Alignment (Vibration Correction)

Mechanical meters are subject to physical vibrations and thermal expansion, which can shift the camera's field of view. To ensure the neural network looks at the _exact_ same coordinate every time, the system performs a **Template Matching** alignment:

- **Reference Anchoring**: The system stores a "Golden Master" reference image during initial setup.
- **Shift Calculation**: For every new frame, the algorithms calculate the precise **Delta X ($\Delta x$)**, **Delta Y ($\Delta y$)**, and **Rotation Angle ($\theta$)** difference between the current frame and the master reference.
- **Dynamic ROI Stabilization**: These offsets are mathematically applied to the **Regions of Interest (ROIs)**. If the camera vibrates 5 pixels to the right, the scanning windows for the digits effectively "move" 5 pixels to the right to compensate, locking onto the numbers with sub-pixel precision.

#### 🤖 Phase 3: Hybrid Inference Engine (Quantized TFLite)

The aligned image segments are fed into two parallel **Convolutional Neural Networks (CNNs)** running on the **TensorFlow Lite Micro** interpreter.

**A. Digital Rolling Counter (CNN Model)**

- **Class Architecture**: The model (`dig-cont_0712_s3_q.tflite`) classifies image patches into 11 discrete classes: `0`-`9` and `NaN`.
- **The "NaN" State (Rolling Handling)**: A critical innovation is the handling of "half-turned" digits. When a mechanical wheel is transitioning (e.g., showing half of '6' and half of '7'), standard AI models often hallucinate a wrong number. **IoT Digi** correctly identifies this ambiguous state as `NaN` (Not a Number), flagging it for logic-based resolution in the next phase.

**B. Analog Dial Vector Analysis**

- **Vector Regression**: The analog model (`ana-cont_1300_s2.tflite`) does not just classify numbers; it predicts the **vector angle (0-360°)** of the needle.
- **Heuristic Cross-Evaluation**: The system implements a "Carry-Over Logic" similar to how humans read clocks.
  - _Example_: If the "x1" dial is pointing at `9.8` (almost 0), and the "x10" dial is ambiguously between `4` and `5`, the system knows the "x10" dial _must_ still be `4` until the "x1" dial crosses zero.
  - **Logic**: `If Lower_Dial > 9.0 AND Current_Dial is Transitioning -> Round Down`.

#### 🛡️ Phase 4: Smart Post-Processing & Logic Gatekeepers

The raw AI output is subjected to a rigorous "Physics & Consistency" filter before being accepted as a valid reading.

- **1. Zero-Trust Consistency Check (`PreValue` Analysis)**

  - The system compares the new reading against the last known valid reading (`PreValue`).
  - **Negative Rate Protection**: Since utility meters cannot run backwards, any reading _lower_ than the `PreValue` is immediately rejected as a recognition error.
  - **MaxFlow Throttling**: The system calculates the implied flow rate (`ΔValue / ΔTime`). If the calculated flow exceeds the physical pipe's maximum capacity (e.g., >10m³/hour for a residential pipe), the reading is discarded as a "glitch."

- **2. "NaN" Resolution Engine**

  - If a digit is flagged as `NaN` by the AI (Phase 3), the system imputes the missing digit based on the `PreValue`.
  - _Example_: If previous was `159` and current is `1NaN0`, and the flow is positive, the system logically infers the `NaN` must be `6` (transitioning `5`->`6`), resolving the reading to `160`.

- **3. Decimal Shifting**
  - Final normalization of the integer value based on the meter's specific multiplier (e.g., `x0.001` for water meters), converting the raw integer `00123` into the floating-point billing unit `0.123 m³`.

### ⚙️ Configuration & Management

- **Web Interface**: A user-friendly HTML5 interface hosted on the ESP32 for setting up ROIs, Wi-Fi, and viewing logs.
- **SD Card Storage**: All logs, images, and configuration files (`config.ini`) are stored locally.

---

## 📡 Communication Protocol

- **Binary Webhook**: Data is transmitted via a bandwidth-optimized custom binary protocol to reduce packet size.
- **Header**: `DIGI` (Magic Bytes) for packet identification.
- **Security**: API Key authentication for server handshake.

---

## 🚀 Server & Backend Capabilities

The backend system (`server/`) is a full-stack solution responsible for data ingestion, billing, and AI customer support.

### 1. Webhook Handler & Data Ingestion

- **Binary Parsing**: Automatically parses the custom binary packets using the `DIGI` header structure.
- **Validation**: Performs data integrity checks (Data length, Checksum) and authentication using the `users` table via API Key.
- **Localization**: Synchronizes all incoming timestamps to `Asia/Ho_Chi_Minh` (GMT+7).

### 2. Database Architecture (MySQL)

The system uses a robust schema (`server/db/database.sql`) to track usage and billing:

- **`users`**: Manages authentication and links `api_key` to specific physical devices.
- **`readings`**: Stores raw telemetry (Value, Timestamp, Error Codes).
- **`daily_usage`**: Aggregates consumption per day (`start_value` to `end_value`).
- **`monthly_usage`**: The core billing table. Tracks:
  - Total monthly consumption.
  - **Real-time Cost Calculation**: Stores the final calculated cost in VND.
  - **Tier tracking**: Stores exact usage per pricing tier (Tier 1-4).
  - **Payment State**: Tracks `payment_status` ('paid'/'unpaid') and `paid_at` timestamps.

### 3. Automatic Billing Engine

The server performs financial calculations immediately upon receiving data:

- **Automatic Tiered Pricing**: Applies a 4-tier progressive pricing model defined in the `rate_tiers` table:
  - **Tier 1 (0-10 units)**: 5,973 VND
  - **Tier 2 (11-20 units)**: 7,052 VND
  - **Tier 3 (21-30 units)**: 8,669 VND
  - **Tier 4 (>30 units)**: 15,929 VND
- **Invoice Generation**: Automatically updates the current month's invoice with every new reading.

---

## 🤖 AI Chatbot Assistant (Raspberry Pi / Edge Server)

To provide intelligent user support, the system includes a Local LLM Chatbot integrated directly into the helper server (Raspberry Pi 4/5).

### Architecture

- **Core Model**: **Ollama** running **Llama 3.2** (Optimized for edge performance).
- **Interface**: PHP Web Widget (`server/chatbot_widget.php`) communicates with Python backend context.
- **RAG (Retrieval-Augmented Generation)**: The system feeds specific device context (Current Bill, Usage Stats) to the LLM, allowing it to answer personalized questions like:
  > _"Why is my bill so high this month?"_ > _"How much water did I use last week?"_

This hybrid approach keeps sensitive data local while providing high-level AI interaction.
