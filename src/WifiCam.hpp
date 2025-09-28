#ifndef WIFICAM_HPP
#define WIFICAM_HPP

#include <WebServer.h>
#include "camera.hpp"

class WifiCam {
public:
  WifiCam();

  void init();

  void handleClient();

private:
  WebServer server;
  Camera camera;

  void addRequestHandlers();

  void serveJPG();
};

#endif // WIFICAM_HPP
