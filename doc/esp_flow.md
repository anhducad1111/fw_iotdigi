# ESP32-CAM Firmware Flow Control Diagrams

## 1. Main System Flow (FlowControl)

This diagram illustrates the high-level orchestration performed by `ClassFlowControl`. It iterates through the registered flow components (TakeImage, Alignment, Digit/Analog CNN, PostProcessing, Webhook) and executes them sequentially.

```mermaid
sequenceDiagram
    participant Main as MainLoop
    participant FC as ClassFlowControl
    participant Log as ClassLogFile
    participant Cycle as FlowCycle (Loop)

    Main->>FC: doFlow(time)
    activate FC

    FC->>Log: WriteHeapInfo("Start")

    loop For Each Component in FlowControl Vector
        Note over FC, Cycle: Iterate through registered steps

        FC->>Cycle: Get Component Name
        FC->>FC: TranslateAktstatus()
        FC->>Log: Log Status (e.g., "Take Image")

        FC->>Cycle: doFlow(time)
        activate Cycle

        alt Component Execution Successful
            Cycle-->>FC: return true
        else Execution Failed
            Cycle-->>FC: return false
            FC->>FC: Retry Logic (repeat++)

            alt Retries > 5
                FC->>Log: Log Error "Rebooting"
                FC->>FC: doReboot()
            end
        end
        deactivate Cycle

        FC->>Log: WriteHeapInfo("After Step")
    end

    FC->>Log: Log "Flow finished"
    FC-->>Main: return result
    deactivate FC
```

## 2. Image Capture Flow (TakeImage)

This diagram details the `ClassFlowTakeImage` process, including handling the flash, capturing the raw frame from the OV2640 sensor, and saving it to the SD card/PSRAM.

```mermaid
sequenceDiagram
    participant FC as ClassFlowControl
    participant TI as ClassFlowTakeImage
    participant Cam as ClassControlCamera
    participant Mem as PSRAM
    participant FS as SDCard

    FC->>TI: doFlow(time)
    activate TI

    TI->>Mem: psram_init_shared_memory()
    TI->>FS: CreateLogFolder(time)

    Note right of TI: Check WiFi power saving

    TI->>TI: takePictureWithFlash()
    activate TI
        TI->>Cam: SetQualityZoomSize()
        TI->>Cam: SetLEDIntensity()

        TI->>Cam: CaptureToBasisImage(rawImage)
        activate Cam
            Cam->>Cam: LED On
            Cam->>Cam: Wait (Flash Duration)
            Cam->>Cam: esp_camera_fb_get()
            Cam->>Cam: LED Off
            Cam-->>TI: Framebuffer
        deactivate Cam

        opt SaveAllFiles = True
            TI->>FS: Save "raw.jpg"
        end
    deactivate TI

    TI->>TI: LogImage()
    TI->>TI: RemoveOldLogs()

    TI->>Mem: psram_deinit_shared_memory()

    TI-->>FC: return true
    deactivate TI
```

## 3. Image Alignment Flow

The alignment step corrects camera shifts or vibrations by comparing the new image against a stored reference.

```mermaid
sequenceDiagram
    participant FC as ClassFlowControl
    participant Align as ClassFlowAlignment
    participant Algo as CAlignAndCutImage
    participant Img as CImageBasis

    FC->>Align: doFlow(time)
    activate Align

    Align->>Img: Check if Raw Image Valid

    Align->>Algo: Align(Reference, NewImage)
    activate Algo
        Algo->>Algo: Find Anchors in New Image
        Algo->>Algo: Calculate SAD (Sum of Absolute Differences)
        Algo->>Algo: Compute Delta X, Delta Y, Rotation
    deactivate Algo

    alt Alignment Successful
        Align->>Align: Store Shift Values
    else Alignment Failed
        Align->>FC: Log Error
    end

    align->>Algo: CutAndSave(ROIs)
    Note over Align, Algo: Updates ROI coordinates based on calculated shift

    Align-->>FC: return true
    deactivate Align
```

## 4. CNN Inference Flow (Digit & Analog)

This diagram shows how `ClassFlowCNNGeneral` processes the aligned Region of Interest (ROI) images using TensorFlow Lite for Microcontrollers.

```mermaid
sequenceDiagram
    participant FC as ClassFlowControl
    participant CNN as ClassFlowCNNGeneral
    participant TFL as CTfLiteClass
    participant ROI as CImageBasis

    FC->>CNN: doFlow(time)
    activate CNN

    CNN->>CNN: doAlignAndCut()

    CNN->>CNN: doNeuralNetwork()
    activate CNN
        loop For Each ROI
            CNN->>TFL: LoadInputImageBasis(ROI_Image)
            activate TFL
                TFL->>TFL: Resize (32x32)
                TFL->>TFL: Normalize (RGB -> Tensor)
            deactivate TFL

            CNN->>TFL: Invoke()
            activate TFL
                Note right of TFL: Run Inference (Quantized int8)
            deactivate TFL

            alt Type == Digit (Class11)
                CNN->>TFL: GetClassFromImageBasis()
                TFL-->>CNN: 0-9 or NaN (10)
            else Type == DoubleHybrid (Analog)
                CNN->>TFL: GetOutClassification()
                TFL-->>CNN: Class 0-9
                CNN->>TFL: GetOutputValue()
                TFL-->>CNN: Sub-pixel value (e.g. 4.6)
                CNN->>CNN: Calculate Result (Shift Logic)
            end

            CNN->>CNN: Store result in GENERAL[n]->ROI[i]
        end
    deactivate CNN

    CNN-->>FC: return true
    deactivate CNN
```

## 5. Post-Processing & Validation Flow

The final step where raw AI results are turned into billing-grade data, checking for consistency and physical impossibility.

```mermaid
sequenceDiagram
    participant FC as ClassFlowControl
    participant PP as ClassFlowPostProcessing
    participant Logic as StateMachine
    participant File as PreValue.ini

    FC->>PP: doFlow(time)
    activate PP

    loop For Each Measurement Number
        PP->>PP: Collect Raw Values (Digits & Analog)

        PP->>Logic: checkDigitConsistency()
        activate Logic
            Note right of Logic: Example: If lower digit is 9->0,<br/>increment higher digit
        deactivate Logic

        PP->>File: Read Previous Value (PreValue)

        PP->>PP: Calculate Flow Rate

        alt Flow < 0 (Negative Rate)
            PP->>PP: Error: "Negative Rate"
            PP->>PP: Reject Reading
        else Flow > MaxRate
            PP->>PP: Error: "Rate too high"
            PP->>PP: Reject Reading
        else Valid
            PP->>File: Update PreValue
            PP->>PP: Set Final Value
        end
    end

    PP-->>FC: return true
    deactivate PP
```
