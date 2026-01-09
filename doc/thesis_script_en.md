# GRADUATION THESIS DEFENSE SCRIPT - ENGLISH

## SLIDE 1: INTRODUCTION & REASON FOR CHOOSING THE TOPIC

"Respected committee chairman, honorable teachers in the review committee, and all students and colleagues present at today's defense.

My name is **Nguyen Anh Duc**, student ID **21IT338**, class **21IR**, major in **IoT & Robotics** – Department of Computer Science & Electronics.

Today, I am very honored to stand here and present the results of my passionate research that I have worked on for a long time. My graduation thesis is titled: **'Developing a water consumption monitoring system based on IoT integrated with Edge AI technology for detecting unusual behavior'**.

This topic has been completed under the careful guidance of **Dr. Nguyen Vu Anh Quang**."

---

## SLIDE 2: REPORT STRUCTURE

"To make it easier for teachers to follow and evaluate, my presentation will be divided into 8 main parts as shown on the slide.

I will start with an **Overview of the problem** to make the importance clear. After that, I will go deeper into the **Theory section** and especially **Chapter 5 - Implementation Method**, where I will analyze the main algorithms in detail. Finally, I will present the specific **experimental results** and **future development directions**."

---

## SLIDE 3: ACKNOWLEDGMENT

"Before going into technical content, I would like to spend a few moments to express my deep gratitude.

I sincerely thank the **Board of Directors of Vietnam-Korea University of Information and Communication Technology (VKU)** for providing the best facilities for students to do scientific research.

Especially, I would like to send my thanks to professor **Nguyen Vu Anh Quang**. During the implementation, from early ideas about putting AI on low-memory chips to optimizing every byte of transmitted data, the professor always carefully guided and directed solutions for me.

I also thank the **open source community** and **my family** for always being a strong support."

---

## SLIDE 4: PROBLEM OVERVIEW

"Respected committee, let me explain the overview section. Why did I choose this topic?

We talk a lot about 'Smart Cities', but the reality is that water supply systems in Vietnam still operate in a very manual way.

The current process is: **Workers go to each house → Open the manhole cover → Write notes in a book → Enter data into computer**. This process has 3 serious problems that technology needs to solve:

1. **Huge operating costs:** It wastes labor and travel time.
2. **Human errors:** Misreading numbers, wrong writing, or data entry mistakes that lead to customer complaints.
3. **Lack of real-time data:** This is the most serious problem. People don't know they have a water leak (for example, a broken underground pipe or broken water tank float) until the end of the month when they get a huge bill.

On the market, there are smart water meters (Ultrasonic/Electromagnetic), but they are very expensive (over $100 each) and require cutting pipes to replace all old meters.

My **IoT Digi** solution takes a different approach: **Retrofit (Upgrading)**. We keep the old mechanical meter as it is and just add a low-cost 'smart eye' device to turn it into a smart meter. This is a balance between cost and technology."

---

## SLIDE 5: PROJECT GOALS

"From the situation above, I set out 3 main technical goals for this system:

**First, artificial intelligence at the edge (Edge Intelligence):** I firmly decided not to choose the solution of sending images to a server for processing. Sending video/images continuously will block network bandwidth and more importantly violate customer privacy. I put AI directly on the ESP32 microcontroller, sending only the final result (cubic meters of water).

**Second, self-operating system (Autonomous Operation):** The system must be a 'Set-and-Forget' device. It must automatically handle lighting in dark areas, automatically adjust when shaken, and automatically fix logic errors (like when the number is partly moving - NaN) without human intervention.

**Third, financial transparency & alert:** The system must convert raw data (cubic meters) into financial information (money) in real-time and detect leaks immediately to alert users."

---

## SLIDE 6: THEORY - IOT ARCHITECTURE

"From the theory side, the system is built strictly following the standard 4-layer IoT architecture:

1. **Sensing Layer (Perception):** Uses **OV2640 Camera** as an optical sensor. Instead of counting magnetic pulses (which are easy to interfere with), the camera records the actual image of the meter face, ensuring absolute accuracy like human eyes.

2. **Network Layer (Network):** Uses **Wi-Fi connection**. Although it uses more power than LoRaWAN, Wi-Fi allows sending large data when needed for debugging and takes advantage of existing home network infrastructure.

3. **Processing Layer (Processing):** I apply a **hybrid model**. Heavy processing (Vision AI) is at the edge, while business processing (Billing, Database) is at the Raspberry Pi server.

4. **Application Layer (Application):** Includes a **Web Dashboard** for users to monitor and a **Chatbot** for support."

---

## SLIDE 7: MONITORING PRINCIPLE

