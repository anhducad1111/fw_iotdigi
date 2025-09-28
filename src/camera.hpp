#ifndef CAMERA_HPP
#define CAMERA_HPP

#include <esp_camera.h>
#include "config.hpp"

class Camera {
public:
  bool init();

  camera_fb_t* capture();

  void returnFrame(camera_fb_t* fb);
};

#endif // CAMERA_HPP