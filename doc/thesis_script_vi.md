# KỊCH BẢN BẢO VỆ ĐỒ ÁN TỐT NGHIỆP - TIẾNG VIỆT

## SLIDE 1: GIỚI THIỆU & LÝ DO CHỌN ĐỀ TÀI

"Kính thưa Chủ tịch Hội đồng, quý Thầy Cô trong Hội đồng phản biện, cùng toàn thể các bạn sinh viên và đồng nghiệp có mặt trong buổi bảo vệ ngày hôm nay.

Em tên là **Nguyễn Anh Đức**, mã sinh viên **21IT338**, thuộc lớp **21IR**, chuyên ngành **IoT & Robotics** – Khoa Kỹ thuật Máy tính & Điện tử.

Hôm nay, em rất vinh dự được đứng đây để trình bày kết quả nghiên cứu tâm huyết của mình trong suốt thời gian qua. Đề tài tốt nghiệp của em mang tên: **'Phát triển hệ thống giám sát tiêu thụ nước dựa trên IoT tích hợp công nghệ Edge AI phát hiện bất thường'**.

Đề tài được thực hiện dưới sự hướng dẫn khoa học tận tình của **Tiến sĩ Nguyễn Vũ Anh Quang**."

---

## SLIDE 2: CẤU TRÚC BÀI BÁO CÁO

"Để Thầy Cô tiện theo dõi và đánh giá, bài thuyết trình của em sẽ được chia làm 8 phần chính như trên slide.

Em sẽ bắt đầu từ **Tổng quan vấn đề** để làm rõ tính cấp thiết. Sau đó, em sẽ đi sâu vào **Cơ sở lý thuyết** và đặc biệt là **Chương 5 - Phương pháp thực hiện**, nơi em sẽ phân tích kỹ các thuật toán cốt lõi. Cuối cùng, em sẽ trình bày các **Kết quả thực nghiệm** cụ thể và **định hướng phát triển** trong tương lai."

---

## SLIDE 3: LỜI CẢM ƠN

"Trước khi đi vào nội dung chuyên môn, em xin phép dành những giây phút đầu tiên để bày tỏ lòng biết ơn sâu sắc.

Em xin trân trọng cảm ơn **Ban Giám hiệu trường Đại học Công nghệ Thông tin và Truyền thông Việt - Hàn (VKU)** đã tạo điều kiện cơ sở vật chất tốt nhất cho sinh viên nghiên cứu khoa học.

Đặc biệt, em xin gửi lời tri ân đến **Tiến sĩ Nguyễn Vũ Anh Quang**. Trong quá trình thực hiện, từ những ý tưởng sơ khai về việc đưa AI lên chip nhớ thấp, đến việc tối ưu hóa từng byte dữ liệu truyền đi, Thầy đã luôn sát sao hướng dẫn và định hướng giải pháp cho em.

Em cũng xin cảm ơn **cộng đồng mã nguồn mở** và **gia đình** đã luôn là hậu phương vững chắc."

---

## SLIDE 4: TỔNG QUAN VẤN ĐỀ

"Kính thưa Hội đồng, đi vào phần tổng quan. Tại sao em lại chọn đề tài này?

Chúng ta đang nói rất nhiều về 'Thành phố thông minh', nhưng thực tế hạ tầng cấp nước tại Việt Nam vẫn đang vận hành theo cách rất thủ công.

Quy trình hiện tại là: **Nhân viên đến từng nhà → Mở nắp hầm → Ghi chép sổ sách → Nhập liệu lên máy tính**. Quy trình này bộc lộ 3 nhược điểm chí mạng mà công nghệ cần giải quyết:

1. **Chi phí vận hành khổng lồ:** Tốn kém nhân lực và thời gian di chuyển.
2. **Sai sót do con người:** Nhìn nhầm số, ghi sai, hoặc nhập liệu sai dẫn đến khiếu nại của khách hàng.
3. **Thiếu dữ liệu thời gian thực:** Đây là vấn đề nghiêm trọng nhất. Người dân không hề biết mình bị rò rỉ nước (ví dụ vỡ ống ngầm, kẹt phao bể) cho đến cuối tháng khi cầm hóa đơn tiền triệu trên tay.

Trên thị trường đã có đồng hồ thông minh (Ultrasonic/Electromagnetic), nhưng giá thành rất cao (trên $100/cái) và yêu cầu phải cắt ống thay thế toàn bộ đồng hồ cũ.

