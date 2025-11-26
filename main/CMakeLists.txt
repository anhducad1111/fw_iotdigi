
FILE(GLOB_RECURSE app_sources ${CMAKE_SOURCE_DIR}/main/*.*)

# idf_component_register(SRCS ${app_sources})

idf_component_register(SRCS ${app_sources}
                    INCLUDE_DIRS "."
                    REQUIRES esp_psram esp_http_server esp-tls jomjol_flowcontrol)