# Dự án: AI Digit Detection - Stream Web + ROI Processing

## 🎯 Tổng quan dự án

**Mục tiêu chính:** Xây dựng hệ thống nhận diện chữ số (0-9) từ camera ESP32-CAM, với giao diện web để cắt ROI (Region of Interest) và xử lý đa ROI.

### Kỹ thuật sử dụng:
- **Hardware:** ESP32-CAM (AI Thinker), 4MB PSRAM
- **Framework:** Arduino + PlatformIO
- **AI Model:** TensorFlow Lite Micro
  - Model: Digit Detection (32x20 RGB, 11 classes: 0-9 + no-detection)
- **Web:** HTML5 Canvas + JavaScript + WebServer (WiFi)

**Xoá bỏ:** Person Detection model (không dùng nữa)

---

## 📊 Current System Status

### ✅ Đã có sẵn
1. **Camera & WebServer**
   - Camera: 640x480 JPEG stream
   - WebServer: `/640x480.jpg` endpoint for live stream

2. **Configuration**
   - AI Thinker board (chân pin cố định)
   - WiFi settings in `app_camera_esp.h`

### ❌ Xoá bỏ
- Person Detection model & pipeline (không dùng)
- Task 1: person detection loop
- File: `person_detect_model_data.h/.cpp`, `detection_responder.cpp/.h`, `image_provider.cpp/.h`

### ⏳ Cần implement
1. **Digit Model Integration**
   - Convert `.tflite` → C data array
   - Load model on-demand (only when POST /detect)
   - Input: 32x20 RGB (float32)
   - Output: 11 classes (0-9 + no-detection)

2. **Digit Detection Endpoint**
   - POST `/detect` - accept multiple ROI coordinates
   - Process: crop 640x480 → resize 32x20 → inference → response JSON

3. **Web UI**
   - Display 640x480 live stream
   - Draw crop grid (aspect ratio 1.6:1)
   - Click to select ROIs
   - Send to ESP32 → display results

---

## 🔍 Model Analysis

### Digit Detection (Only model)

```cpp
Input:  [1, 32, 20, 3]           (RGB, float32, NO quantization)
Output: [1, 11]                  (0-9 digits + 10=no-detection)
Value:  float32, already softmax (probabilities 0.0-1.0)
Classes: 0,1,2,3,4,5,6,7,8,9,10
```

**Normalization needed:**
- Input value range: [-3.24, 2.92] (normalized)
- Formula: `(pixel - mean) / std` or simple `pixel / 255.0`

---

## 🏗️ Architecture Design

### Data Flow

```
┌──────────────────────────────────────────────────────────┐
│                    ESP32-CAM                             │
├──────────────────────────────────────────────────────────┤
│                                                          │
│  [Camera] 640x480 JPEG                                  │
│     ↓                                                    │
│  ├─→ [Task 1: AI] → 96x96 gray → Person model           │
│  │                  → Serial output (person_score)      │
│  │                                                       │
│  └─→ [Task 2: WebServer]                                │
│      ├─ GET /640x480.jpg → Stream JPEG                  │
│      └─ POST /detect → Process ROI → Digit model        │
│         Input: {crops: [{x,y,w,h}, ...]}               │
│         Output: {results: [{class, confidence}, ...]}   │
│                                                          │
└──────────────────────────────────────────────────────────┘
                          ↑↓ WiFi
                    ┌─────────────┐
                    │ Web Browser │
                    ├─────────────┤
                    │ - Canvas    │
                    │ - Draw ROIs │
                    │ - Send POST │
                    │ - Show result│
                    └─────────────┘
```

### File Structure (Proposed)

```
src/
├── main.cpp                         (Main + WebServer only)
├── app_camera_esp.c/.h              (Camera: 640x480)
├── WifiCam.cpp/.hpp                 (WebServer + /detect endpoint)
│
├── digit_model_data.h/.cpp          (Model: 32x20 RGB) ← NEW
├── digit_detection.h/.cpp           (Digit inference) ← NEW
│
└── [DELETE] person_detect_model_data.h/.cpp  (Person model - xoá)
    [DELETE] detection_responder.cpp/.h       (Person output - xoá)
    [DELETE] image_provider.cpp/.h            (Person capture - xoá)

web/                                 ← NEW
├── index.html                       (Web UI)
├── camera.js                        (Canvas + stream)
└── detection.js                     (ROI crop + POST)
```

