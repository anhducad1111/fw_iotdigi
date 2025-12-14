-- Create database iotdigi_db
CREATE DATABASE IF NOT EXISTS iotdigi_db;
USE iotdigi_db;

-- 1. Table readings - store all raw data from webhook
CREATE TABLE IF NOT EXISTS readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    value DECIMAL(10, 2) NOT NULL COMMENT 'Value in m³ from sensor',
    timestamp BIGINT NOT NULL COMMENT 'Unix timestamp',
    error_code INT DEFAULT 0 COMMENT 'Error code',
    error_message VARCHAR(255) DEFAULT NULL COMMENT 'Error message',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_timestamp (timestamp),
    INDEX idx_created_at (created_at)
);

-- 2. Table daily_usage - total water consumption per day
CREATE TABLE IF NOT EXISTS daily_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL UNIQUE COMMENT 'Date',
    consumption DECIMAL(10, 2) NOT NULL DEFAULT 0 COMMENT 'Total water usage (m³)',
    start_value DECIMAL(10, 2) COMMENT 'Starting value of the day',
    end_value DECIMAL(10, 2) COMMENT 'Ending value of the day',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (date)
);

-- 3. Table monthly_usage - total water + cost per month
CREATE TABLE IF NOT EXISTS monthly_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    year INT NOT NULL COMMENT 'Year',
    month INT NOT NULL COMMENT 'Month (1-12)',
    consumption DECIMAL(10, 2) NOT NULL DEFAULT 0 COMMENT 'Total water usage (m³)',
    cost DECIMAL(15, 2) NOT NULL DEFAULT 0 COMMENT 'Total cost (VND)',
    tier_1_usage DECIMAL(10, 2) DEFAULT 0 COMMENT '0-10m³ @ 5.973 VND/m³',
    tier_2_usage DECIMAL(10, 2) DEFAULT 0 COMMENT '10-20m³ @ 7.052 VND/m³',
    tier_3_usage DECIMAL(10, 2) DEFAULT 0 COMMENT '20-30m³ @ 8.669 VND/m³',
    tier_4_usage DECIMAL(10, 2) DEFAULT 0 COMMENT '>30m³ @ 15.929 VND/m³',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_month (year, month),
    INDEX idx_year_month (year, month)
);

-- 4. Table alerts - event alerts
CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL COMMENT 'Alert type (leak, high_consumption, device_offline, etc)',
    message VARCHAR(255) NOT NULL COMMENT 'Alert message',
    value_at_event DECIMAL(10, 2) COMMENT 'Value at the time of alert',
    timestamp BIGINT NOT NULL COMMENT 'Unix timestamp when event occurred',
    resolved BOOLEAN DEFAULT FALSE COMMENT 'Whether issue is resolved',
    resolved_at TIMESTAMP NULL COMMENT 'Time when resolved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_timestamp (timestamp),
    INDEX idx_resolved (resolved)
);

-- 5. Table rate_tiers - water rate by tier (for easy expansion)
CREATE TABLE IF NOT EXISTS rate_tiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tier_level INT NOT NULL COMMENT 'Tier level (1, 2, 3, 4)',
    from_m3 DECIMAL(10, 2) NOT NULL COMMENT 'From m³',
    to_m3 DECIMAL(10, 2) COMMENT 'To m³ (NULL = unlimited)',
    rate DECIMAL(10, 3) NOT NULL COMMENT 'Price VND/m³',
    UNIQUE KEY unique_tier (tier_level)
);

-- Insert current water rates
INSERT INTO rate_tiers (tier_level, from_m3, to_m3, rate) VALUES
(1, 0, 10, 5973),
(2, 10, 20, 7052),
(3, 20, 30, 8669),
(4, 30, NULL, 15929);

-- 6. Table device_status - device status tracking
CREATE TABLE IF NOT EXISTS device_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_name VARCHAR(100) NOT NULL,
    is_online BOOLEAN DEFAULT TRUE,
    last_reading_timestamp BIGINT,
    last_online_at TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_device (device_name)
);

-- Insert default device
INSERT INTO device_status (device_name) VALUES ('main');

COMMIT;
