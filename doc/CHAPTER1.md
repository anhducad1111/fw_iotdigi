# CHAPTER 1. THEORETICAL FOUNDATIONS

## 1.1. IoT-Based Monitoring Systems

### 1.1.1. IoT architecture

The Internet of Things (IoT) architecture serves as the structural framework that defines how various components of an IoT system interact to sense, process, and transmit data. A standard IoT architecture is typically divided into three to four principal layers, depending on the complexity of the deployment:

1.  **Perception Layer (The Sensors)**: This is the physical layer consisting of sensors and actuators that interact directly with the environment. In the context of utility monitoring, this involves devices capable of capturing physical states—such as water flow, pressure, or, in the case of visual recognition systems, optical sensors (cameras). The primary goal of this layer is accurate data acquisition.
2.  **Network Layer (Connectivity)**: Once data is collected, it must be transmitted to a central processing unit. This layer creates the communication bridge, utilizing protocols such as Wi-Fi, LoRaWAN, Zigbee, or cellular networks (4G/5G). Essential considerations here include bandwidth availability, power consumption during transmission, and the reliability of the communication link.
3.  **Processing/Middleware Layer**: This layer handles data aggregation, filtering, and initial processing. In modern "Edge Computing" paradigms, significant processing happens closer to the source (at the Perception layer/Edge) to reduce latency and bandwidth usage before the data reaches the cloud.
4.  **Application Layer (The Interface)**: This is the user-facing component where processed data is presented. It includes dashboards, mobile applications, and billing systems that translate raw telemetry into actionable insights for end-users or administrators.

### 1.1.2. Water consumption monitoring principles

Water consumption monitoring has evolved from purely mechanical systems to sophisticated digital solutions. The theoretical principles governing these systems depend largely on the method of measurement:

- **Mechanical Displacement**: The traditional principle used in most residential meters (e.g., oscillating piston or nutating disc meters). Physical water flow moves a gear train, which advances a mechanical register or dial. These are accurate and require no power, but they lack connectivity.
- **Pulse Output**: A step up from purely mechanical systems, these meters generate an electrical pulse for every unit of water (e.g., 1 pulse per 10 liters) using a reed switch or Hall effect sensor. While digital, they are prone to "missed pulses" if the wire is damaged or the sensor drifts.
- **Ultrasonic/Electromagnetic**: These use the Time-of-Flight (ToF) principle or Faraday’s law of induction to measure flow velocity without moving parts. They are highly accurate but expensive and power-hungry.
- **Vision-Based Retrofitting (The IoT Digi Approach)**: This emerging principle treats the existing mechanical meter as a reliable "ground truth." Instead of replacing the infrastructure, a camera sensor digitizes the analog face. The theoretical challenge here transforms from _flow mechanics_ to _pattern recognition_—accurately interpreting the visual state of dials and rolling digits essentially converting a mechanical integrator into a digital data point.

### 1.1.3. Data acquisition and transmission in IoT systems

Data acquisition allows the conversion of analog environmental signals into digital values that a microcontroller can process.

- **Sampling Rate**: The frequency at which the sensor checks the environment. For water monitoring, an extremely high sampling rate isn't usually necessary unless leak detection requires identifying micro-flow signatures. A balance must be struck between temporal resolution and power conservation.
- **Signal Conditioning**: Raw signals often require filtering (to remove noise), amplification, or normalization before they are useful.
- **Transmission Protocols**:
  - **MQTT (Message Queuing Telemetry Transport)**: A lightweight, publish-subscribe protocol ideal for low-bandwidth environments. It decouples the device from the server, allowing for asynchronous communication.
  - **HTTP/HTTPS (Hypertext Transfer Protocol)**: A request-response protocol. While heavier than MQTT, it is ubiquitous and easily integrates with standard web servers (RESTful APIs). For devices transmitting complex data like images or large binary payloads (webhooks), HTTP is often preferred for its robust handling of larger packets.
  - **Binary Payloads**: To optimize transmission, efficient systems often encode data into custom binary structures rather than verbose formats like JSON or XML, significantly reducing the "airtime" of the radio and saving battery life.

## 1.2. Image Processing and Edge AI on ESP32-CAM

### 1.2.1. Fundamentals of digital image processing

Digital Image Processing (DIP) is the use of computer algorithms to perform image processing on digital images. Before any Artificial Intelligence (AI) can interpret an image, standard algorithms are often required to prepare the data:

- **Image Formation**: An image is essentially a 2D matrix of intensity values (pixels). In embedded systems, handling high-resolution color images (RGB) is memory-intensive.
- **Grayscaling**: Converting color images to grayscale reduces the data dimension by a factor of 3 (from 3 channels to 1), retaining luminance information which is usually sufficient for identifying shapes like numbers or hands on a dial.
- **Region of Interest (ROI) Extraction**: This technique involves cropping specific areas of an image that contain relevant information. By ignoring the background and focusing only on the digits/dials, the system drastically reduces the number of pixels to process, enabling faster computation on limited hardware.
- **Thresholding & Binarization**: Converting an image into pure black and white based on an intensity threshold. This helps in separating the "foreground" (the text/digits) from the "background," simplifying character recognition.
- **Geometric Transformations**: Operations like rotation, scaling, and affine transformations are crucial for **alignment**. If a camera is slightly tilted, the pixels must be mathematically remapped to a standard coordinate system before analysis can proceed.

