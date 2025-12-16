#ifdef ENABLE_WEBHOOK

#pragma once
#ifndef WEBHOOK_BINARY_H
#define WEBHOOK_BINARY_H

#include <stdint.h>
#include <vector>
#include <string>
#include <cstring>

// Magic header for webhook binary format
#define WEBHOOK_MAGIC_HEADER 0x44494749  // "DIGI"

// Error code mapping
enum ErrorCode : uint8_t {
    ERROR_NONE = 0,                    // "no error" or empty
    ERROR_NEGATIVE_RATE = 1,           // "Neg. Rate - ..."
    ERROR_RATE_TOO_HIGH = 2,           // "Rate too high - ..."
    ERROR_UNKNOWN = 255                // Any other error
};

// Structure for binary packet
struct WebhookBinaryPacket {
    uint32_t magic;           // 0x44494749

    uint16_t dataLength;      // Length of data section
    uint8_t* data;            // Pointer to data
    uint16_t checksum;        // CRC16
};

// Helper functions
class WebhookBinaryHelper {
public:
    // Encoding functions
    static void writeByte(std::vector<uint8_t>& buffer, uint8_t value);
    static void writeUint16BE(std::vector<uint8_t>& buffer, uint16_t value);
    static void writeUint32BE(std::vector<uint8_t>& buffer, uint32_t value);
    static void writeUint64BE(std::vector<uint8_t>& buffer, uint64_t value);
    static void writeString(std::vector<uint8_t>& buffer, const std::string& str);
    
    // Decoding functions
    static uint8_t readByte(const uint8_t* buffer, int& offset);
    static uint16_t readUint16BE(const uint8_t* buffer, int& offset);
    static uint32_t readUint32BE(const uint8_t* buffer, int& offset);
    static uint64_t readUint64BE(const uint8_t* buffer, int& offset);
    static std::string readString(const uint8_t* buffer, int& offset, int maxLen);
    
    // CRC16 calculation
    static uint16_t calculateCRC16(const uint8_t* data, int length);
    
    // Error code mapping
    static ErrorCode stringToErrorCode(const std::string& errorStr);
    static std::string errorCodeToString(ErrorCode code);
};

#endif // WEBHOOK_BINARY_H
#endif // ENABLE_WEBHOOK
