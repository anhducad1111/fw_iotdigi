#ifndef WIFICAM_HPP
#define WIFICAM_HPP

#include <WebServer.h>
#include <esp_camera.h>

class WifiCam {
public:
  WifiCam();

  void init();

  void handleClient();

private:
  WebServer server;

  void addRequestHandlers();

  void serveJPG();
};

#endif // WIFICAM_HPP
