# IoT Digi - Multi-Tenancy Upgrade Plan

## Overview

This plan outlines the steps to upgrade the system from a single-device architecture to a multi-user/multi-device system.
**Core Principle**: 1 User = 1 Device.

## 1. Database Schema Changes (`server/db/database.sql`)

We will introduce a `users` table and link all data to specific users (devices).

### 1.1 New Table: `users`

- `id` (INT, Auto Increment): Primary Key. Acts as the internal `device_id` for data linking.
- `username` (VARCHAR): For web login.
- `password` (VARCHAR): Hashed password for web login.
- `role` (ENUM): `'admin'` or `'user'`.
- `api_key` (VARCHAR): Unique key used by the ESP32 to authenticate.
- `created_at` (TIMESTAMP).

### 1.2 Modify Existing Tables

Add `device_id` column (INT) to the following tables to partition data:

- `readings`
- `daily_usage`
- `monthly_usage`
- `alerts` (if applicable)

### 1.3 Data Migration Strategy

- **Existing Data**: All currently existing rows in `readings`, `daily_usage`, etc., will be updated to belong to a default "Legacy User".
- **Legacy User Profile**:
  - **API Key**: `123`
  - **Role**: `admin` (or `user` depending on preference, likely `admin` to see all).
  - **Status**: Active.

## 2. Backend Logic Update (`server/webhook.php`)

### 2.1 API Key Authentication

- **Current**: Checks against a hardcoded array `$ALLOWED_API_KEYS`.
- **New**:
  - Extract `HTTP_APIKEY` header.
  - Query `users` table to find the user where `api_key` matches.
  - If found: Retrieve `id` (which becomes our `device_id`) for insertion.
  - If not found: Reject request (403 Forbidden).

### 2.2 Binary Parsing

- **Remove**: Logic that extracts/parses the "name" field from the binary packet (as requested, we rely solely on API Key).
- **Update**: Ensure `device_id` is included in all `INSERT` statements to the database.

## 3. Frontend & Authentication

### 3.1 Login System

- Create `login.php`: Simple login form.
- Create `logout.php`: Destroy session.
- **Session Management**: Store `user_id`, `role`, and `username` in PHP Session upon successful login.

### 3.2 Access Control (RBAC)

- **Admin**: Can view data for all devices (or specific views).
- **User**: Can _only_ view data where `readings.device_id == $_SESSION['user_id']`.
- **Implementation**: Update `fetch_data.php`, `history.php`, and `get.php` to append `AND device_id = ?` to SQL queries based on the logged-in user's ID.

## 4. Execution Steps

1.  **Draft SQL**: Prepare the `ALTER TABLE` and `CREATE TABLE` statements.
2.  **Execute Migration**: Run SQL against `iotdigi_db`.
3.  **Update Webhook**: Modify PHP code to handle the new logic.
4.  **Implement Auth**: Add Login Page and protect API endpoints.
