cmake_minimum_required(VERSION 3.22.1)
project("php_jni_wrapper")

add_library(native-lib SHARED native-lib.cpp)

target_include_directories(native-lib PRIVATE
    ${CMAKE_SOURCE_DIR}/../../includes/php
    ${CMAKE_SOURCE_DIR}/../../includes/php/main
    ${CMAKE_SOURCE_DIR}/../../includes/php/Zend
    ${CMAKE_SOURCE_DIR}/../../includes/php/TSRM
    ${CMAKE_SOURCE_DIR}/../../includes/php/sapi/embed
)

set(PHP_LIB_DIR ${CMAKE_SOURCE_DIR}/../jniLibs/${ANDROID_ABI})

add_library(php SHARED IMPORTED)
set_target_properties(php PROPERTIES IMPORTED_LOCATION
    ${PHP_LIB_DIR}/libphp.so
)

find_library(log-lib log)

target_link_libraries(native-lib  
    ${log-lib}
    php
    android
	atomic
	m
	c
)
