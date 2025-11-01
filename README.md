# PHPAndro
![Project Screenshot](./image.png)
PHPAndro is an Android application that embeds a fully functional PHP server directly into your app, allowing you to run PHP scripts locally on your device. It uses NanoHTTPD as the lightweight HTTP server and supports both .php and static files (.html, .css, .js, images, etc.), enabling a seamless PHP development and testing environment on Android.

##  Key Features
- Run PHP scripts locally – full support for GET and POST requests.

- Serve static files – HTML, CSS, JavaScript, images, and more.

- Dynamic PHP environment – uses php.ini with configurable settings.

- Embedded web server – powered by NanoHTTPD, providing localhost access (http://127.0.0.1:8080).

- WebView integration – easily preview your PHP/HTML content inside the app.

- Automatic asset management – copies and updates PHP and web files on app version changes.

- Cross-platform PHP functionality – works entirely on Android, no external server required.

## Use Cases
- Mobile PHP development and testing.

- Offline PHP web applications on Android.

- Learning and experimenting with PHP on the go.

## Requirements

- Android Studio
- JDK 17
- Android SDK
- Android NDK
- CMake

## Setup

1. Clone this repository
2. Open project in Android Studio
3. Place your PHP/HTML files in `app/src/main/assets/www/`
4. Change package name and update JNI function name in \app\src\main\cpp\native-lib.cpp (if your app is com.myapp jni function must be Java_com_myapp_PhpRunner_runPhpFile
6. Build and run the app

## Project Structure
app/src/main/assets/www/
├── index.php
├── index.html
├── css/
└── js/


## Features

- PHP 8.4 with 21. extensions
- NanoHTTPD web server
- Easy file placement in assets folder

## Contributing

This is a work in progress. Contributors are welcome.

## Donate

[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/embedsito)
