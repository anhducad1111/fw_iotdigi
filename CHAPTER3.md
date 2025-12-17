# CHAPTER 3. SYSTEM ANALYSIS

## 3.1. ESP32-CAM Firmware and Edge Processing

### 3.1.1. Image capture and preprocessing pipeline

The firmware initiates the core image acquisition process via the **`ClassFlowTakeImage`** component. This process is critical because the quality of the input data directly determines the accuracy of the downstream AI.

- **Sensor Configuration**: The OV2640 is initialized in `PIXFORMAT_RGB565` or `GRAYSCALE` mode depending on the configuration. A resolution of **UXGA (1600x1200)** is typically chosen to ensure sufficient pixel density for each digit, although SVGA (800x600) may be used for faster inference.
- **Flash Synchronization**: To eliminate motion blur and sensor noise, the high-intensity LED is triggered _milliseconds_ before the shutter opens and extinguished immediately after capture.
- **Buffer Management**: Due to the ESP32's limited internal RAM (520KB), the raw image frame buffer is allocated in the external **PSRAM (4MB)**. The system verifies heap integrity before capture to prevent stack overflows affecting the FreeRTOS scheduler.

### 3.1.2. ROI configuration and automatic alignment

Once the image is in memory, the **`ClassFlowAlignment`** module executes the stabilization logic.

- **Reference Image**: During the initial setup (SoftAP mode), the user captures a "perfect" reference image. This serves as the ground truth.
- **Coarse & Fine Alignment**: The algorithm searches for high-contrast features (anchors) in the current image and correlates them with the reference. It computes a transformation matrix ($\Delta x, \Delta y, \theta$).
- **ROI Remapping**: The Regions of Interest (ROIs)—the specific $x,y,w,h$ coordinates for each digit—are defined relative to the specific meter face. The calculated offsets are applied to these coordinates mathematically. This is computationally cheaper than rotating the entire 2MB image buffer; instead, we only adjust the "crop window" for the subsequent steps.

### 3.1.3. Neural Network Models and Training Pipeline

The **`ClassFlowCNNGeneral`** component manages the neural network inference. The system uses two distinct model architectures, each meticulously optimized for its specific recognition task.

#### 3.1.3.1. Digit Classification Training (CNN)

The rolling digit counter is processed using a specialized Deep CNN trained on a dataset of ~2,000 images per class. The training script (`digit.py`) defines a rigorous pipeline to ensure robustness against the noisy environment of a utility meter box.

- **Input Specification**: Images are resized to **32 (Height) x 20 (Width) x 3 (RGB)**.
- **Detailed Network Topology**:
  1.  **Input Layer**: Accepts the normalized image tensor.
  2.  **Batch Normalization**: Applied _immediately_ after input. This critical step normalizes the pixel intensity distribution, making the model resilient to the extreme lighting variations (flash vs ambient) encountered in the field.
  3.  **Feature Extraction Block 1**: `Conv2D` (32 filters, 3x3, ReLU, padding='same') $\rightarrow$ `MaxPool2D` (2x2).
  4.  **Feature Extraction Block 2**: `Conv2D` (32 filters, 3x3, ReLU, padding='same') $\rightarrow$ `MaxPool2D` (2x2).
  5.  **Feature Extraction Block 3**: `Conv2D` (32 filters, 3x3, ReLU, padding='same') $\rightarrow$ `MaxPool2D` (2x2).
  6.  **Classifier Head**: `Flatten` $\rightarrow$ `Dense` (256 units, ReLU) $\rightarrow$ `Dense` (11 units, Softmax).
- **Hyperparameter Tuning**:
  - **Optimizer**: **Adadelta** is selected with a high learning rate ($\eta=1.0$) and decay rate $\rho=0.95$. This optimizer adapts learning rates per parameter, preventing the "dying ReLU" problem often seen in sparse digit images.
  - **Epochs**: The model is trained for **250 epochs** to ensure full convergence.
- **ESP32-Specific Quantization**:
  - The conversion to TFLite uses a representative dataset for **Full Integer Quantization**.
  - **Critical Flag**: `_experimental_disable_per_channel_quantization_for_dense_layers = True`. This specific setting is mandatory for the ESP32-S3's DSP instruction set; without it, the dense layer operations would fall back to unoptimized reference kernels, increasing inference time by 10x.

#### 3.1.3.2. Analog Pointer Training (Regression)

For circular dials, the problem is framed as **Cyclic Angular Regression**. A standard scalar regression (predicting 0.0-9.9) fails at the "Zero-Crossing" (e.g., the jump from 9.9 to 0.0 creates an infinite error gradient).

- **Input Processing**:
  - **Shape**: **32x32x3**.
  - **Resampling**: The pipeline uses **Mitchell-Netravali Cubic** interpolation (`mitchellcubic`) during resizing. This preserves the high-frequency edge details of thin needles better than standard bilinearity.
