plugins {
    id("com.android.application") version "8.13.0"
    id("org.jetbrains.kotlin.android") version "2.1.10"
}

android {
    namespace = "com.tommy0412.phpandro"
    compileSdk = 35  // Android 15
    ndkVersion = "27.0.12077973"

    defaultConfig {
        applicationId = "com.tommy0412.phpandro"
        minSdk = 28  // Android 9+
        targetSdk = 35  // Android 15
        versionCode = 1
        versionName = "1.0"

        ndk {
            abiFilters += listOf("armeabi-v7a", "arm64-v8a", "x86_64")
        }

        // CMake configuration for Android 15
        externalNativeBuild {
            cmake {
                arguments += "-DANDROID_STL=c++_shared"
                arguments += "-DANDROID_PLATFORM=android-28"
                arguments += "-DPACKAGE_NAME=${namespace?.replace(".", "/")}"
            }
        }
    }

    splits {
        abi {
            isEnable = true
            reset()
            include("armeabi-v7a", "arm64-v8a", "x86_64")
            isUniversalApk = true
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    kotlinOptions {
        jvmTarget = "17"
    }

    buildTypes {
        release {
            isMinifyEnabled = true
            isShrinkResources = true
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
            // Optimize for smaller APK
            isDebuggable = false
            isJniDebuggable = false
        }
        debug {
            isMinifyEnabled = false
            isJniDebuggable = true
        }
    }

    // Disable lint for release builds
    lint {
        checkReleaseBuilds = false
        abortOnError = false
    }

    // Packaging options for Android 15
    packaging {
        resources {
            excludes += "/META-INF/{AL2.0,LGPL2.1}"
            excludes += "/META-INF/DEPENDENCIES"
            excludes += "/META-INF/LICENSE"
            excludes += "/META-INF/LICENSE.txt"
            excludes += "/META-INF/NOTICE"
            excludes += "/META-INF/NOTICE.txt"
            excludes += "/META-INF/INDEX.LIST"
            excludes += "/META-INF/services/*"
        }
        jniLibs {
            useLegacyPackaging = false
        }
    }

    externalNativeBuild {
        cmake {
            path = file("src/main/cpp/CMakeLists.txt")
        }
    }

    // Build features
    buildFeatures {
        buildConfig = true
    }
}

dependencies {
    // AndroidX Core - Android 15 optimized
    implementation("androidx.core:core-ktx:1.15.0")
    implementation("androidx.appcompat:appcompat:1.7.0")
    implementation("androidx.activity:activity-ktx:1.9.3")
    implementation("com.google.android.material:material:1.12.0")
    implementation("org.jetbrains.kotlin:kotlin-stdlib:2.1.10")
    
    // AndroidX lifecycle for better activity management
    implementation("androidx.lifecycle:lifecycle-runtime-ktx:2.8.7")
    implementation("androidx.lifecycle:lifecycle-viewmodel-ktx:2.8.7")
    
    // Servlet API (required for Jetty)
    implementation("javax.servlet:javax.servlet-api:4.0.1")
    
    // Jetty for embedded server - updated to compatible version
    implementation("org.eclipse.jetty:jetty-server:9.4.56.v20240826")
    implementation("org.eclipse.jetty:jetty-servlet:9.4.56.v20240826")
    implementation("org.eclipse.jetty:jetty-util:9.4.56.v20240826")
    implementation("org.eclipse.jetty:jetty-http:9.4.56.v20240826")
    implementation("org.eclipse.jetty:jetty-io:9.4.56.v20240826")
}