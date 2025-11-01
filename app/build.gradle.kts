plugins {
    id("com.android.application") version "8.13.0"
    
    id("org.jetbrains.kotlin.android") version "2.1.10"
}

android {
    namespace = "com.tommy0412.phpandro"
    compileSdk = 36 
	ndkVersion = "27.0.12077973"

    defaultConfig {
        applicationId = "com.tommy0412.phpandro"
        minSdk = 28 
        targetSdk = 36
        versionCode = 1
        versionName = "1.0"
        
        ndk {
			abiFilters.addAll(listOf("arm64-v8a"))
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
    implementation("androidx.core:core-ktx:1.17.0")
    implementation("androidx.appcompat:appcompat:1.7.1")
	implementation("com.google.android.material:material:1.13.0")
	implementation("org.jetbrains.kotlin:kotlin-stdlib:2.1.10")
	implementation("org.nanohttpd:nanohttpd:2.3.1")
}