- **Dual-Vector Target**:
  The model predicts a 2D vector $(\sin \theta, \cos \theta)$ rather than a single angle $\theta$. The value is derived as:
  $$ \text{Target} = [\sin(2\pi \cdot \text{val}), \cos(2\pi \cdot \text{val})] $$
  This ensures mathematical continuity across the entire 360° rotation.
- **Custom Augmentation Pipeline**:
  The system employs domain-specific augmentations defined in `src/utils/augmentation.py`:
  1.  **Random White Balance**: Simulates color shifts from aging plastic covers.
  2.  **Random Inversion**: Trains the model to recognize both "Black needle on White dial" and "White needle on Black dial" simultaneously.
  3.  **Physical Jitter**: Width/Height shift ($\pm 1px$), Zoom ($\pm 5\%$), and Brightness ($\pm 20\%$).
- **Scalable Architecture**:
  The training script supports variable backbones (`s0`, `s1`, `s2`, `s3`). The default **`s0`** model is compact (optimized for speed), while `s3` offers deeper feature extraction for difficult, low-contrast dials.
- **Quantization Strategy**:
  Like the digit model, the analog model is compiled with `TFLITE_BUILTINS_INT8`, ensuring all trigonometric regression calculations are approximated using fast integer arithmetic on the microcontroller.

## 3.2. Anomaly Detection and Data Processing Logic

### 3.2.1. Digit sequence validation and correction

The raw output from the CNN is rarely perfect due to the physical nature of mechanical meters. The **`ClassFlowPostProcessing`** module implements a **"State-Transition Logic"** to correct common errors:

- **The "NaN" Problem**: When a mechanical digit moves from '1' to '2', it spends time in a "half-state." The CNN may classify this as `NaN` (Not a Number) or fluctuate between 1 and 2.
- **Logic Correction**: The system checks the _lower significance digits_. In a standard odometer, a digit only moves when the digit to its right completes a full revolution (0 thru 9).
  - _Rule_: If Digit $N$ is physically between $X$ and $X+1$, look at Digit $N-1$.
  - If Digit $N-1 > 5$, Digit $N$ is likely completing its move to $X+1$.
  - If Digit $N-1 < 5$, Digit $N$ is likely just starting its move and should be floor-rounded to $X$.

### 3.2.2. Rule-based anomaly detection

To prevent billing errors, the system enforces strict physical rules:

- **Max Rate Check**: A configuration parameter `MaxRateValue` (e.g., 2.0 $m^3/hr$) defines the physical limit of the pipe.
  - _Logic_: $FlowRate = (NewValue - PreValue) / (NewTimestamp - OldTimestamp)$.
  - If $FlowRate > MaxRateValue$, the reading is flagged as an anomaly (e.g., "Glitch" or "Optical Reflection") and discarded.
- **Negative Rate Protection**:
  - _Logic_: $NewValue < PreValue$.
  - Since water meters do not count backwards, any value lower than the previous valid reading is rejected. This protects against ROI misalignment where a '8' might be misclassified as a '3'.

### 3.2.3. Temporal consistency checking

This analysis ensures data integrity over time.

- **PreValue Persistency**: The last verified "Good Reading" (`PreValue`) is stored in non-volatile storage (SD Card `config/prevalue.ini`) and updated only after a reading passes all validation checks.
- **Imputation**: If a specific digit is consistently unreadable (e.g., obscured by a scratch on the glass) across multiple attempts, the system attempts to _impute_ its value based on the `PreValue` and the calculated expected flow, rather than returning a null result.

## 3.3. Communication and Data Transmission

The system employs a custom **Binary Webhook Protocol** to minimize overhead.

- **Structure**: Instead of JSON `{"value": 123.45}`, which requires parsing strings, the system sends a raw byte stream.
  - **Header**: `0x44 0x49 0x47 0x49` ("DIGI" - Magic Bytes).
  - **API Key**: Fixed-length (byte array) for device identification.
  - **Payload**: Float (4 bytes) for the meter reading.
- **Efficiency**: This reduces the transmission time needed for the ESP32 radio, directly saving battery life.
- **Security**: The server validates the packet via the API Key and Checksum before processing.

## 3.4. System Integration Design

The integration analysis focuses on how the components bind together into a cohesive product.

- **Coupling**: The system uses **Loose Coupling**. The ESP32 does not know _how_ the billing is calculated; it only knows how to read digits. The Server does not know _how_ the image was processed; it only trusts the incoming integer.
- **Feedback Loop**: The only feedback mechanism is the HTTP Response code.
  - `200 OK`: Data accepted.
  - `4xx/5xx`: Error (Device logs error to SD card).
- **Scalability**: By offloading the heavy visual recognition to the Edge (ESP32), the Server is freed from processing thousands of images. It only handles lightweight text-based SQL queries, allowing a single Raspberry Pi to support hundreds of meters.
