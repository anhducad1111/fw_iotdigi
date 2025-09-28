#include "WifiCam.hpp"

WifiCam wifiCam;

void
setup() {
  wifiCam.init();
}

void
loop() {
  wifiCam.handleClient();
}