"My monitoring principle is **Vision-Based Retrofitting (Non-invasive)**.

Unlike flow sensors that require cutting the water pipe to install, my device is designed as a module that is mounted on the outside (Add-on).

The theoretical challenge here shifts from a 'hydraulics' problem to a 'pattern recognition' problem. The system must distinguish the numbers 0-9 on the mechanical wheel, recognize the rotating meter hand, and remove environmental noise like dust, water vapor, or scratches on the glass."

---

## SLIDE 8: AI & IMAGE PROCESSING - IMPORTANT

"Respected committee, this is the most important theory section: **How to fit AI into a cheap chip with only a few megabytes of memory?** I have applied 3 main techniques:

### 1. Region of Interest Extraction (ROI Extraction)

The camera takes pictures at 1600x1200 resolution, but I don't process the entire image. I use a **web server** to extract and cut out small number boxes (only 32x20 pixels). This helps remove **95% of extra data** (background, meter casing), greatly reducing CPU load.

### 2. Model Quantization (INT8)

Standard deep learning models use 32-bit real numbers (Float32), which are heavy and slow. I did 'Post-training Quantization', changing all the neural network weights to 8-bit integers.

- **Result:** Model size reduced **4 times**.
- **Inference speed:** Increased **3.8 times**.
- **Accuracy:** Only decreased from 99.0% to 98.2%, a completely acceptable trade-off for running on embedded devices.

### 3. TinyML

I used the **TensorFlow Lite for Microcontrollers** framework. This is a special simplified version that runs directly on hardware (Bare-metal) without needing a complex operating system, helping optimize every machine cycle."

---

## SLIDE 9: INTERFACE THEORY

"For the interface, besides standard dashboard design, I want to emphasize the **Generative AI** feature.

I integrated the **Llama 3.2** large language model running locally on the server. The reason is to reduce the thinking load for users. Instead of having to read charts and make guesses themselves, users can ask the **Chatbot** in natural Vietnamese/English. The Chatbot acts like a virtual assistant, automatically queries the SQL database, and answers questions like 'What time did I use the most water this month?', making the technology more user-friendly."

---

## SLIDE 10: METHODOLOGY - HARDWARE & POWER

"Respected committee, let me explain the specific implementation method on the edge device.

### First, about the hardware platform

I chose the **ESP32-CAM AI-Thinker** module. However, the biggest challenge of this chip is memory. The ESP32 microcontroller only has about **520KB internal SRAM memory**, which is too small to store a high-resolution image UXGA (1600x1200), let alone run an AI model.

- **Solution:** I had to enable and use **4MB external PSRAM (pseudo-static RAM)**. This is the key element to create a buffer to store images and computing space (Tensor Arena) for the TensorFlow Lite library to work.

### Second, about the image capture strategy

Taking a photo of a meter in a dark underground room is not just about turning on the flash and taking a picture. If the flash is on too long, heat will cause noise in the image sensor (thermal noise).

- **Technique:** I programmed the **flash LED to be controlled at the millisecond level**. The light only turns on just before the camera opens and turns off immediately after data is captured. This synchronization technique removes motion blur and ensures uniform brightness for the AI.

### Third, about power management

Since this is a battery-powered retrofit device, I can't leave the CPU running continuously. I set up a strict **Deep Sleep cycle**.

- **The operation process is:** Device wakes up → Camera starts → Takes photo & processes AI → Sends data → Then immediately cuts all power to external parts (including WiFi) to go back to sleep mode.
- **The entire process** takes only a few tens of seconds, helping maximize battery life."

---

## SLIDE 11: EDGE ALGORITHMS - MOST IMPORTANT

"Respected teachers, to turn a raw image into an accurate number, I built a **3-step processing pipeline** right on the ESP32 chip:

### Step 1: Image stabilization (Automatic Alignment)

In real installation, the camera on the water pipe will definitely shake or move over time. If I just cut the image at a fixed position (Hard-code), the AI will misread the location.

- **Solution:** I use a **feature matching algorithm**. The system compares the current image with a **reference image** saved during setup.
- It calculates a **transformation matrix** with 3 parameters: horizontal shift (Delta x), vertical shift (Delta y), and rotation angle (theta). Then the system automatically moves the cutting area (ROI) to correct this error. This ensures the AI always sees the number in the middle of the frame.

### Step 2: Hybrid AI model (CNN & Regression hybrid)

I don't use one model for everything. I split it into two specialized types:

1. **For digit rows (Digits):** I use a **quantized CNN network**. Especially, I add a **BatchNormalization layer** at the start to handle light variations (bright or dark). This model classifies 11 classes: digits 0-9 and a special **'NaN'** class.

