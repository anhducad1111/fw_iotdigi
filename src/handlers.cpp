#include "WifiCam.hpp"
#include <WiFi.h>

static void
serveJPG() {
  auto frame = esp32cam::capture();
  if (frame == nullptr) {
    Serial.println("capture() failure");
    server.send(500, "text/plain", "still capture error\n");
    return;
  }
  Serial.printf("capture() success: %dx%d %zub\n", frame->getWidth(), frame->getHeight(),
                frame->size());

  WiFiClient client = server.client();
  server.setContentLength(frame->size());
  server.send(200, "image/jpeg");
  frame->writeTo(client);
}

void
addRequestHandlers() {
  server.on("/640x480.jpg", HTTP_GET, serveJPG);
}