Giải pháp **IoT Digi** của em tiếp cận theo hướng khác: **Retrofit (Trang bị lại)**. Chúng ta giữ nguyên đồng hồ cơ cũ, chỉ gắn thêm một thiết bị 'mắt thần' giá rẻ để biến nó thành đồng hồ thông minh. Đây là bài toán cân bằng giữa chi phí và công nghệ."

---

## SLIDE 5: MỤC TIÊU DỰ ÁN

"Từ thực trạng trên, em đặt ra 3 mục tiêu kỹ thuật cốt lõi cho hệ thống này:

**Thứ nhất, Trí tuệ nhân tạo tại biên (Edge Intelligence):** Em kiên quyết không chọn giải pháp gửi ảnh về Server để xử lý. Việc truyền video/ảnh liên tục sẽ gây nghẽn băng thông mạng và quan trọng hơn là xâm phạm quyền riêng tư của khách hàng. Em đưa AI xuống chạy trực tiếp trên vi điều khiển ESP32, chỉ gửi đi kết quả cuối cùng (số m³ nước).

**Thứ hai, Vận hành tự chủ (Autonomous Operation):** Hệ thống phải là một thiết bị 'Set-and-Forget'. Nó phải tự động xử lý ánh sáng trong hầm tối, tự động căn chỉnh khi bị rung lắc, và tự sửa lỗi logic (như trường hợp số đang quay dở - NaN) mà không cần con người can thiệp.

**Thứ ba, Minh bạch tài chính & Cảnh báo:** Hệ thống phải chuyển đổi dữ liệu thô (m³) thành thông tin tài chính (VNĐ) theo thời gian thực và phát hiện rò rỉ ngay lập tức để cảnh báo người dùng."

---

## SLIDE 6: CƠ SỞ LÝ THUYẾT - KIẾN TRÚC IOT

"Về mặt lý thuyết, hệ thống được xây dựng chặt chẽ theo kiến trúc IoT 4 lớp tiêu chuẩn:

1. **Lớp Cảm nhận (Perception):** Sử dụng **Camera OV2640** làm cảm biến quang học. Thay vì đếm xung từ trường (dễ bị nhiễu), camera ghi lại hình ảnh thực tế của mặt số, đảm bảo độ chính xác tuyệt đối như mắt người nhìn.

2. **Lớp Mạng (Network):** Sử dụng **kết nối Wi-Fi**. Dù tiêu thụ năng lượng cao hơn LoRaWAN, nhưng Wi-Fi cho phép truyền tải dữ liệu lớn khi cần debug và tận dụng hạ tầng mạng gia đình có sẵn.

3. **Lớp Xử lý (Processing):** Em áp dụng **mô hình lai (Hybrid)**. Xử lý nặng (Vision AI) nằm tại biên (Edge), còn xử lý nghiệp vụ (Billing, Database) nằm tại Server Raspberry Pi.

4. **Lớp Ứng dụng (Application):** Bao gồm **Web Dashboard** cho người dùng theo dõi và **Chatbot** hỗ trợ."

---

## SLIDE 7: NGUYÊN LÝ GIÁM SÁT

"Nguyên lý giám sát của em là **Vision-Based Retrofitting (Không xâm lấn)**.

Khác với các cảm biến dòng chảy cần cắt ống nước để lắp đặt, thiết bị của em được thiết kế dạng module ốp bên ngoài (Add-on).

Thách thức lý thuyết ở đây chuyển từ bài toán 'cơ học thủy lực' sang bài toán 'nhận dạng mẫu' (Pattern Recognition). Hệ thống phải phân biệt được các con số 0-9 trên bánh xe cơ học, phân biệt được kim đồng hồ quay, và loại bỏ các nhiễu từ môi trường như bụi bẩn, hơi nước, hay vết xước trên mặt kính."

---

## SLIDE 8: LÝ THUYẾT AI & XỬ LÝ ẢNH - QUAN TRỌNG

"Kính thưa Hội đồng, đây là phần lý thuyết nền tảng quan trọng nhất: **Làm sao để nhét AI vào một con chip giá rẻ chỉ có vài MB bộ nhớ?** Em đã áp dụng 3 kỹ thuật chính:

### 1. Trích xuất vùng quan tâm (ROI Extraction)

Camera chụp ảnh độ phân giải 1600x1200, nhưng em không xử lý cả bức ảnh đó. Em dùng **web server** để trích xuất và cắt lấy các ô số nhỏ (kích thước chỉ 32x20 pixel). Việc này giúp loại bỏ **95% dữ liệu dư thừa** (nền, vỏ đồng hồ), giảm tải cực lớn cho CPU.

### 2. Lượng tử hóa mô hình (Model Quantization - INT8)

