#include <Arduino.h>
#include <freertos/FreeRTOS.h>
#include <freertos/task.h>
#include "WifiCam.hpp"

WifiCam wifiCam;

void wifiCamTask(void *pvParameters) {
  while (true) {
    wifiCam.handleClient();
    vTaskDelay(10 / portTICK_PERIOD_MS); // Small delay to yield
  }
}

void
setup() {
  wifiCam.init();

  // Create a FreeRTOS task for handling clients
  xTaskCreate(
    wifiCamTask,    // Function to implement the task
    "WifiCamTask",  // Name of the task
    4096,           // Stack size in words
    NULL,           // Task input parameter
    1,              // Priority of the task
    NULL            // Task handle
  );
}

void
loop() {
  // Loop can be empty since we're using tasks
  vTaskDelay(1000 / portTICK_PERIOD_MS); // Delay to prevent watchdog
}
