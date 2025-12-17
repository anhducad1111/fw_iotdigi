## 1. Binary Protocol Structure

The system uses a custom binary protocol to optimize bandwidth, reducing payload size by ~60% compared to JSON.

```mermaid
classDiagram
    class BinaryPacket {
        +uint32 MagicHeader (0x44494749)
        +uint16 DataLength
        +DataItem[] Items
        +uint16 Checksum (CRC16)
    }

    class DataItem {
        +uint64 Timestamp
        +String RawValue
        +String FinalValue
        +String PreValue
        +String Rate
        +String ChangeAbs
        +uint8 ErrorCode
    }

    BinaryPacket *-- DataItem : contains 1..N
```

## 2. Webhook Transmission Sequence

This diagram illustrates the streamlined process of the ESP32 transmitting telemetry data to the PHP backend.

```mermaid
sequenceDiagram
    participant ESP as ESP32-CAM
    participant Net as Network
    participant Serv as RPi Handler (PHP)
    participant DB as MariaDB

    Note left of ESP: 1. Packing Phase
    ESP->>ESP: Serialize Data (Binary)
    ESP->>ESP: Calculate CRC16

    Note left of ESP: 2. Transmission Phase
    ESP->>Net: HTTP POST (Stream)
    Note over ESP, Net: Headers: APIKEY, Content-Type: octet-stream

    Net->>Serv: Receive Request
    activate Serv
        Serv->>Serv: Validate API Key
        Serv->>Serv: Verify Magic Bytes & CRC

        alt Data Valid
            Serv->>DB: INSERT readings
            Serv->>DB: UPDATE billing_tables
            Serv-->>Net: 200 OK
        else Critical Error
            Serv-->>Net: 400/403 Error
        end
    deactivate Serv

    Net-->>ESP: Acknowledge

    alt Success
        ESP->>ESP: Mark as Sent
    else Failure
        ESP->>ESP: Retry Later
    end
```
