#include <ESP8266WiFi.h>
#include <ESP8266WebServer.h>

// WiFi credentials
const char* ssid = "duc";
const char* password = "11111111";

// Web server on port 5001
ESP8266WebServer server(5001);

// Allowed API keys
const char* ALLOWED_API_KEYS[] = {"123", "456", "789"};
const int NUM_API_KEYS = 3;

// Global variable to store API key from request
String currentApiKey = "";

// Magic header for webhook binary format
#define WEBHOOK_MAGIC_HEADER 0x4A4F4D4A  // "JOMJ"

// Error code mapping
enum ErrorCode : uint8_t {
    ERROR_NONE = 0,                    // "no error" or empty
    ERROR_NEGATIVE_RATE = 1,           // "Neg. Rate - ..."
    ERROR_RATE_TOO_HIGH = 2,           // "Rate too high - ..."
    ERROR_UNKNOWN = 255                // Any other error
};

// Binary helper functions
class BinaryHelper {
public:
    static uint16_t readUint16BE(const uint8_t* buffer, int& offset) {
        uint16_t value = (buffer[offset] << 8) | buffer[offset + 1];
        offset += 2;
        return value;
    }

    static uint32_t readUint32BE(const uint8_t* buffer, int& offset) {
        uint32_t value = (buffer[offset] << 24) | (buffer[offset + 1] << 16) | 
                         (buffer[offset + 2] << 8) | buffer[offset + 3];
        offset += 4;
        return value;
    }

    static uint64_t readUint64BE(const uint8_t* buffer, int& offset) {
        uint64_t value = ((uint64_t)buffer[offset] << 56) | ((uint64_t)buffer[offset + 1] << 48) |
                         ((uint64_t)buffer[offset + 2] << 40) | ((uint64_t)buffer[offset + 3] << 32) |
                         ((uint64_t)buffer[offset + 4] << 24) | ((uint64_t)buffer[offset + 5] << 16) |
                         ((uint64_t)buffer[offset + 6] << 8) | buffer[offset + 7];
        offset += 8;
        return value;
    }

    static uint8_t readByte(const uint8_t* buffer, int& offset) {
        return buffer[offset++];
    }

    static String readString(const uint8_t* buffer, int& offset, int maxLen) {
        uint8_t len = buffer[offset++];
        if (len > maxLen) len = maxLen;
        
        String str = "";
        for (int i = 0; i < len; i++) {
            str += (char)buffer[offset++];
        }
        return str;
    }

    static uint16_t calculateCRC16(const uint8_t* data, int length) {
        uint16_t crc = 0xFFFF;
        
        for (int i = 0; i < length; i++) {
            crc ^= (uint16_t)data[i] << 8;
            for (int j = 0; j < 8; j++) {
                if (crc & 0x8000) {
                    crc = (crc << 1) ^ 0x1021;
                } else {
                    crc = crc << 1;
                }
                crc &= 0xFFFF;
            }
        }
        
        return crc;
    }

    static String errorCodeToString(uint8_t code) {
        switch (code) {
            case ERROR_NONE:
                return "no error";
            case ERROR_NEGATIVE_RATE:
                return "Neg. Rate";
            case ERROR_RATE_TOO_HIGH:
                return "Rate too high";
            case ERROR_UNKNOWN:
            default:
                return "unknown error";
        }
    }
};

// Function to check API key
bool checkApiKey() {
    // Get API key from request headers
    if (server.hasHeader("APIKEY")) {
        currentApiKey = server.header("APIKEY");
        
        // Check if API key is in allowed list
        for (int i = 0; i < NUM_API_KEYS; i++) {
            if (currentApiKey == ALLOWED_API_KEYS[i]) {
                return true;
            }
        }
    }
    return false;
}

