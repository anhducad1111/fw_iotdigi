#ifdef ENABLE_WEBHOOK
#include "interface_webhook.h"
#include "webhook_binary.h"

#include "esp_log.h"
#include <time.h>
#include "ClassLogFile.h"
#include "esp_http_client.h"
#include "time_sntp.h"
#include "../../include/defines.h"
#include <cJSON.h>
#include <ClassFlowDefineTypes.h>
#include <vector>

#define MAX_HTTP_OUTPUT_BUFFER 2048


static const char *TAG = "WEBHOOK";

std::string _webhookURI;
std::string _webhookApiKey;
long _lastTimestamp;

static esp_err_t http_event_handler(esp_http_client_event_t *evt);

void WebhookInit(std::string _uri, std::string _apiKey)
{
    _webhookURI = _uri;
    _webhookApiKey = _apiKey;
    _lastTimestamp = 0L;
}

bool WebhookPublish(std::vector<NumberPost*>* numbers)
{
    bool numbersWithError = false;
    
    // Build binary packet
    std::vector<uint8_t> dataBuffer;
    
    // Number of items
    WebhookBinaryHelper::writeByte(dataBuffer, (*numbers).size());
    
    for (int i = 0; i < (*numbers).size(); ++i)
    {
        time_t lastValueTime = (*numbers)[i]->timeStampLastValue;
        _lastTimestamp = static_cast<long>(lastValueTime);
        
        // Write each field for this NumberPost
        // 1. Name
        WebhookBinaryHelper::writeString(dataBuffer, (*numbers)[i]->name);
        
        // 2. Timestamp (8 bytes)
        WebhookBinaryHelper::writeUint64BE(dataBuffer, (uint64_t)_lastTimestamp);
        
        // 3. Raw Value
        WebhookBinaryHelper::writeString(dataBuffer, (*numbers)[i]->ReturnRawValue);
        
        // 4. Value
        WebhookBinaryHelper::writeString(dataBuffer, (*numbers)[i]->ReturnValue);
        
        // 5. Pre Value
        WebhookBinaryHelper::writeString(dataBuffer, (*numbers)[i]->ReturnPreValue);
        
        // 6. Rate
        WebhookBinaryHelper::writeString(dataBuffer, (*numbers)[i]->ReturnRateValue);
        
        // 7. Change Absolute
        WebhookBinaryHelper::writeString(dataBuffer, (*numbers)[i]->ReturnChangeAbsolute);
        
        // 8. Error code (1 byte) - map string to error code
        ErrorCode errCode = WebhookBinaryHelper::stringToErrorCode((*numbers)[i]->ErrorMessageText);
        WebhookBinaryHelper::writeByte(dataBuffer, (uint8_t)errCode);

        if ((*numbers)[i]->ErrorMessage) {
            numbersWithError = true;
        }
    }
    
    // Build complete packet with header and checksum
    std::vector<uint8_t> packet;
    
    // Magic header (4 bytes)
    WebhookBinaryHelper::writeUint32BE(packet, WEBHOOK_MAGIC_HEADER);
    
    // Data length (2 bytes)
    WebhookBinaryHelper::writeUint16BE(packet, dataBuffer.size());
    
    // Data
    packet.insert(packet.end(), dataBuffer.begin(), dataBuffer.end());
    
    // Calculate CRC16 of header + length + data (before adding checksum)
    uint16_t crc = WebhookBinaryHelper::calculateCRC16(packet.data(), packet.size());
    
    // Checksum (2 bytes)
    WebhookBinaryHelper::writeUint16BE(packet, crc);

    LogFile.WriteToFile(ESP_LOG_INFO, TAG, "sending webhook binary");
    LogFile.WriteToFile(ESP_LOG_DEBUG, TAG, "packet size: " + std::to_string(packet.size()) + " bytes");

    char response_buffer[MAX_HTTP_OUTPUT_BUFFER] = {0};
    esp_http_client_config_t http_config = {};
    http_config.url = _webhookURI.c_str();
    http_config.user_agent = "ESP32 Meter reader";
    http_config.method = HTTP_METHOD_POST;
    http_config.event_handler = http_event_handler;
    http_config.buffer_size = MAX_HTTP_OUTPUT_BUFFER;
    http_config.user_data = response_buffer;

    esp_http_client_handle_t http_client = esp_http_client_init(&http_config);

    esp_http_client_set_header(http_client, "Content-Type", "application/octet-stream");
    esp_http_client_set_header(http_client, "APIKEY", _webhookApiKey.c_str());

    ESP_ERROR_CHECK(esp_http_client_set_post_field(http_client, (const char *)packet.data(), packet.size()));

    esp_err_t err = ESP_ERROR_CHECK_WITHOUT_ABORT(esp_http_client_perform(http_client));

    if(err == ESP_OK) {
        LogFile.WriteToFile(ESP_LOG_DEBUG, TAG, "HTTP request was performed");
        int status_code = esp_http_client_get_status_code(http_client);
        LogFile.WriteToFile(ESP_LOG_DEBUG, TAG, "HTTP status code: " + std::to_string(status_code));
    } else {
        LogFile.WriteToFile(ESP_LOG_ERROR, TAG, "HTTP request failed");
    } 

    esp_http_client_cleanup(http_client);
    return numbersWithError;    
}

