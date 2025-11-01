plugins {
    id("com.android.application") version "8.5.1"
    
    id("org.jetbrains.kotlin.android") version "1.9.23"
}

android {
    namespace = "com.tommy0412.phpandro"
    compileSdk = 34 
	ndkVersion = "27.0.12077973"

    defaultConfig {
        applicationId = "com.tommy0412.phpandro"
        minSdk = 28 
        targetSdk = 34
        versionCode = 1
        versionName = "1.0"
        
        ndk {
			abiFilters.addAll(listOf("arm64-v8a"))
        }
    }
	
    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_1_8
        targetCompatibility = JavaVersion.VERSION_1_8
    }

    kotlinOptions {
        jvmTarget = "1.8"
    }
	
    buildTypes {
        release {
            isMinifyEnabled = false
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
        }
    }

    externalNativeBuild {
        cmake {
            path = file("src/main/cpp/CMakeLists.txt")
        }
    }
}

dependencies {
    implementation("androidx.core:core-ktx:1.13.1")
    implementation("androidx.appcompat:appcompat:1.6.1")
	implementation("com.google.android.material:material:1.11.0")
	implementation("org.jetbrains.kotlin:kotlin-stdlib:1.9.0")
	implementation("org.nanohttpd:nanohttpd:2.3.1")
}