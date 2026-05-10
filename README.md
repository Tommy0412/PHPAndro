# PHPAndro
![Project Screenshot](./image.png)
PHPAndro is an Android application that embeds a fully functional PHP server directly into your app, allowing you to run PHP scripts locally on your device. It uses Eclipse Jetty as the embedded HTTP server and supports both .php and static files (.html, .css, .js, images, etc.), enabling a seamless PHP development and testing environment on Android.

##  Key Features
- **Run PHP scripts locally** – full support for GET, POST, PUT, and DELETE requests.
- **Serve static files** – HTML, CSS, JavaScript, images, and more with proper MIME type detection.
- **Dynamic PHP environment** – uses `php.ini` with configurable settings (upload_tmp_dir, session.save_path, etc.).
- **Embedded web server** – powered by **Eclipse Jetty**, providing stable localhost access (`http://127.0.0.1:8080`).
- **.htaccess Support** – includes support for `DirectoryIndex`, `ErrorDocument`, and `RewriteRule`.
- **WebView integration** – smooth preview with modern navigation and JS alert handling.
- **Automatic asset management** – copies and updates PHP and web files on app version changes.
- **Dynamic JNI Binding** – no manual C++ edits required; automatically adapts to your project's package name.
- **Android 15 Optimized** – includes edge-to-edge support and modern background threading with Coroutines.

## Use Cases
- Mobile PHP development and testing.
- Offline PHP web applications on Android.
- Learning and experimenting with PHP on the go.

## Requirements
- Android Studio
- JDK 17
- Android SDK (Target 35)
- Android NDK (27+)
- CMake

## Setup
1. **Clone this repository**
2. **Open project in Android Studio**
3. **Customize your web files**: Place your PHP/HTML files in `app/src/main/assets/www/`
4. **Build and run the app**: The JNI layer and package name binding are handled automatically via Gradle and CMake.

## Project Structure
```
app/src/main/assets/
├── php/           # PHP core files, extensions, and php.ini
└── www/           # Your web root (.php, .html, .htaccess, assets)
    ├── index.php
    ├── .htaccess
    ├── css/
    └── js/
```

## Features
- **PHP 8.4** with core extensions.
- **Eclipse Jetty** web server for high performance and reliability.
- **Dynamic Package Support**: Change the `namespace` in `build.gradle.kts` and everything "just works".
- **Coroutine-based startup**: No UI freezes during server initialization.

## Contributing
This is a work in progress. Contributors are welcome.

## Donate
[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/embedsito)
