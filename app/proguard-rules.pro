# Add project specific ProGuard rules here.
# Android 15 optimized ProGuard rules for PHPAndro

# Keep native methods (required for JNI)
-keepclasseswithmembernames class * {
    native <methods>;
}

# Keep PHP Runner class
-keep class com.tommy0412.phpandro.PhpRunner {
    *;
}

# Keep MainActivity
-keep class com.tommy0412.phpandro.MainActivity {
    *;
}

# Keep ApacheConfig data class
-keep class com.tommy0412.phpandro.ApacheConfig {
    *;
}

# Keep JNI methods
-keepclasseswithmembers class * {
    public static native <methods>;
}

# Keep native-lib exports
-keep class * {
    public static native <methods>;
}

# Jetty rules - keep required classes for embedded server
-keep class org.eclipse.jetty.** {
    *;
}

# Keep Servlet API
-keep class javax.servlet.** {
    *;
}

# Keep Kotlin metadata
-keep class kotlin.Metadata {
    *;
}

# Keep Kotlin coroutines
-keepnames class kotlinx.coroutines.internal.MainDispatcherFactory {}
-keepnames class kotlinx.coroutines.CoroutineExceptionHandler {}

# Keep AndroidX classes
-keep class androidx.** {
    *;
}

# Keep Material components
-keep class com.google.android.material.** {
    *;
}

# Keep WebView related classes
-keepclassmembers class * {
    @android.webkit.JavascriptInterface <methods>;
}

# Keep JavaScript interface
-keepattributes JavascriptInterface
-keepattributes *Annotation*

# Keep R class
-keepclassmembers class **.R$* {
    public static <fields>;
}

# Keep enum
-keepclassmembers enum * {
    public static **[] values();
    public static ** valueOf(java.lang.String);
}

# Keep Parcelable
-keep class * implements android.os.Parcelable {
    public static final android.os.Parcelable$Creator *;
}

# Keep Serializable
-keepclassmembers class * implements java.io.Serializable {
    static final long serialVersionUID;
    private static final java.io.ObjectStreamField[] serialPersistentFields;
    private void writeObject(java.io.ObjectOutputStream);
    private void readObject(java.io.ObjectInputStream);
    java.lang.Object writeReplace();
    java.lang.Object readResolve();
}

# Optimize for smaller APK size
-assumenosideeffects class android.util.Log {
    public static *** d(...);
    public static *** v(...);
}

# Allow access to package/protected methods
-allowaccessmodification

# Dontwarn for optional dependencies
-dontwarn org.eclipse.jetty.util.**
-dontwarn org.eclipse.jetty.jmx.**
-dontwarn org.eclipse.jetty.io.jmx.**
-dontwarn org.eclipse.jetty.server.session.**
-dontwarn org.ietf.jgss.**
-dontwarn javax.security.auth.**
-dontwarn javax.naming.**
-dontwarn javax.servlet.**
-dontwarn java.beans.**
-dontwarn java.lang.management.**

# Keep signature attribute (required for JNI)
-keepattributes Signature

# Keep Inner classes
-keepclassmembers,allowshrinking,allowobfuscation class * {
    <fields>;
    <methods>;
}