Các mô hình Deep Learning thông thường dùng số thực 32-bit (Float32), rất nặng và chậm. Em đã thực hiện quá trình 'Post-training Quantization', chuyển đổi toàn bộ trọng số mạng nơ-ron sang số nguyên 8-bit.

- **Kết quả:** Kích thước mô hình giảm **4 lần**.
- **Tốc độ suy luận:** Tăng gấp **3.8 lần**.
- **Độ chính xác:** Chỉ giảm từ 99.0% xuống 98.2%, một sự đánh đổi hoàn toàn chấp nhận được để chạy trên thiết bị nhúng.

### 3. TinyML

Em sử dụng framework **TensorFlow Lite for Microcontrollers**. Đây là phiên bản rút gọn đặc biệt, chạy trực tiếp trên phần cứng (Bare-metal) mà không cần hệ điều hành phức tạp, giúp tối ưu hóa từng chu kỳ máy."

---

## SLIDE 9: LÝ THUYẾT GIAO DIỆN

"Về giao diện, ngoài các tiêu chuẩn Dashboard thông thường, em muốn nhấn mạnh vào tính năng **Generative AI**.

Em tích hợp mô hình ngôn ngữ lớn **Llama 3.2** chạy cục bộ trên Server. Lý do là để giảm tải nhận thức cho người dùng. Thay vì phải tự đọc biểu đồ và suy luận, người dùng có thể hỏi **Chatbot** bằng tiếng Việt/Anh tự nhiên. Chatbot sẽ đóng vai trò như một trợ lý ảo, tự động truy vấn cơ sở dữ liệu SQL và trả lời các câu hỏi như 'Tháng này tôi dùng nước vào giờ nào nhiều nhất?', giúp công nghệ trở nên thân thiện hơn."

---

## SLIDE 10: PHƯƠNG PHÁP THỰC HIỆN - PHẦN CỨNG & TỐI ƯU HÓA NĂNG LƯỢNG

"Kính thưa Hội đồng, em xin phép đi sâu vào phương pháp triển khai cụ thể trên thiết bị biên (Edge Device).

### Thứ nhất, về nền tảng phần cứng

Em lựa chọn module **ESP32-CAM AI-Thinker**. Tuy nhiên, thách thức lớn nhất của dòng chip này là bộ nhớ. Bộ vi điều khiển ESP32 chỉ có khoảng **520KB bộ nhớ SRAM nội**, con số này là quá nhỏ để chứa một bức ảnh độ phân giải cao UXGA (1600x1200), chưa nói đến việc chạy mô hình AI.

- **Giải pháp:** Em bắt buộc phải kích hoạt và sử dụng **4MB PSRAM (RAM giả tĩnh)** bên ngoài. Đây là yếu tố sống còn để tạo ra vùng đệm (buffer) chứa ảnh và không gian tính toán (Tensor Arena) cho thư viện TensorFlow Lite hoạt động.

### Thứ hai, về chiến lược thu thập ảnh

Việc chụp ảnh đồng hồ trong hầm tối không đơn giản là bật đèn và chụp. Nếu bật đèn Flash quá lâu, nhiệt độ sẽ làm nhiễu cảm biến ảnh (thermal noise).

- **Kỹ thuật:** Em đã lập trình điều khiển **đèn Flash LED ở cấp độ mili-giây**. Đèn chỉ bật sáng ngay trước khi máy ảnh mở và tắt ngay lập tức sau khi thu nhận dữ liệu xong. Kỹ thuật đồng bộ này giúp loại bỏ hiện tượng nhòe do chuyển động (motion blur) và đảm bảo độ sáng đồng nhất cho AI.

### Thứ ba, về quản lý năng lượng

Vì đây là thiết bị Retrofit dùng pin, em không thể để CPU chạy liên tục. Em thiết lập chu trình **Deep Sleep (Ngủ sâu)** nghiêm ngặt.

- **Quy trình hoạt động là:** Thiết bị thức dậy → Khởi động Camera → Chụp ảnh & Xử lý AI → Gửi dữ liệu → Và lập tức ngắt toàn bộ nguồn điện của các ngoại vi (bao gồm cả sóng WiFi) để quay lại trạng thái ngủ.
- **Toàn bộ quá trình này** chỉ diễn ra trong khoảng vài chục giây, giúp kéo dài tuổi thọ pin lên tối đa."

---

## SLIDE 11: THUẬT TOÁN XỬ LÝ TẠI BIÊN - QUAN TRỌNG NHẤT

"Kính thưa Thầy Cô, để biến một bức ảnh thô thành con số chính xác, em đã xây dựng một **Pipeline xử lý 3 bước** phức tạp ngay trên con chip ESP32:

