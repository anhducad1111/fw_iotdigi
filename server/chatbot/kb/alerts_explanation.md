# Understanding System Alerts

The IoT Digi system monitors water flow continuously and triggers alerts when abnormal patterns are detected.

## Common Alert Types:

1.  **Leak Detection (continuous_flow)**:
    *   **Meaning**: Water has been flowing continuously for a long period (e.g., 6 hours) without stopping.
    *   **Potential Cause**: A leaking toilet flapper, a dripping faucet, or a hidden pipe leak.
    *   **Action**: Check all fixtures and toilets.

2.  **High Usage Spike (burst_pipe)**:
    *   **Meaning**: The flow rate suddenly exceeded the configured maximum threshold.
    *   **Potential Cause**: A burst pipe, a hose left running, or unusually high valid usage.
    *   **Action**: Inspect the property immediately for major leaks.

3.  **Device Offline**:
    *   **Meaning**: The server hasn't received data from the sensor for a set interval.
    *   **Potential Cause**: Power outage, Wi-Fi disconnection, or sensor malfunction.
