FILE(GLOB_RECURSE app_sources ${CMAKE_CURRENT_SOURCE_DIR}/*.*)

idf_component_register(SRCS ${app_sources}
                    INCLUDE_DIRS "."
                    REQUIRES esp_timer esp_wifi ad_tfliteclass ad_helper ad_controlcamera ad_webhook ad_fileserver_ota ad_image_proc ad_wlan openmetrics esp_http_server)