2. **For meter hand (Analog):** This is the creative point of the project. Instead of classifying rotation angle (which causes large errors), I use a **Regression model** to predict the **sine and cosine** coordinates of the hand.
   - **Why sine/cosine?** Because at the transition point between 9.9 and 0.0, the value jumps, making training difficult. Switching to the sine/cosine space turns the problem into a continuous circle, completely removing errors at this dead point.

### Step 3: Post-processing logic (Anomaly Correction Logic)

The result from AI is not the final result. It must go through a **physical logic filter**:

- **Handling 'NaN' state:** When a number is partly moving (for example between 1 and 2), AI reports 'NaN'. Then the algorithm looks at the units digit. If the units digit > 5, it means the number is about to jump up → System automatically rounds up. Otherwise it rounds down.
- **Blocking fake flow:** If the new value is less than the old value (negative water) or flow speed exceeds the physical limit of the pipe (Max Rate), the system treats it as noise and immediately removes it."

---

## SLIDE 12: SYSTEM ARCHITECTURE & COMMUNICATION

"After processing at the edge, data is sent to the server. In this part, I want to emphasize the optimization in the communication protocol.

### First, Binary Webhook protocol (Binary Payload)

Most IoT systems today use JSON format (text) to send data. However, JSON uses a lot of space and the microcontroller must use CPU to process text strings.

- **My improvement:** I designed a **custom binary packet structure (Binary Struct)**. The packet starts with **4 magic bytes (0x44 0x49 0x47 0x49)** (which is 'DIGI'), followed by API Key and water value as a real number (Float).
- **Efficiency:** This method reduces packet size by **60%** compared to JSON. Smaller packets mean shorter WiFi transmission time, helping save battery power for the device.

### Second, Decoupled architecture

I design the system with clear task separation:

- **ESP32 (Edge):** Only acts as a 'smart sensor'. It doesn't know the water price - it just knows how to read cubic meters and send the data.
- **Raspberry Pi (Server):** Acts as the 'business brain'. The `webhook.php` script here receives raw data, then applies tiered pricing formulas (Tier 1, Tier 2...) and saves to the database.
- **Benefit:** This architecture lets me change water prices or change billing logic on the server without needing to upload new code (Flash Firmware) to hundreds of meters already installed in the field."

---

## SLIDE 13: EXPERIMENTAL RESULTS

"After 72 hours of continuous operation in a test environment (with a variable speed pump adjusting flow), I got these measurable results:

1. **AI accuracy:** Achieved **98.2%** on static numbers. For moving numbers (NaN), the logic algorithm successfully recovers **94%** of cases.

2. **Billing reliability:** I compared the bill calculated by the system with manual calculations in Excel. The error difference is less than **0.05%**, proving the tiered pricing algorithm works correctly.

3. **Performance:** Average processing time from wake-up to sleep is **40 seconds**.

4. **Battery life:** Based on the measured power consumption (0.07 mAh per cycle), a 2500mAh battery can theoretically last up to **3.7 years** (with 1 reading per day)."

---

## SLIDE 14: EVALUATION

"Based on the overall evaluation table, here are my honest observations:

**Biggest strength:** **Economic efficiency**. Total component cost (BoM) is under **$10**, 10-20 times cheaper than smart meter replacement solutions. At the same time it guarantees complete privacy for users.

**Limitations:** I also honestly recognize the weaknesses.

- **First,** Wi-Fi connection, although convenient, uses more battery than the industry standard LoRaWAN.
- **Second,** the flash glare effect (Specular Highlights) on the glass sometimes creates blind spots that lose image information.
- **Third,** the current AI model is 'overfitting' to Emic meter fonts and will need retraining if switching to other brands."

---

## SLIDE 15: CONCLUSION & FUTURE

"In conclusion, my project has proven the ability to 'breathe new life' into old mechanical devices with modern technology. I successfully built a complete process: **From image capture → Edge AI processing → Optimal data transmission → To user support with Chatbot**.

Next steps for commercialization:

1. **Integrate a circular polarizing filter (CPL)** to physically reduce glare.
2. **Switch to LoRaWAN technology** to increase coverage range to kilometers and save battery power 10 times.
3. **Develop a 'Universal Meter Reader'** model to read various types of meters on the market."

---

## SLIDE 16: CONCLUSION

"My presentation ends here.

I sincerely thank all the teachers and friends for taking time to listen to a long report with many technical details.

I very much hope to receive questions for review and professional suggestions from the committee so that I can look at the problem from many angles and improve the product even better. I sincerely thank everyone!"

---