---

## 📋 Implementation Phases

### Phase 1: Backend Setup (ESP32)
**Status:** ⏳ TODO
**Strategy:** Time-sharing models (load/unload per request)

Tasks:
- [ ] **1.1** Convert digit model `.tflite` → C array
  - Use Python: `xxd -i` or custom script
  - Create `digit_model_data.h/.cpp`
  
- [ ] **1.2** Create digit inference function
  - File: `digit_detection.h/.cpp`
  - Function: `TfLiteStatus DigitInference(uint8_t* image_32x20, int8_t* output)`
  - Input: 32x20x3 RGB buffer, ROI coordinates
  - Process: normalize RGB → inference → return class + confidence
  - **Does NOT load model** (caller handles load/unload)
  
- [ ] **1.3** Add `/detect` POST endpoint with model switching
  - Parse JSON: `{crops: [{x, y, w, h}, ...]}`
  - For each crop:
    - Pause person detection task (set flag)
    - Unload person model from interpreter
    - Load digit model
    - Capture frame → crop ROI → resize 32x20 → inference
    - Unload digit model
  - Return JSON: `{results: [{class, conf}, ...]}`
  - Resume person detection task
  - Update `WifiCam.cpp`

- [ ] **1.4** Add model management helpers
  - Function: `LoadModel(const uint8_t* model_data, size_t model_size)`
  - Function: `UnloadModel()`
  - Add task control flag: `volatile bool g_pause_person_detection`

### Phase 2: Frontend (Web UI)
**Status:** ⏳ TODO

Tasks:
- [ ] **2.1** Create `web/index.html`
  - Canvas to display 640x480 stream
  - Control panel: crop size, aspect ratio
  - ROI visualization

- [ ] **2.2** Implement stream loading (`web/camera.js`)
  - Fetch `/640x480.jpg` repeatedly (MJPEG)
  - Draw on canvas
  - Performance: 5-10 FPS target

- [ ] **2.3** ROI selection UI (`web/detection.js`)
  - Click & drag to draw ROI (aspect ratio 1.6:1)
  - Display multiple ROIs
  - Send to `/detect` endpoint
  - Display results on canvas (class + confidence)

### Phase 3: Integration & Testing
**Status:** ⏳ TODO

Tasks:
- [ ] **3.1** End-to-end testing
  - Single ROI detection
  - Multiple ROI batch detection
  - Latency measurement

- [ ] **3.2** Performance optimization
  - Inference time per ROI
  - Network latency
  - Memory usage

- [ ] **3.3** Deployment
  - Build & flash to ESP32-CAM
  - Test WiFi stability
  - Documentation

---

## 🔧 Technical Details

### Memory Budget (ESP32, 320KB RAM) - Digit Model Only

**Normal Operation (WebServer mode):**
```
- WiFi stack:              ~80KB
- FreeRTOS tasks:          ~50KB
- WebServer buffers:       ~20KB
- Buffer (640x480):        ~80KB (PSRAM)
- Free:                    ~90KB ✓ PLENTY
```

**ROI Detection (Digit model mode):**
```
- WiFi stack:              ~80KB
- Digit model tensor:      ~30KB (allocated)
- Buffer (32x20x3):        ~2KB (PSRAM)
- Free:                    ~208KB ✓ VERY PLENTY
```

**Strategy:** Load model on-demand, no simultaneous models needed

### Inference Time (Estimated)
```
- Person model (96x96):    ~100ms
- Digit model (32x20):     ~10-20ms per ROI
- WiFi POST/GET:          ~50-200ms (depends on network)
```

### ROI Processing
```
Input:  {x: 100, y: 50, w: 64, h: 40}
Steps:
1. Capture full 640x480 frame from camera
2. Extract ROI: frame[50:90, 100:164] → 40×64 buffer
3. Resize 40×64 → 32×20 (aspect ratio preserved ✓)
4. Normalize RGB: (pixel - mean) / std
5. Inference: 32×20×3 input → model → 11 outputs
6. Softmax argmax → class ID
7. Get confidence from output array
```