### Bước 1: Ổn định hình ảnh (Automatic Alignment)

Trong thực tế lắp đặt, camera gắn trên ống nước chắc chắn sẽ bị rung lắc hoặc xê dịch theo thời gian. Nếu chỉ cắt ảnh theo tọa độ cố định (Hard-code), AI sẽ nhìn nhầm vị trí.

- **Giải pháp:** Em sử dụng **thuật toán so khớp đặc trưng**. Hệ thống sẽ so sánh ảnh hiện tại với một **'ảnh tham chiếu' (Reference Image)** được lưu lúc cài đặt.
- Nó tính toán ra một **ma trận biến đổi** gồm 3 tham số: độ dịch chuyển (Delta x), (Delta y) và góc xoay (theta). Sau đó, hệ thống tự động dịch chuyển khung hình cắt (ROI) để bù trừ lại sai số này. Điều này đảm bảo AI luôn nhận được hình ảnh con số nằm chính giữa khung hình.

### Bước 2: Mô hình Hybrid AI (Lai ghép CNN & Regression)

Em không dùng một mô hình cho tất cả, mà tách ra làm hai loại chuyên biệt:

1. **Với dãy số (Digits):** Em dùng **mạng CNN lượng tử hóa**. Đặc biệt, em thêm lớp **BatchNormalization** ngay đầu vào để xử lý việc chênh lệch ánh sáng (lóa hoặc tối). Mô hình này phân loại 11 lớp: từ 0-9 và lớp đặc biệt là **'NaN'**.

2. **Với kim đồng hồ (Analog):** Đây là điểm sáng tạo của đồ án. Thay vì phân loại góc quay (dễ sai số lớn), em dùng mô hình **Hồi quy (Regression)** để dự đoán tọa độ **sin** và **cos** của kim.
   - **Tại sao là sin/cos?** Vì tại điểm chuyển giao giữa 9.9 và 0.0, giá trị số nhảy vọt gây khó khăn cho việc huấn luyện. Việc chuyển sang không gian sin/cos giúp biến bài toán thành một vòng tròn liên tục, loại bỏ hoàn toàn sai số tại điểm chết này.

### Bước 3: Logic hậu xử lý (Anomaly Correction Logic)

Kết quả từ AI chưa phải là kết quả cuối cùng. Nó phải đi qua **bộ lọc Logic vật lý**:

- **Xử lý trạng thái 'NaN':** Khi số đang quay lơ lửng (ví dụ giữa số 1 và 2), AI sẽ báo là 'NaN'. Lúc này, thuật toán sẽ nhìn vào chữ số hàng đơn vị bên cạnh. Nếu hàng đơn vị > 5, nghĩa là số lớn đang chuẩn bị nhảy lên → Hệ thống tự động làm tròn lên (Round Up). Ngược lại sẽ làm tròn xuống.
- **Chặn dòng chảy ảo:** Nếu giá trị mới nhỏ hơn giá trị cũ (âm nước) hoặc tốc độ chảy vượt quá ngưỡng vật lý của đường ống (Max Rate), hệ thống sẽ coi đó là nhiễu và loại bỏ ngay lập tức."

---

## SLIDE 12: KIẾN TRÚC HỆ THỐNG & GIAO THỨC TRUYỀN THÔNG

"Sau khi xử lý xong tại biên, dữ liệu được truyền về Server. Ở phần này, em muốn nhấn mạnh vào sự tối ưu hóa trong giao thức truyền thông.

### Thứ nhất, Giao thức Binary Webhook (Binary Payload)

Đa số các hệ thống IoT hiện nay dùng định dạng JSON (dạng văn bản) để gửi dữ liệu. Tuy nhiên, JSON rất tốn dung lượng và vi điều khiển phải tốn CPU để xử lý chuỗi ký tự.

- **Cải tiến của em:** Em thiết kế riêng một **cấu trúc gói tin nhị phân (Binary Struct)**. Gói tin này bắt đầu bằng **4 Magic Bytes (0x44 0x49 0x47 0x49)** (tức là 'DIGI'), theo sau là API Key và giá trị nước dạng số thực (Float).
- **Hiệu quả:** Cách làm này giúp giảm kích thước gói tin tới **60%** so với JSON. Gói tin nhỏ hơn đồng nghĩa với việc thời gian phát sóng WiFi ngắn hơn, giúp tiết kiệm pin đáng kể cho thiết bị.

### Thứ hai, Kiến trúc phân tách (Decoupled Architecture)

Em thiết kế hệ thống theo mô hình phân tách nhiệm vụ rõ ràng:

