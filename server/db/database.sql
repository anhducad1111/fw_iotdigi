-- Create database iotdigi_db
CREATE DATABASE IF NOT EXISTS iotdigi_db;
USE iotdigi_db;

-- 1. Table users (New)
-- API Key serves as the Device ID linkage.
-- Admin users might not have an API Key.
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL COMMENT 'Bcrypt hash',
    full_name VARCHAR(100),
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    api_key VARCHAR(64) UNIQUE COMMENT 'Unique Identifier for the Device (Device ID). Null for admins without devices.',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Table readings - store all raw data from webhook
CREATE TABLE IF NOT EXISTS readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id VARCHAR(64) NOT NULL COMMENT 'Corresponds to users.api_key',
    value DECIMAL(10, 2) NOT NULL COMMENT 'Value in m³ from sensor',
    timestamp BIGINT NOT NULL COMMENT 'Unix timestamp',
    error_code INT DEFAULT 0 COMMENT 'Error code',
    error_message VARCHAR(255) DEFAULT NULL COMMENT 'Error message',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_device_timestamp (device_id, timestamp),
    INDEX idx_created_at (created_at)
);


-- 3. Table daily_usage - total water consumption per day per device
CREATE TABLE IF NOT EXISTS daily_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id VARCHAR(64) NOT NULL COMMENT 'Corresponds to users.api_key',
    date DATE NOT NULL COMMENT 'Date',
    consumption DECIMAL(10, 2) NOT NULL DEFAULT 0 COMMENT 'Total water usage (m³)',
    start_value DECIMAL(10, 2) COMMENT 'Starting value of the day',
    end_value DECIMAL(10, 2) COMMENT 'Ending value of the day',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date_device (date, device_id)
);


-- 4. Table monthly_usage - total water + cost per month per device
CREATE TABLE IF NOT EXISTS monthly_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id VARCHAR(64) NOT NULL COMMENT 'Corresponds to users.api_key',
    year INT NOT NULL COMMENT 'Year',
    month INT NOT NULL COMMENT 'Month (1-12)',
    consumption DECIMAL(10, 2) NOT NULL DEFAULT 0 COMMENT 'Total water usage (m³)',
    cost DECIMAL(15, 2) NOT NULL DEFAULT 0 COMMENT 'Total cost (VND)',
    tier_1_usage DECIMAL(10, 2) DEFAULT 0 COMMENT '0-10m³ @ 5.973 VND/m³',
    tier_2_usage DECIMAL(10, 2) DEFAULT 0 COMMENT '10-20m³ @ 7.052 VND/m³',
    tier_3_usage DECIMAL(10, 2) DEFAULT 0 COMMENT '20-30m³ @ 8.669 VND/m³',
    tier_4_usage DECIMAL(10, 2) DEFAULT 0 COMMENT '>30m³ @ 15.929 VND/m³',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_month_device (year, month, device_id)
);


-- 5. Table alerts - event alerts
CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id VARCHAR(64) NOT NULL COMMENT 'Corresponds to users.api_key',
    type VARCHAR(50) NOT NULL COMMENT 'Alert type',
    message VARCHAR(255) NOT NULL COMMENT 'Alert message',
    value_at_event DECIMAL(10, 2) COMMENT 'Value at the time of alert',
    timestamp BIGINT NOT NULL COMMENT 'Unix timestamp when event occurred',
    resolved BOOLEAN DEFAULT FALSE COMMENT 'Whether issue is resolved',
    resolved_at TIMESTAMP NULL COMMENT 'Time when resolved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_device_type (device_id, type),
    INDEX idx_resolved (resolved)
);


-- 6. Table rate_tiers - water rate by tier
CREATE TABLE IF NOT EXISTS rate_tiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tier_level INT NOT NULL COMMENT 'Tier level (1, 2, 3, 4)',
    from_m3 DECIMAL(10, 2) NOT NULL COMMENT 'From m³',
    to_m3 DECIMAL(10, 2) COMMENT 'To m³ (NULL = unlimited)',
    rate DECIMAL(10, 3) NOT NULL COMMENT 'Price VND/m³',
    UNIQUE KEY unique_tier (tier_level)
);

-- Insert current water rates (Use IGNORE to prevent error if already exists)
INSERT IGNORE INTO rate_tiers (tier_level, from_m3, to_m3, rate) VALUES
(1, 0, 10, 5973),
(2, 10, 20, 7052),
(3, 20, 30, 8669),
(4, 30, NULL, 15929);


-- 7. Table device_status - device status tracking
CREATE TABLE IF NOT EXISTS device_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id VARCHAR(64) NOT NULL,
    is_online BOOLEAN DEFAULT TRUE,
    last_reading_timestamp BIGINT,
    last_online_at TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_device (device_id)
);

COMMIT;
