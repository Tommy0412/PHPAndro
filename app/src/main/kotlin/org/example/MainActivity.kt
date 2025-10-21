package org.example

import android.content.Context
import android.os.Build
import android.os.Bundle
import android.util.Log
import android.webkit.WebResourceRequest
import android.webkit.WebSettings
import android.webkit.WebView
import android.webkit.WebViewClient
import androidx.appcompat.app.AppCompatActivity
import fi.iki.elonen.NanoHTTPD
import java.io.File
import java.io.FileOutputStream

class MainActivity : AppCompatActivity() {

    private val phpRunner = PhpRunner()
    private lateinit var server: NanoHTTPD

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        val phpDir = File(filesDir, "php").apply { mkdirs() }
        val tmpDir = File(phpDir, "tmp").apply { mkdirs() }
        val wwwDir = File(filesDir, "www").apply { mkdirs() }

        // --- Check app version ---
        val prefs = getSharedPreferences("app_info", Context.MODE_PRIVATE)
        val packageInfo = packageManager.getPackageInfo(packageName, 0)
        val currentVersion = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.P) {
            packageInfo.longVersionCode.toInt()
        } else {
            @Suppress("DEPRECATION")
            packageInfo.versionCode
        }
        val savedVersion = prefs.getInt("saved_version", -1)

        if (currentVersion != savedVersion) {
            // Version changed or first install → reset and copy assets
            phpDir.deleteRecursively()
            wwwDir.deleteRecursively()
            phpDir.mkdirs()
            wwwDir.mkdirs()

            copyAssetsToInternal("php", phpDir)
            copyAssetsToInternal("www", wwwDir)

            prefs.edit().putInt("saved_version", currentVersion).apply()
        }

        // --- Prepare php.ini ---
        val iniFile = File(phpDir, "php.ini")
        val phpIniContent = assets.open("php/php.ini").bufferedReader().use { it.readText() }
        val modifiedIni = phpIniContent
            .replace(
                Regex("^upload_tmp_dir\\s*=.*", RegexOption.MULTILINE),
                "upload_tmp_dir = \"${tmpDir.absolutePath}\""
            )
            .replace(
                Regex("^session.save_path\\s*=.*", RegexOption.MULTILINE),
                "session.save_path = \"${tmpDir.absolutePath}\""
            )
        iniFile.writeText(modifiedIni)

        // --- Start embedded PHP server ---
        server = object : NanoHTTPD(8080) {
            override fun serve(session: IHTTPSession): Response {
                val uri = session.uri.removePrefix("/")
                val method = session.method.name
                val query = session.queryParameterString ?: ""
                val body = if (session.method == Method.POST) {
                    session.inputStream.bufferedReader().use { it.readText() }
                } else {
                    ""
                }

                Log.d("PHPServer", "Serve: uri=$uri, method=$method, query=$query, bodyLength=${body.length}")

                val file = if (uri.isEmpty()) { val phpIndex = File(wwwDir, "index.php"); val htmlIndex = File(wwwDir, "index.html"); if (phpIndex.exists()) phpIndex else if (htmlIndex.exists()) htmlIndex else File(wwwDir, "index.php") } else File(wwwDir, uri)

                return if (file.exists()) {
                    if (file.extension.equals("php", ignoreCase = true)) {
                        val output = phpRunner.runPhpFile(
                            file.absolutePath,
                            iniFile.absolutePath,
                            method,
                            query,
                            body
                        )
                        newFixedLengthResponse(Response.Status.OK, "text/html; charset=utf-8", output)
                    } else {
                        val content = file.readBytes()
                        val mime = getMimeType(file.extension)
                        Log.d("PHPServer", "Serving static file: ${file.name}")
                        newFixedLengthResponse(
                            Response.Status.OK,
                            mime,
                            content.inputStream(),
                            content.size.toLong()
                        )
                    }
                } else {
                    Log.d("PHPServer", "File not found: ${file.absolutePath}")
                    newFixedLengthResponse(Response.Status.NOT_FOUND, "text/plain", "404 Not Found")
                }
            }
        }

        server.start()

        // --- Setup WebView ---
        val webView = WebView(this)
        setContentView(webView)

        webView.settings.apply {
            javaScriptEnabled = true
            domStorageEnabled = true
            allowFileAccess = true
            allowContentAccess = true
            mixedContentMode = WebSettings.MIXED_CONTENT_ALWAYS_ALLOW
            cacheMode = WebSettings.LOAD_DEFAULT
            builtInZoomControls = true
            displayZoomControls = false
            setSupportZoom(true)
        }

        webView.webViewClient = object : WebViewClient() {
            override fun shouldOverrideUrlLoading(view: WebView?, request: WebResourceRequest?): Boolean {
                request?.url?.let { view?.loadUrl(it.toString()) }
                return true
            }
        }

        webView.loadUrl("http://127.0.0.1:8080")
    }

    private fun copyAssetsToInternal(srcDir: String, destDir: File) {
        assets.list(srcDir)?.forEach { name ->
            val fullSrc = "$srcDir/$name"
            val outFile = File(destDir, name)
            if (assets.list(fullSrc)?.isNotEmpty() == true) {
                outFile.mkdirs()
                copyAssetsToInternal(fullSrc, outFile)
            } else {
                assets.open(fullSrc).use { input ->
                    FileOutputStream(outFile).use { output ->
                        input.copyTo(output)
                    }
                }
            }
        }
    }

    private fun getMimeType(ext: String): String = when (ext.lowercase()) {
        "html", "htm" -> "text/html"
        "js" -> "application/javascript"
        "json" -> "application/json"
        "css" -> "text/css"
        "png" -> "image/png"
        "jpg", "jpeg" -> "image/jpeg"
        "gif" -> "image/gif"
        "svg" -> "image/svg+xml"
        "ico" -> "image/x-icon"
        "woff" -> "font/woff"
        "woff2" -> "font/woff2"
        "ttf" -> "font/ttf"
        "eot" -> "application/vnd.ms-fontobject"
        "otf" -> "font/otf"
        "mp4" -> "video/mp4"
        "webm" -> "video/webm"
        "ogv" -> "video/ogg"
        "mp3" -> "audio/mpeg"
        "ogg" -> "audio/ogg"
        "wav" -> "audio/wav"
        "xml" -> "application/xml"
        "pdf" -> "application/pdf"
        "zip" -> "application/zip"
        "rar" -> "application/x-rar-compressed"
        "7z" -> "application/x-7z-compressed"
        else -> "text/plain"
    }

    override fun onDestroy() {
        super.onDestroy()
        server.stop()
    }
}