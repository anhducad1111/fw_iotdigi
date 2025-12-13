#ifdef ENABLE_WEBHOOK

#include "webhook_binary.h"
#include "esp_log.h"

static const char *TAG = "WEBHOOK_BINARY";
void WebhookBinaryHelper::writeByte(std::vector<uint8_t>& buffer, uint8_t value) {
    buffer.push_back(value);
}

// Write uint16 big-endian
void WebhookBinaryHelper::writeUint16BE(std::vector<uint8_t>& buffer, uint16_t value) {
    buffer.push_back((value >> 8) & 0xFF);
    buffer.push_back(value & 0xFF);
}

// Write uint32 big-endian
void WebhookBinaryHelper::writeUint32BE(std::vector<uint8_t>& buffer, uint32_t value) {
    buffer.push_back((value >> 24) & 0xFF);
    buffer.push_back((value >> 16) & 0xFF);
    buffer.push_back((value >> 8) & 0xFF);
    buffer.push_back(value & 0xFF);
}

// Write uint64 big-endian
void WebhookBinaryHelper::writeUint64BE(std::vector<uint8_t>& buffer, uint64_t value) {
    buffer.push_back((value >> 56) & 0xFF);
    buffer.push_back((value >> 48) & 0xFF);
    buffer.push_back((value >> 40) & 0xFF);
    buffer.push_back((value >> 32) & 0xFF);
    buffer.push_back((value >> 24) & 0xFF);
    buffer.push_back((value >> 16) & 0xFF);
    buffer.push_back((value >> 8) & 0xFF);
    buffer.push_back(value & 0xFF);
}

// Write string with length prefix
void WebhookBinaryHelper::writeString(std::vector<uint8_t>& buffer, const std::string& str) {
    uint8_t len = str.length() > 255 ? 255 : str.length();
    buffer.push_back(len);
    for (int i = 0; i < len; i++) {
        buffer.push_back(str[i]);
    }
}

// Read single byte
uint8_t WebhookBinaryHelper::readByte(const uint8_t* buffer, int& offset) {
    return buffer[offset++];
}

// Read uint16 big-endian
uint16_t WebhookBinaryHelper::readUint16BE(const uint8_t* buffer, int& offset) {
    uint16_t value = (buffer[offset] << 8) | buffer[offset + 1];
    offset += 2;
    return value;
}

// Read uint32 big-endian
uint32_t WebhookBinaryHelper::readUint32BE(const uint8_t* buffer, int& offset) {
    uint32_t value = (buffer[offset] << 24) | (buffer[offset + 1] << 16) | 
                     (buffer[offset + 2] << 8) | buffer[offset + 3];
    offset += 4;
    return value;
}

// Read uint64 big-endian
uint64_t WebhookBinaryHelper::readUint64BE(const uint8_t* buffer, int& offset) {
    uint64_t value = ((uint64_t)buffer[offset] << 56) | ((uint64_t)buffer[offset + 1] << 48) |
                     ((uint64_t)buffer[offset + 2] << 40) | ((uint64_t)buffer[offset + 3] << 32) |
                     ((uint64_t)buffer[offset + 4] << 24) | ((uint64_t)buffer[offset + 5] << 16) |
                     ((uint64_t)buffer[offset + 6] << 8) | buffer[offset + 7];
    offset += 8;
    return value;
}

// Read string with length prefix
std::string WebhookBinaryHelper::readString(const uint8_t* buffer, int& offset, int maxLen) {
    uint8_t len = buffer[offset++];
    if (len > maxLen) len = maxLen;
    
    std::string str;
    for (int i = 0; i < len; i++) {
        str += (char)buffer[offset++];
    }
    return str;
}

// CRC16 calculation (CRC-CCITT)
uint16_t WebhookBinaryHelper::calculateCRC16(const uint8_t* data, int length) {
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

// Map error string to error code
ErrorCode WebhookBinaryHelper::stringToErrorCode(const std::string& errorStr) {
    // Debug log
    ESP_LOGD(TAG, "stringToErrorCode: input='%s'", errorStr.c_str());
    
    if (errorStr.empty() || errorStr == "no error") {
        ESP_LOGD(TAG, "stringToErrorCode: returning ERROR_NONE");
        return ERROR_NONE;
    }
    if (errorStr.find("Neg. Rate") != std::string::npos) {
        ESP_LOGD(TAG, "stringToErrorCode: found 'Neg. Rate', returning ERROR_NEGATIVE_RATE");
        return ERROR_NEGATIVE_RATE;
    }
    if (errorStr.find("Rate too high") != std::string::npos) {
        ESP_LOGD(TAG, "stringToErrorCode: found 'Rate too high', returning ERROR_RATE_TOO_HIGH");
        return ERROR_RATE_TOO_HIGH;
    }
    ESP_LOGD(TAG, "stringToErrorCode: no match, returning ERROR_UNKNOWN");
    return ERROR_UNKNOWN;
}

// Map error code to error string
std::string WebhookBinaryHelper::errorCodeToString(ErrorCode code) {
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

#endif // ENABLE_WEBHOOK