- **ESP32 (Edge):** Chỉ đóng vai trò là 'Cảm biến thông minh'. Nó không hề biết giá nước là bao nhiêu, nó chỉ biết đọc số m³ và gửi đi.
- **Raspberry Pi (Server):** Đóng vai trò là 'Bộ não nghiệp vụ'. Script `webhook.php` tại đây sẽ nhận dữ liệu thô, sau đó mới áp dụng các công thức tính tiền bậc thang (Tier 1, Tier 2...) để lưu vào Database.
- **Lợi ích:** Kiến trúc này giúp em có thể thay đổi giá nước hoặc thay đổi logic tính tiền trên Server mà không cần phải nạp lại code (Flash Firmware) cho hàng trăm thiết bị đo đang lắp đặt ngoài hiện trường."

---

## SLIDE 13: KẾT QUẢ THỰC NGHIỆM

"Sau 72 giờ vận hành liên tục trong môi trường giả lập (có máy bơm biến tần điều chỉnh lưu lượng), em thu được các kết quả định lượng sau:

1. **Độ chính xác AI:** Đạt **98.2%** trên các số tĩnh. Với các số chuyển động (NaN), thuật toán Logic giúp khôi phục chính xác **94%** trường hợp.

2. **Độ tin cậy Billing:** Em đã so sánh hóa đơn do hệ thống tự tính với tính toán thủ công bằng Excel. Sai số lệch chưa đến **0.05%**, chứng minh thuật toán tính tiền bậc thang hoạt động chính xác.

3. **Hiệu năng:** Thời gian xử lý từ lúc thức dậy đến lúc ngủ trung bình là **40 giây**.

4. **Tuổi thọ pin:** Dựa trên mức tiêu thụ đo được (0.07 mAh/chu kỳ), pin 2500mAh có thể duy trì lý thuyết lên tới **3.7 năm** (với tần suất đọc 1 lần/ngày)."

---

## SLIDE 14: ĐÁNH GIÁ

"Dựa trên bảng đánh giá tổng quan, em xin rút ra các nhận định khách quan:

**Điểm mạnh lớn nhất:** **Hiệu quả kinh tế**. Tổng linh kiện (BoM) chỉ dưới **$10**, rẻ hơn gấp 10-20 lần so với giải pháp thay thế đồng hồ thông minh. Đồng thời đảm bảo quyền riêng tư tuyệt đối cho người dùng.

**Hạn chế:** Em cũng thẳng thắn nhìn nhận các điểm yếu.

- **Thứ nhất,** kết nối Wi-Fi dù tiện nhưng vẫn tốn pin hơn chuẩn công nghiệp LoRaWAN.
- **Thứ hai,** hiện tượng lóa đèn Flash (Specular Highlights) trên mặt kính đôi khi tạo ra điểm mù làm mất thông tin ảnh.
- **Thứ ba,** mô hình AI hiện tại đang 'học tủ' (overfit) phông chữ của đồng hồ Emic, sẽ cần huấn luyện lại nếu đổi sang hãng khác."

---

## SLIDE 15: KẾT LUẬN & HƯỚNG PHÁT TRIỂN

"Kết luận lại, đồ án của em đã chứng minh tính khả thi của việc 'thổi hồn' công nghệ 4.0 vào những thiết bị cơ khí cũ kỹ. Em đã xây dựng thành công quy trình khép kín: **Từ thu thập ảnh → Xử lý AI tại biên → Truyền tải tối ưu → Đến hỗ trợ người dùng bằng Chatbot**.

Hướng phát triển tiếp theo để thương mại hóa sản phẩm:

1. **Tích hợp kính lọc phân cực (CPL)** để vật lý hóa việc khử lóa sáng.
2. **Chuyển sang công nghệ LoRaWAN** để tăng tầm phủ sóng lên hàng km và tiết kiệm pin gấp 10 lần.
3. **Phát triển mô hình 'Universal Meter Reader'** để đọc được đa dạng các loại đồng hồ trên thị trường."

---

## SLIDE 16: KẾT THÚC

"Phần trình bày của em đến đây là kết thúc.

Em xin chân thành cảm ơn Quý Thầy Cô và các bạn đã dành thời gian lắng nghe một bài báo cáo khá dài và nhiều chi tiết kỹ thuật.

Em rất mong nhận được những câu hỏi phản biện, những góp ý chuyên môn từ Hội đồng để em có thể nhìn nhận vấn đề đa chiều hơn và hoàn thiện sản phẩm tốt hơn nữa. Em xin trân trọng cảm ơn!"

---