### 1.2.2. Edge AI concepts and embedded deployment

Edge AI refers to the deployment of Artificial Intelligence algorithms locally on a hardware device, rather than relying on remote cloud servers.

- **Latency & Reliability**: By processing data on-device, the system eliminates network latency. The device can make decisions (e.g., "detecting a leak") even if the internet connection is down.
- **Privacy & Bandwidth**: Transmitting video streams violates user privacy and consumes massive bandwidth. Edge AI ensures that only the _metadata_ (the reading) leaves the device, not the image itself.
- **Model Quantization**: Standard Deep Learning models use 32-bit floating-point numbers, which are computationally expensive. **Quantization** converts these parameters into 8-bit integers (int8). This slightly reduces accuracy but significantly shrinks the model size and increases inference speed, making it feasible to run complex Neural Networks on microcontrollers with limited RAM (kilobytes/megabytes) rather than Gigabytes.
- **TinyML**: A field of machine learning focused on running models on ultra-low-power devices (milliwatts range). It involves tight optimization of convolution operations to fit within the constraints of embedded CPUs.

### 1.2.3. Image processing and Edge AI on ESP32-CAM

The ESP32-CAM is a popular, low-cost platform that combines a 32-bit microcontroller with a camera interface, but it presents unique theoretical challenges and opportunities:

- **Hardware Constraints**: With a clock speed around 240MHz and limited internal SRAM (typically 520KB + external PSRAM), the ESP32 cannot run standard desktop Computer Vision libraries like full OpenCV.
- **TensorFlow Lite for Microcontrollers (TFLite Micro)**: This is the standardized framework used to execute models on the ESP32. It provides an interpreter that runs the quantized models described above. The theoretical workflow involves:
  1.  **Training**: Creating a model on a powerful server using a dataset of meter images.
  2.  **Conversion**: Converting the model to a TFLite FlatBuffer.
  3.  **Inference**: The ESP32 loads this buffer and passes the captured image tensor through the layers to generate a probability distribution (Softmax) over the possible classes (0-9).
- **Hybrid Approaches**: Pure AI is often too heavy for everything. Effective systems on ESP32 use a hybrid theory: use classic image processing (lightweight math) for finding the digits (Alignment/ROI), and use Deep Learning (heavy math) only for the specific task of recognizing the character 0-9. This balances CPU load and accuracy.

## 1.3. User Interface and Alert Mechanisms

### 1.3.1. User interaction requirements for monitoring systems

A robust technical backend is useless without an effective Human-Machine Interface (HMI). Theoretical User Interaction (UI) design for monitoring systems focuses on:

- **Cognitive Load Reduction**: Users should not have to interpret raw data. The system must present "Information" (processed data), not just "Data." For example, instead of just showing "15403 liters," the interface should highlight "Daily Usage: 400 Liters (High)".
- **Accessibility & Responsiveness**: The interface must be platform-agnostic, accessible via web browsers on desktops, tablets, and mobile phones.
- **Control vs. Monitoring**: Users need the ability to _act_, not just watch. This includes configuration capabilities (setting billing cycles, threshold limits) directly through the interface.
- **Feedback Loops**: The system must confirm actions. If a user updates a setting, the UI must theoretically account for the "round-trip" time to the edge device and provide a loading state or confirmation message, ensuring the user knows the state of the physical device.

### 1.3.2. Data visualization and consumption analytics

Data visualization translates abstract telemetry into visual patterns that the human brain can process efficiently.

- **Temporal Aggregation**: Raw sensor data arrives as a time-series stream. Analytics requires aggregating this into meaningful buckets: Hourly, Daily, and Monthly views.
- **Trend Analysis**: Line charts and bar graphs are essential for showing trends. A theoretical requirement is the ability to compare "Current Period vs. Previous Period" (e.g., This Month vs. Last Month), allowing cost forecasting.
- **Billing Logic Integration**: In utility monitoring, data is inextricably linked to cost. The visualization layer must integrate pricing logic (tiered tariffs, block pricing) to show the _financial_ implication of the usage in real-time. This transforms a technical metric ($m^3$) into a value metric ($ Currency).
- **Anomaly Highlighting**: Visual cues (color coding: Green for normal, Red for excessive) allow for "at-a-glance" status assessments.

### 1.3.3. Alert and notification principles for anomaly detection

The value of an IoT monitoring system is largely defined by its proactiveness.

- **Threshold-Based Detection**: The simplest theoretical model. If $Value > Limit$, trigger alert. This is useful for "Max Flow" or "Monthly Budget Exceeded" alerts.
- **Behavioural Anomaly Detection**: More advanced systems look for patterns that deviate from the norm, even if they don't cross a hard threshold. For example, continuous water flow for 4 hours at 3 AM might indicate a leak ("Continuous Flow Alert"), whereas high usage at 6 PM is normal.
- **Push vs. Pull Notification Models**:
  - **Pull**: The user opens the app to check status. This is passive and relies on user discipline.
  - **Push**: The server initiates the communication (Email, SMS, App Notification) immediately upon event detection. This is critical for emergency events like leaks.
- **Alert Fatigue**: A theoretical risk in system design. If a system monitors too sensitively and sends too many minor alerts, the user becomes desensitized and ignores critical warnings. Effective alerting principles dictate that notifications should be actionable, infrequent, and high-value.