---

## 📡 API Specification

### GET /640x480.jpg
```
Request:  GET http://ESP32_IP/640x480.jpg
Response: JPEG image (streaming)
Purpose:  Live camera feed
```

### POST /detect
```
Request:
{
  "crops": [
    {"x": 100, "y": 50, "w": 64, "h": 40},
    {"x": 200, "y": 100, "w": 64, "h": 40}
  ]
}

Response:
{
  "results": [
    {"class": 3, "confidence": 0.95},
    {"class": 7, "confidence": 0.87}
  ],
  "processing_time_ms": 45
}

Error Response:
{
  "error": "Invalid ROI coordinates",
  "code": 400
}
```

---

## 🧪 Testing Checklist

- [ ] Model loads successfully on ESP32
- [ ] Person detection still works (regression test)
- [ ] Digit model inference works
- [ ] Single ROI detection accurate
- [ ] Multiple ROI batch detection working
- [ ] Web UI displays stream
- [ ] Web UI can draw and send ROIs
- [ ] Results display correctly on web
- [ ] Memory usage acceptable (<90% heap)
- [ ] WiFi connection stable
- [ ] Latency acceptable (<500ms per request)

---

## 📝 Notes & Considerations

1. **Image Normalization**
   - Model trained with specific normalization
   - Need to figure out mean/std (check model metadata)
   - Or use safe default: `pixel / 255.0`

2. **Aspect Ratio**
   - Model expects 32×20 (ratio 1.6:1)
   - When cropping 640×480 feed (ratio 1.33:1), need to:
     - Option A: Crop with black padding
     - Option B: Stretch (may degrade accuracy)
     - Option C: User selects 1.6:1 crops only

3. **Performance Trade-off**
   - More ROI crops = slower response
   - Consider: max 3-5 crops per request
   - Or: process async in background task

4. **Memory Optimization**
   - Both models loaded at startup
   - Share input/output buffers if possible
   - Use static allocation, no malloc during inference

5. **Future Enhancements**
   - Batch processing (feed multiple 32×20 crops to model at once)
   - Real-time ROI tracking
   - Confidence threshold filtering
   - Save detection history

---

## 📅 Timeline

| Phase | Subtask | Est. Time | Status |
|-------|---------|-----------|--------|
| 1 | 1.1 Model conversion | 30min | ⏳ |
| 1 | 1.2 Digit inference | 2h | ⏳ |
| 1 | 1.3 /detect endpoint | 1.5h | ⏳ |
| 1 | 1.4 Memory optimization | 1h | ⏳ |
| 2 | 2.1 HTML UI | 1h | ⏳ |
| 2 | 2.2 Stream loading | 1h | ⏳ |
| 2 | 2.3 ROI & detection | 1.5h | ⏳ |
| 3 | 3.1 Integration test | 1h | ⏳ |
| 3 | 3.2 Performance tune | 1h | ⏳ |
| 3 | 3.3 Deployment | 30min | ⏳ |
| | **Total** | **~11.5h** | |

---

## 🚀 Next Step

**Recommend:** Start Phase 1.1 - Convert digit model to C array

Command:
```bash
cd assets
python -c "
import numpy as np
with open('dig-class11_2000_s2_q.tflite', 'rb') as f:
    data = f.read()
with open('../src/digit_model_data.h', 'w') as f:
    f.write('#ifndef DIGIT_MODEL_DATA_H\n')
    f.write('#define DIGIT_MODEL_DATA_H\n\n')
    f.write('extern const unsigned char g_digit_model_data[];\n')
    f.write('extern const int g_digit_model_data_len;\n\n')
    f.write('#endif\n')
with open('../src/digit_model_data.cpp', 'w') as f:
    f.write('#include \"digit_model_data.h\"\n\n')
    f.write(f'const unsigned char g_digit_model_data[] = {{\\n')
    for i, b in enumerate(data):
        f.write(f'0x{b:02x}' + (', ' if i < len(data)-1 else ''))
        if (i+1) % 16 == 0:
            f.write('\\n')
    f.write(f'\\n}};\n')
    f.write(f'const int g_digit_model_data_len = {len(data)};\n')
"
```

Hoặc bạn muốn tôi implement ngay từ đầu?
