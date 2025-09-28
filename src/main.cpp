#include "WifiCam.hpp"
#include <WiFi.h>

static const char* WIFI_SSID = "duc";
static const char* WIFI_PASS = "11111111";

esp32cam::Resolution initialResolution;

WebServer server(80);

void
setup() {
  Serial.begin(115200);
  Serial.println();
  esp32cam::setLogger(Serial);
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

  {
    using namespace esp32cam;

    initialResolution = Resolution::find(640, 480);

    Config cfg;
    cfg.setPins(pins::AiThinker);
    cfg.setResolution(initialResolution);
    cfg.setJpeg(50);

    bool ok = Camera.begin(cfg);
    if (!ok) {
      Serial.println("camera initialize failure");
      delay(5000);
      ESP.restart();
    }
    Serial.println("camera initialize success");
  }

  pinMode(4, OUTPUT);
  analogWrite(4, 10);  // 3% brightness

  Serial.println("camera starting");
  Serial.print("http://");
  Serial.println(WiFi.localIP());
  Serial.println("/640x480.jpg");

  addRequestHandlers();
  server.begin();
}

void
loop() {
  server.handleClient();

  static unsigned long lastPrint = 0;
  if (millis() - lastPrint >= 3000) {
    lastPrint = millis();
    Serial.printf("Free heap: %d bytes, Free PSRAM: %d bytes\n", ESP.getFreeHeap(), ESP.getFreePsram());
  }
}