void WebhookUploadPic(ImageData *Img) {
    LogFile.WriteToFile(ESP_LOG_INFO, TAG, "Starting WebhookUploadPic");

    std::string fullURI = _webhookURI + "?timestamp=" + std::to_string(_lastTimestamp);
    char response_buffer[MAX_HTTP_OUTPUT_BUFFER] = {0};
    esp_http_client_config_t http_config = {};
    http_config.url = fullURI.c_str();
    http_config.user_agent = "ESP32 Meter reader";
    http_config.method = HTTP_METHOD_PUT;
    http_config.event_handler = http_event_handler;
    http_config.buffer_size = MAX_HTTP_OUTPUT_BUFFER;
    http_config.user_data = response_buffer;

    esp_http_client_handle_t http_client = esp_http_client_init(&http_config);

    esp_http_client_set_header(http_client, "Content-Type", "image/jpeg");
    esp_http_client_set_header(http_client, "APIKEY", _webhookApiKey.c_str());

    esp_err_t err = ESP_ERROR_CHECK_WITHOUT_ABORT(esp_http_client_set_post_field(http_client, (const char *)Img->data, Img->size));

    err = ESP_ERROR_CHECK_WITHOUT_ABORT(esp_http_client_perform(http_client));

    if (err == ESP_OK) {
        LogFile.WriteToFile(ESP_LOG_DEBUG, TAG, "HTTP PUT request was performed successfully");
        int status_code = esp_http_client_get_status_code(http_client);
        LogFile.WriteToFile(ESP_LOG_DEBUG, TAG, "HTTP status code: " + std::to_string(status_code));
    } else {
        LogFile.WriteToFile(ESP_LOG_ERROR, TAG, "HTTP PUT request failed");
    }

    esp_http_client_cleanup(http_client);

    LogFile.WriteToFile(ESP_LOG_INFO, TAG, "WebhookUploadPic finished");
}


static esp_err_t http_event_handler(esp_http_client_event_t *evt)
{
    switch(evt->event_id)
    {
        case HTTP_EVENT_ERROR:
            LogFile.WriteToFile(ESP_LOG_DEBUG, TAG, "HTTP Client Error encountered");
            break;
        case HTTP_EVENT_ON_CONNECTED:
            LogFile.WriteToFile(ESP_LOG_DEBUG, TAG, "HTTP Client connected");
            ESP_LOGI(TAG, "HTTP Client Connected");
            break;
        case HTTP_EVENT_HEADERS_SENT:
            LogFile.WriteToFile(ESP_LOG_DEBUG, TAG, "HTTP Client sent all request headers");
            break;
        case HTTP_EVENT_ON_HEADER:
            LogFile.WriteToFile(ESP_LOG_DEBUG, TAG, "Header: key=" + std::string(evt->header_key) + ", value="  + std::string(evt->header_value));
            break;
        case HTTP_EVENT_ON_DATA:
            LogFile.WriteToFile(ESP_LOG_DEBUG, TAG, "HTTP Client data recevied: len=" + std::to_string(evt->data_len));
            break;
        case HTTP_EVENT_ON_FINISH:
            LogFile.WriteToFile(ESP_LOG_DEBUG, TAG, "HTTP Client finished");
            break;
         case HTTP_EVENT_DISCONNECTED:
            LogFile.WriteToFile(ESP_LOG_DEBUG, TAG, "HTTP Client Disconnected");
            break;
        case HTTP_EVENT_REDIRECT:
            LogFile.WriteToFile(ESP_LOG_DEBUG, TAG, "HTTP Redirect");
            break;
    }
    return ESP_OK;
}

#endif //ENABLE_WEBHOOK
