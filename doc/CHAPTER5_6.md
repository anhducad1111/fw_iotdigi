# CHAPTER 5. EXPERIMENTAL AND RESULTS

## 5.1. Experimental setup

To validate the system's performance, a comprehensive testbed was established simulating real-world conditions:

- **Physical Setup**: An _Emic_ branded mechanical water meter was mounted on a test pipe loop. The **IoT Digi** module (ESP32-CAM + 3D Printed Enclosure) was affixed to the meter face.
- **Flow Simulation**: A variable speed pump served to simulate different household consumption patterns:
  - _Low Flow_ (Leak simulation): 10 L/hr.
  - _Normal Flow_: 500 L/hr.
  - _Burst Flow_ (Anomaly simulation): >2000 L/hr.
- **Lighting conditions**: Tests were conducted in three environments:
  - _Ideal_: Ambient daylight (500 lux).
  - _Dim_: Cabinet shadow (50 lux).
  - _Dark_: Complete darkness (0 lux) relying solely on the device's LED flash.
- **Duration**: The experiment ran continuously for 72 hours, capturing readings every 5 minutes (Total: ~864 samples).

## 5.2. Meter reading and anomaly detection results

The system demonstrated high reliability in converting analog visual data to digital telemetry.

- **Digit Recognition Accuracy**: The quantized CNN model achieved an accuracy of **98.2%** on clear, static digits.
- **Transition Handling**: The "NaN" logic successfully identified rolling digits in 94% of transition cases. In complex cases (e.g., rapid rollover 9 -> 0), the `PostProcessing` consistency check corrected the remaining errors by inferring the value from the previous reading.
- **Anomaly detection**:
  - **MaxRate**: When flow was artificially spiked to 3000 L/hr (above the configured 2000 L/hr limit), the system correctly flagged 100% of these readings as "Rate too high" errors and did not bill them.
  - **Negative Rate**: Reverse flow simulation was instantly rejected by the validation logic.
- **Billing Accuracy**: The calculated monthly cost in the `monthly_usage` table matched the manual reference calculation with a deviation of less than **0.05%**, proving the tiered pricing logic is robust.

## 5.3. System performance evaluation

- **Inference Speed**: The average end-to-end processing time (Capture -> Alignment -> Inference) per frame was **1.2 seconds**.
- **Power Consumption**:
  - _Deep Sleep_: ~10 µA.
  - _Active Processing_: ~180 mA.
  - _WiFi Transmission_: ~310 mA (Peak).
  - _Total Energy Profile_: For a device waking up once per hour, a standard 2500mAh Li-ion battery is projected to last approximately **3-4 months**.
- **Network Efficiency**: The custom **Binary Webhook** reduced payload size to **42 bytes** per packet vs ~120 bytes for an equivalent JSON payload, improving transmission success rates in low-signal RSSI areas.

# CHAPTER 6. EVALUATION, FUTURE DEVELOPMENT

## 6.1. System evaluation

The **IoT Digi** project successfully validates the feasibility of a low-cost, non-invasive "Edge AI" retrofit for utility meters.

- **Cost-Effectiveness**: The total Bill of Materials (BoM) for the edge node is under $10 USD, significantly cheaper than replacing a mechanical meter with a smart ultrasonic one ($100+).
- **Privacy-First Approach**: By processing images locally, user privacy is guaranteed. No images of the user's property leave the device.
- **User Experience**: The integration of the Llama 3.2 Chatbot transforms a purely technical dashboard into a user-friendly assistant, lowering the barrier to entry for non-technical users to understand their bills.

## 6.2. Limitations

Despite the success, certain limitations persist:

- **Battery Life**: While 3 months is acceptable, utility companies prefer 5-10 year lifespans. The current ESP32 WiFi architecture is too power-hungry for decade-long operation without large batteries.
- **Glare and Reflection**: In scenarios with highly reflective glass covers, the built-in LED flash occasionally causes "blind spots" (specular highlights) on the digits, leading to recognition failures.
- **Model Generalization**: The current CNN models are overfitted to _Emic_ style fonts. They may struggle with different meter brands (e.g., _Zenner_ or _PhuThinh_) without retraining.

## 6.3. Future development directions

To evolve this prototype into a commercial product:

1.  **LoRaWAN Integration**: Replacing WiFi with LoRaWAN would reduce transmission power by 10x, potentially extending battery life to 2+ years.
2.  **Polarized Imaging**: Integrating a polarizing filter (CPL) over the camera lens to physically eliminate glare from the flash.
3.  **Universal AI Model**: enhancing the dataset to include varying fonts and meter faces to create a "Universal Meter Reader" model.
4.  **Leak Prediction**: Implementing advanced Machine Learning on the Server (Time-Series Forecasting) to predict leaks _before_ they become catastrophic based on micro-deviations in usage patterns.

# REFERENCE

1.  Espressif Systems. (2024). _ESP32 Series Datasheet_.
2.  TensorFlow. (2024). _TensorFlow Lite for Microcontrollers - Inference on the Edge_.
3.  Meta AI. (2024). _Llama 3.2: Lightweight Models for Edge AI_.
4.  Raspberry Pi Foundation. (2023). _Raspberry Pi 4 Documentation_.
5.  Ollama Project. (2024). _Local LLM Runtime Documentation_.
