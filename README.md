# IoT Digi - Intelligent AI-Powered Utility Meter Reader

## 📖 Project Overview

**IoT Digi** represents a cutting-edge leap in bridging the gap between legacy utility infrastructure and the modern Internet of Things (IoT). Designed as a comprehensive end-to-end solution, this project aims to digitize traditional mechanical utility meters (water, gas, electricity) solely through non-invasive visual recognition.

In an era where data is the new oil, manual meter reading is obsolete, prone to human error, and costly. **IoT Digi** solves this by retrofitting existing meters with a smart, AI-on-the-Edge camera module. Unlike reliable but expensive smart meters that require total replacement, **IoT Digi** creates a "Digital Twin" of the analog face, converting needle positions and rolling digits into precise digital telemetry.

---

## 🎯 Key Design Philosophy

The system is built upon three core pillars:

1.  **Edge Intelligence**
    By deploying lightweight Convolutional Neural Networks (CNNs) directly on the ESP32-CAM, we eliminate the need to transmit high-bandwidth video streams. Only the extracted data (the meter reading) is sent, ensuring ultra-low power consumption and minimal network footprint.

2.  **Autonomous Operation**
    The device manages its own alignment, lighting conditions, and error correction (e.g., handling "NaN" states during digit rollover) without human intervention.

3.  **Actionable Inspectability**
    The backend doesn't just store numbers; it acts as a billing engine. It detects flow anomalies and processes complex, tiered pricing models in real-time, providing immediate financial insights alongside consumption data.

---

## 🌟 Device Capabilities

### 🧠 Advanced AI Recognition System (The Core)

The device implements a sophisticated pipeline to convert visual meter data into digital flow readings _entirely on the edge_ (no cloud processing required for recognition).

#### Image Acquisition & Auto-Alignment

- **Capture**: The camera takes high-resolution images of the meter interface.
- **Alignment**: To counter vibrations or slight camera movements, the system uses Reference Images. It compares the current image with a stored reference to calculate rotation and position shifts, ensuring the Regions of Interest (ROIs) correspond exactly to the digits.

#### Hybrid Inference Engine (TFLite)

The system employs quantized TensorFlow Lite models individually optimized for different meter components:

- **Digital Counter Recognition (CNN)**

  - **Model**: `dig-cont_0712_s3_q.tflite`
  - **Function**: Classification of mechanical digits (0-9). It is trained to handle "NaN" states (when a digit is transitioning between two numbers) to avoid false readings during rollover.

- **Analog Dial Recognition**
  - **Model**: `ana-cont_1300_s2.tflite`
  - **Function**: Analyzing the angle of circular pointers to return values.

### 3. Precision Post-Processing

### ⚙️ Configuration & Management

- **Web Interface**: A user-friendly HTML5 interface hosted on the ESP32 for setting up ROIs, Wi-Fi, and viewing logs.
- **SD Card Storage**: All logs, images, and configuration files (`config.ini`) are stored locally.

---

## 📡 Communication Protocol

- **Binary Webhook**: Data is transmitted via a bandwidth-optimized custom binary protocol.
- **Header**: `JOMJ` (Magic Bytes) for packet identification.
- **Security**: API Key authentication for server handshake.

---

## 🚀 Server & Backend Capabilities

The backend system (`server/webhook.php`) acts as a comprehensive head-end system for data collection and billing.

### Webhook Handler

- **Binary Parsing**: Automatically parses the custom binary packets using the `JOMJ` header structure.
- **Validation**: Performs data integrity checks (Data length, Checksum) and authentication.
- **Localization**: Synchronizes all incoming timestamps to `Asia/Ho_Chi_Minh` (GMT+7) timezone.

### Database Integration (MySQL)

- Direct connection to `iotdigi_db`.
- **`readings` table**: Stores raw telemetry (Value, Timestamp, Error Codes).

### Automatic Billing & Analytics Engine

The server performs calculations immediately upon receiving data:

#### Daily Usage Aggregation

- Calculates total consumption for the current day.
- Tracks `start_value` (beginning of day) and `end_value` (current).

#### Monthly Billing (Tiered Pricing)

- Aggregates consumption by month.
- **Automatic Tiered Pricing Calculation**: Applies a 4-tier progressive pricing model:
  - **Tier 1 (0-10 units)**: 5,973 VND
  - **Tier 2 (11-20 units)**: 7,052 VND
  - **Tier 3 (21-30 units)**: 8,669 VND
  - **Tier 4 (>30 units)**: 15,929 VND