// Handle webhook POST request (parse binary data)
void handleWebhookPost() {
    if (!checkApiKey()) {
        server.send(403, "application/json", "{\"status\":\"error\",\"message\":\"Invalid API key\"}");
        return;
    }

    // Get binary data from request
    String contentType = server.header("Content-Type");
    if (contentType != "application/octet-stream") {
        server.send(400, "application/json", "{\"status\":\"error\",\"message\":\"Invalid Content-Type. Expected application/octet-stream\"}");
        return;
    }

    uint8_t* body = (uint8_t*)server.arg("plain").c_str();
    int bodyLen = server.arg("plain").length();

    if (bodyLen < 8) {  // Min: 4 (magic) + 2 (length) + 2 (crc)
        server.send(400, "application/json", "{\"status\":\"error\",\"message\":\"Invalid packet size\"}");
        return;
    }

    int offset = 0;

    // Parse header
    uint32_t magic = BinaryHelper::readUint32BE(body, offset);
    if (magic != WEBHOOK_MAGIC_HEADER) {
        server.send(400, "application/json", "{\"status\":\"error\",\"message\":\"Invalid magic header\"}");
        return;
    }

    // Parse data length
    uint16_t dataLength = BinaryHelper::readUint16BE(body, offset);
    
    // Verify packet structure: 4 (magic) + 2 (length) + dataLength + 2 (crc) = bodyLen
    if (4 + 2 + dataLength + 2 != bodyLen) {
        server.send(400, "application/json", "{\"status\":\"error\",\"message\":\"Packet length mismatch\"}");
        return;
    }

    // Verify CRC16
    uint16_t expectedCRC = BinaryHelper::readUint16BE(body, offset + dataLength);
    uint16_t calculatedCRC = BinaryHelper::calculateCRC16(body, 4 + 2 + dataLength);
    
    if (expectedCRC != calculatedCRC) {
        server.send(400, "application/json", "{\"status\":\"error\",\"message\":\"CRC16 checksum failed\"}");
        return;
    }

    Serial.println("\n=== Webhook Binary Data Received ===");
    Serial.print("API Key: ");
    Serial.println(currentApiKey);
    Serial.print("Packet Size: ");
    Serial.print(bodyLen);
    Serial.println(" bytes");
    Serial.print("Data Length: ");
    Serial.print(dataLength);
    Serial.println(" bytes");
    Serial.print("Checksum: 0x");
    Serial.println(expectedCRC, HEX);

    // Parse data
    offset = 6;  // Skip magic (4) + length (2)
    uint8_t numItems = BinaryHelper::readByte(body, offset);
    
    Serial.print("Number of items: ");
    Serial.println(numItems);

    for (int i = 0; i < numItems; i++) {
        Serial.print("\n--- Item ");
        Serial.print(i + 1);
        Serial.println(" ---");

        // Read fields
        String name = BinaryHelper::readString(body, offset, 255);
        Serial.print("Name: ");
        Serial.println(name);

        uint64_t timestamp = BinaryHelper::readUint64BE(body, offset);
        Serial.print("Timestamp: ");
        Serial.println((long)timestamp);

        String rawValue = BinaryHelper::readString(body, offset, 255);
        Serial.print("Raw Value: ");
        Serial.println(rawValue);

        String value = BinaryHelper::readString(body, offset, 255);
        Serial.print("Value: ");
        Serial.println(value);

        String preValue = BinaryHelper::readString(body, offset, 255);
        Serial.print("Pre Value: ");
        Serial.println(preValue);

        String rate = BinaryHelper::readString(body, offset, 255);
        Serial.print("Rate: ");
        Serial.println(rate);

        String changeAbsolute = BinaryHelper::readString(body, offset, 255);
        Serial.print("Change Absolute: ");
        Serial.println(changeAbsolute);

        uint8_t errorCode = BinaryHelper::readByte(body, offset);
        String errorStr = BinaryHelper::errorCodeToString(errorCode);
        Serial.print("Error Code: ");
        Serial.print(errorCode);
        Serial.print(" (");
        Serial.print(errorStr);
        Serial.println(")");
    }

    Serial.println("\n=====================================\n");
    
    server.send(200, "application/json", "{\"status\":\"success\",\"message\":\"Binary data received and parsed\"}");
}

// Handle webhook requests
void handleWebhook() {
    if (server.method() == HTTP_POST) {
        handleWebhookPost();
    } else {
        server.send(405, "application/json", "{\"status\":\"error\",\"message\":\"Method not allowed\"}");
    }
}

// Setup function
void setup() {
    Serial.begin(115200);
    delay(100);
    
    Serial.println("\n\nStarting ESP8266 Webhook Server (Binary Mode)...");

    // Connect to WiFi
    Serial.print("Connecting to WiFi: ");
    Serial.println(ssid);
    
    WiFi.mode(WIFI_STA);
    WiFi.begin(ssid, password);

    int attempts = 0;
    while (WiFi.status() != WL_CONNECTED && attempts < 20) {
        delay(500);
        Serial.print(".");
        attempts++;
    }

    if (WiFi.status() == WL_CONNECTED) {
        Serial.println("\nWiFi connected!");
        Serial.print("IP address: ");
        Serial.println(WiFi.localIP());
    } else {
        Serial.println("\nFailed to connect to WiFi");
        // Continue anyway for testing
    }

    // Setup web server routes
    server.on("/webhook", handleWebhook);

    // Start server
    server.begin();
    Serial.println("Webhook server started on port 5001");
    Serial.println("Endpoints:");
    Serial.println("  POST /webhook - Receive binary webhook data");
}

// Loop function
void loop() {
    server.handleClient();
    delay(1);
}
