#include "WifiCam.hpp"
#include <WiFi.h>
#include "config.hpp"

WifiCam::WifiCam()
  : server(80) {}

void
WifiCam::init() {
  Serial.begin(115200);
  Serial.println();
  delay(1000);

  WiFi.persistent(false);
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASS);
  if (WiFi.waitForConnectResult() != WL_CONNECTED) {
    Serial.printf("WiFi failure %d\n", WiFi.status());
    delay(5000);
    ESP.restart();
  }
  Serial.println("WiFi connected");
  delay(1000);

  if (!camera.init()) {
    Serial.println("camera initialize failure");
    delay(5000);
    ESP.restart();
  }
  Serial.println("camera initialize success");

  pinMode(LED_GPIO_NUM, OUTPUT);
  analogWrite(LED_GPIO_NUM, 10);  // 3% brightness

  Serial.println("camera starting");
  Serial.print("http://");
  Serial.println(WiFi.localIP());
  Serial.println("/640x480.jpg");

  addRequestHandlers();
  server.begin();
}

void
WifiCam::handleClient() {
  server.handleClient();

  static unsigned long lastPrint = 0;
  if (millis() - lastPrint >= 3000) {
    lastPrint = millis();
    Serial.printf("Free heap: %d bytes, Free PSRAM: %d bytes\n", ESP.getFreeHeap(), ESP.getFreePsram());
  }
}

void
WifiCam::addRequestHandlers() {
  server.on("/640x480.jpg", HTTP_GET, [this](){ serveJPG(); });
}

void
WifiCam::serveJPG() {
  camera_fb_t * fb = camera.capture();
  if (!fb) {
    Serial.println("capture() failure");
    server.send(500, "text/plain", "still capture error\n");
    return;
  }
  Serial.printf("capture() success: %dx%d %ub\n", fb->width, fb->height, fb->len);

  WiFiClient client = server.client();
  server.setContentLength(fb->len);
  server.send(200, "image/jpeg");
  client.write(fb->buf, fb->len);
  camera.returnFrame(fb);
}