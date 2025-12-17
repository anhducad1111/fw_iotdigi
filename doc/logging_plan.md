# Firmware Verification & Logging Plan (ESP32-CAM)

This document outlines the logging strategy required to technically prove that the `fw_iotdigi` system is optimized, stable, and production-ready for the ESP32-CAM constraints.

## 1. Memory & Resource Management (Evaluation of Leaks & Headroom)

**Objective**: Prove model fits in RAM and no long-term memory leaks occur.

**Required Logs**:

- **Boot State**: Baseline free memory.
- **Pre-Inference**: Memory before allocating heavy Tensor Arena.
- **Post-Inference**: Proof of memory release (or static stability).
- **Stack High-Water Mark**: Ensure AI task doesn't overflow stack.

**Log Format**:

```text
[MEM][BOOT] DRAM free: 138 KB | PSRAM free: 3120 KB | Frag: 85%
[MEM][PRE_AI] DRAM free: 92 KB | PSRAM free: 2980 KB
[MEM][POST_AI] DRAM free: 91 KB | PSRAM free: 2980 KB
[STACK][AI_TASK] High watermark: 4096 bytes used / 8192 allocated
```

**Verification Criteria**:

- [ ] DRAM does not decrease linearly over time (Leak check).
- [ ] PSRAM has >500KB buffer for OTA/Network bursts.

## 2. AI Inference Performance (Real-time Capabilities)

**Objective**: Demonstrate `fw_iotdigi` is suitable for Edge AI (Low Latency).

**Required Logs**:

- **Preprocessing**: Time to Resize/Crop/Normalize.
- **Inference**: Pure Interpreter time.
- **Total Pipeline**: End-to-end latency.

**Log Format**:

```text
[AI] Preprocess: 18 ms
[AI] Inference: 64 ms (Quantized INT8)
[AI] Postprocess: 5 ms
[AI] Total pipeline: 87 ms
```

**Benchmarking**:

- Compare **Int8** vs **Float32**.
- Compare **32x32 Input** vs **64x64 Input**.

## 3. Model Integrity & Version Control

**Objective**: Traceability and reproducibility of results.

**Required Logs**:

```text
[MODEL] File: /sdcard/dig-s3-quant.tflite
[MODEL] Size: 48.6 KB
[MODEL] Schema: INT8 (Quantized)
[MODEL] CRC32: 0x8F21A3C9
```

## 4. Camera & Image Pipeline Stability

**Objective**: Prove the system handles physical alignment effectively.

**Required Logs**:

```text
[CAM] Init: OV2640 | Res: 800x600 | Fmt: RGB565
[CAM] Capture: 32 ms
[ALIGN] Ref: /sdcard/ref.jpg | Shift: dx=+4, dy=-2, rot=0.3°
[ROI] Digit1: x=120, y=210, w=32, h=50
```

## 5. AI Decision Making & Anomaly Resolution

**Objective**: Show that the system is not "guessing" but using logic (NaN resolution, Odometer rules).

**Required Logs**:

```text
[AI_RAW] Sequence: [1, NaN, 2, 8]
[LOGIC] NaN Correction: Digit[1] is NaN, Digit[2]=2 (<5) -> Round Floor
[VALUE] Final Reading: 122.8
```

## 6. Physical Constraints (Domain-Awareness)

**Objective**: Prevent billing errors using physics-based rules.

**Required Logs**:

```text
[VALIDATE] PreValue: 122.8 | NewValue: 122.9 | dT: 600s
[VALIDATE] FlowRate: 0.006 m3/h (Limit: 2.0 m3/h) -> ACCEPT
```

- _OR (Failure Case)_:

```text
[REJECT] Reason: Negative Rate (New < Pre)
[REJECT] Reason: Flow Rate Exceeded (5.0 > 2.0)
```

## 7. Network & Power Efficiency

**Objective**: Demonstrate suitability for battery/solar operation (if applicable) or stable WiFi.

**Required Logs**:

```text
[NET] WiFi RSSI: -65 dBm | Channel: 6
[PWR] CPU Freq: 240MHz (Processing) -> 80MHz (Idle)
[NET] Upload: 1.2s (Packet Size: 256B)
```

## 8. Reliability & Recovery (Failsafe)

**Objective**: Prove self-healing capabilities.

**Required Logs**:

```text
[ERROR] Camera Capture Timeout (Attempt 1/3)
[WARN] Re-initializing Sensor...
[RECOVER] Camera success on Retry 2
[REBOOT] Reason: Watchdog Timer (WDT)
```

## 9. Tuning & Optimization Strategy

To achieve the performance metrics above, the Model Training must follow these strict guidelines:

1.  **Quantization**: Strict `INT8` (Post-training quantization).
    - _Why_: Reduces RAM usage by 4x, Speed up by ~3x on ESP32-S3/ESP32.
2.  **Input Size**: Max `32x32` pixels.
    - _Why_: Keeps Tensor Arena < 100KB.
3.  **Operations**:
    - Avoid complex layers (LSTMs, RNNs) - Stick to `Conv2D`, `MaxPool`, `FullyConnected`.
    - **Fuse Layers**: Ensure `Conv2D + ReLU` are fused in TFLite.
4.  **Memory Arena**: Use `Static Tensor Arena` allocated in PSRAM to save DRAM for WiFi stack.
