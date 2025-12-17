# System Analysis Diagrams (ana1.md)

## 1. Image Acquisition Process (ClassFlowTakeImage)

This diagram details the critical steps for capturing high-quality input data, emphasizing synchronization and memory management on the resource-constrained ESP32.

```mermaid
sequenceDiagram
    participant FW as Firmware Core
    participant Sens as OV2640 Sensor
    participant LED as High-Intensity Flash
    participant RAM as Internal RAM
    participant PSRAM as External PSRAM (4MB)

    Note over FW, PSRAM: Initialization Phase
    FW->>Sens: Init(PIXFORMAT_RGB565, UXGA)
    FW->>RAM: Check Heap Integrity

    FW->>PSRAM: Allocate Image Buffer (~3.8MB)
    alt Allocation Failed
        PSRAM-->>FW: Error (Soft Reset)
    else Allocation Success
        FW->>FW: Proceed to Capture
    end

    Note over FW, LED: Capture Phase
    FW->>LED: Trigger ON (Pre-shutter)
    FW->>FW: Delay(FlashWarmupTime)

    FW->>Sens: Capture Frame()
    activate Sens
    Sens-->>PSRAM: DMA Transfer Raw Data
    deactivate Sens

    FW->>LED: Trigger OFF (Immediately)

    Note over FW, PSRAM: Validation
    FW->>PSRAM: Verify Frame Integrity
    FW->>FW: Pass to Alignment Module
```

## 2. ROI Lifecycle: Configuration & Alignment

This diagram illustrates the complete lifecycle of the Region of Interest (ROI) system, from the user manually defining coordinates on the Web UI to the firmware physically aligning the image in real-time.

```mermaid
sequenceDiagram
    participant User
    participant Browser as Web UI (edit_digits.html)
    participant ESP as ESP32 Firmware
    participant SD as SD Card (Storage)
    participant PSRAM as PSRAM (Buffer)

    Note over User, PSRAM: === PHASE 1: SETUP (Creation of Ground Truth) ===

    User->>Browser: Open ROI Editor
    Browser->>ESP: Request reference.jpg
    ESP-->>Browser: Send JPEG
    Browser->>ESP: Request config.ini
    ESP-->>Browser: Send ROI Parameters

    rect rgb(240, 248, 255)
        Note right of User: Interactive Config (JS)
        User->>Browser: Create Number Seq & ROIs
        Browser->>Browser: Logic: Sync Sizes, Aspect Ratio, Equidistance
        User->>Browser: Drag & Drop to align with digits
    end

    User->>Browser: Save Config
    Browser->>ESP: POST /edit_config
    ESP->>SD: Write updated config.ini

    Note over User, PSRAM: ... System Reboot / New Cycle ...

    Note over User, PSRAM: === PHASE 2: RUNTIME (Alignment & Correction) ===

    ESP->>SD: Read config.ini (Target Coordinates)
    ESP->>PSRAM: Capture New Image (Raw)

    activate ESP
        Note right of ESP: Alignment Logic (CAlignAndCutImage)
        ESP->>ESP: Find Anchors (Template Matching)
        ESP->>ESP: Calculate Shift (dx, dy, θ)

        rect rgb(255, 240, 240)
            Note right of ESP: Physical Transformation
            ESP->>PSRAM: Read Raw Pixels
            ESP->>ESP: Translate & Rotate (Bilinear)
            ESP->>PSRAM: Write Aligned Image Back
        end

        loop For Each ROI
            ESP->>PSRAM: Extract Crop (from Aligned Buffer)
            ESP->>ESP: Pass to CNN Inference
        end
    deactivate ESP
```
