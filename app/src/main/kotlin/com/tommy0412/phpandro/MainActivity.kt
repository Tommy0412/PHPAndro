package com.tommy0412.phpandro

import android.app.AlertDialog
import android.content.Context
import android.os.Build
import android.os.Bundle
import android.util.Log
import android.view.View
import android.webkit.*
import androidx.activity.OnBackPressedCallback
import androidx.activity.enableEdgeToEdge
import androidx.appcompat.app.AppCompatActivity
import androidx.lifecycle.lifecycleScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import org.eclipse.jetty.server.Server
import org.eclipse.jetty.servlet.ServletContextHandler
import org.eclipse.jetty.servlet.ServletHolder
import java.io.File
import java.io.FileOutputStream
import java.net.URLConnection
import javax.servlet.http.HttpServlet
import javax.servlet.http.HttpServletRequest
import javax.servlet.http.HttpServletResponse

data class ApacheConfig(
    var directoryIndex: List<String> = listOf("index.php", "index.html", "index.htm"),
    var errorDocuments: Map<Int, String> = emptyMap(),
    var rewriteRules: List<Pair<Regex, String>> = emptyList()
)

class MainActivity : AppCompatActivity() {

    private val phpRunner = PhpRunner()
    private lateinit var wwwDir: File
    private lateinit var phpDir: File
    private lateinit var tmpDir: File
    private lateinit var iniFile: File
    
    private lateinit var webView: WebView
    private lateinit var loadingLayout: View
    
    private var server: Server? = null
    private var apacheConfig = ApacheConfig()

    override fun onCreate(savedInstanceState: Bundle?) {
        enableEdgeToEdge()
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        webView = findViewById(R.id.webView)
        loadingLayout = findViewById(R.id.loadingLayout)

        setupWebView()
        handleBackNavigation()

        lifecycleScope.launch(Dispatchers.IO) {
            initEnvironment()
            startJettyServer()
            
            withContext(Dispatchers.Main) {
                loadingLayout.visibility = View.GONE
                webView.visibility = View.VISIBLE
                webView.loadUrl("http://127.0.0.1:8080/")
            }
        }
    }

    private fun handleBackNavigation() {
        onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
            override fun handleOnBackPressed() {
                if (webView.canGoBack()) {
                    webView.goBack()
                } else {
                    isEnabled = false
                    onBackPressedDispatcher.onBackPressed()
                }
            }
        })
    }

    private fun initEnvironment() {
        phpDir = File(filesDir, "php").apply { mkdirs() }
        tmpDir = File(phpDir, "tmp").apply { mkdirs() }
        wwwDir = File(filesDir, "www").apply { mkdirs() }

        val prefs = getSharedPreferences("app_info", Context.MODE_PRIVATE)
        val packageInfo = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            packageManager.getPackageInfo(packageName, android.content.pm.PackageManager.PackageInfoFlags.of(0L))
        } else {
            @Suppress("DEPRECATION") packageManager.getPackageInfo(packageName, 0)
        }
        val currentVersion = packageInfo.longVersionCode.toInt()
        val savedVersion = prefs.getInt("saved_version", -1)

        if (currentVersion != savedVersion) {
            Log.i("MainActivity", "Version changed, updating assets...")
            phpDir.deleteRecursively()
            wwwDir.deleteRecursively()
            phpDir.mkdirs()
            wwwDir.mkdirs()
            copyAssetsToInternal("php", phpDir)
            copyAssetsToInternal("www", wwwDir)
            prefs.edit().putInt("saved_version", currentVersion).apply()
        }

        apacheConfig = loadHtaccess(wwwDir)

        iniFile = File(phpDir, "php.ini")
        try {
            val phpIniContent = assets.open("php/php.ini").bufferedReader().use { it.readText() }
            val modifiedIni = phpIniContent
                .replace(Regex("^upload_tmp_dir\\s*=.*", RegexOption.MULTILINE), "upload_tmp_dir = \"${tmpDir.absolutePath}\"")
                .replace(Regex("^session.save_path\\s*=.*", RegexOption.MULTILINE), "session.save_path = \"${tmpDir.absolutePath}\"")
                .replace(Regex("^sys_temp_dir\\s*=.*", RegexOption.MULTILINE), "sys_temp_dir = \"${tmpDir.absolutePath}\"")
            iniFile.writeText(modifiedIni)
        } catch (e: Exception) {
            Log.e("MainActivity", "Failed to prepare php.ini", e)
        }
    }

    private fun setupWebView() {
        webView.settings.apply {
            javaScriptEnabled = true
            domStorageEnabled = true
            allowFileAccess = false
            allowContentAccess = false
            mixedContentMode = WebSettings.MIXED_CONTENT_ALWAYS_ALLOW
            cacheMode = WebSettings.LOAD_DEFAULT
            builtInZoomControls = true
            displayZoomControls = false
            setSupportZoom(true)
            databaseEnabled = false
            setGeolocationEnabled(false)
            mediaPlaybackRequiresUserGesture = true
        }

        webView.webViewClient = object : WebViewClient() {
            override fun shouldOverrideUrlLoading(view: WebView?, request: WebResourceRequest?): Boolean {
                return false // Allow all links to load within the WebView
            }
        }

        webView.webChromeClient = object : WebChromeClient() {
            override fun onJsAlert(view: WebView?, url: String?, message: String?, result: JsResult?): Boolean {
                AlertDialog.Builder(view?.context ?: this@MainActivity)
                    .setMessage(message)
                    .setPositiveButton(android.R.string.ok) { _, _ -> result?.confirm() }
                    .setCancelable(false).create().show()
                return true
            }
        }
    }

    private fun loadHtaccess(dir: File): ApacheConfig {
        val config = ApacheConfig()
        val htaccess = File(dir, ".htaccess")
        if (!htaccess.exists()) return config

        try {
            htaccess.readLines().forEach { line ->
                val trimmed = line.trim()
                if (trimmed.isEmpty() || trimmed.startsWith("#")) return@forEach
                
                when {
                    trimmed.startsWith("DirectoryIndex", ignoreCase = true) -> {
                        config.directoryIndex = trimmed.substringAfter("DirectoryIndex").trim()
                            .split("\\s+".toRegex()).filter { it.isNotEmpty() }
                    }
                    trimmed.startsWith("ErrorDocument", ignoreCase = true) -> {
                        val parts = trimmed.substringAfter("ErrorDocument").trim().split("\\s+".toRegex(), 2)
                        if (parts.size == 2) parts[0].toIntOrNull()?.let { code ->
                            config.errorDocuments = config.errorDocuments + (code to parts[1].trim('"'))
                        }
                    }
                    trimmed.startsWith("RewriteRule", ignoreCase = true) -> {
                        val parts = trimmed.substringAfter("RewriteRule").trim().split("\\s+".toRegex())
                        if (parts.size >= 2) {
                            val pattern = parts[0]
                            val replacement = parts[1]
                            try { config.rewriteRules += Regex(pattern) to replacement } 
                            catch (e: Exception) { Log.w("ApacheConfig", "Invalid rewrite rule: $pattern") }
                        }
                    }
                }
            }
        } catch (e: Exception) {
            Log.e("ApacheConfig", "Error reading .htaccess", e)
        }
        return config
    }

    private fun startJettyServer() {
        try {
            server = Server(8080)
            val context = ServletContextHandler(ServletContextHandler.SESSIONS)
            context.contextPath = "/"
            server?.handler = context
            context.addServlet(ServletHolder(PhpServlet()), "/*")
            server?.start()
            Log.d("JettyServer", "Server started on port 8080")
        } catch (e: Exception) {
            Log.e("JettyServer", "Failed to start server", e)
        }
    }

    private fun copyAssetsToInternal(src: String, dest: File) {
        assets.list(src)?.forEach { name ->
            val srcPath = if (src.isEmpty()) name else "$src/$name"
            val outFile = File(dest, name)
            if (assets.list(srcPath)?.isNotEmpty() == true) {
                outFile.mkdirs()
                copyAssetsToInternal(srcPath, outFile)
            } else {
                assets.open(srcPath).use { inp -> 
                    FileOutputStream(outFile).use { out -> inp.copyTo(out) } 
                }
            }
        }
    }

    inner class PhpServlet : HttpServlet() {

        override fun service(req: HttpServletRequest, resp: HttpServletResponse) {
            try {
                process(req, resp)
            } catch (e: Exception) {
                Log.e("PhpServlet", "Error processing request", e)
                sendError(resp, 500, "Internal Server Error", e.message ?: "Unknown error")
            }
        }

        private fun process(req: HttpServletRequest, resp: HttpServletResponse) {
            val uri = req.requestURI?.removePrefix("/") ?: ""
            val method = req.method
            val query = req.queryString ?: ""
            val body = if (method == "POST" || method == "PUT") req.reader.readText() else ""

            // Apply Rewrite Rules
            var path = uri
            for ((pat, repl) in apacheConfig.rewriteRules) {
                if (pat.containsMatchIn(uri)) {
                    path = pat.replace(uri, repl)
                    break
                }
            }

            val file = File(wwwDir, path)
            val actual = if (file.isDirectory || path.isEmpty()) findIndex(file) else file

            if (actual.exists() && !actual.isDirectory) {
                // Path Traversal Check
                if (!actual.canonicalPath.startsWith(wwwDir.canonicalPath)) {
                    sendError(resp, 403, "Forbidden", "Access denied")
                    return
                }

                if (actual.extension.equals("php", ignoreCase = true)) {
                    executePhp(actual, method, query, body, resp)
                } else {
                    serveStatic(actual, resp)
                }
            } else {
                sendError(resp, 404, "Not Found", "The requested URL /$uri was not found on this server.")
            }
        }

        private fun findIndex(dir: File): File {
            for (idx in apacheConfig.directoryIndex) {
                val f = File(dir, idx)
                if (f.exists() && !f.isDirectory) return f
            }
            return dir
        }

        private fun executePhp(file: File, method: String, query: String, body: String, resp: HttpServletResponse) {
            try {
                val out = phpRunner.runPhpFile(file.absolutePath, iniFile.absolutePath, method, query, body)
                resp.contentType = "text/html; charset=utf-8"
                resp.status = HttpServletResponse.SC_OK
                resp.writer.write(out)
            } catch (e: Exception) {
                Log.e("PhpServlet", "PHP execution error", e)
                sendError(resp, 500, "PHP Execution Error", e.message ?: "Unknown error")
            }
        }

        private fun serveStatic(file: File, resp: HttpServletResponse) {
            try {
                val contentType = getMimeType(file)
                resp.contentType = contentType
                resp.status = HttpServletResponse.SC_OK
                resp.setContentLengthLong(file.length())
                resp.setHeader("Cache-Control", "public, max-age=86400")
                file.inputStream().use { it.copyTo(resp.outputStream) }
            } catch (e: Exception) {
                Log.e("PhpServlet", "Error serving static file", e)
                sendError(resp, 500, "Internal Server Error", "Failed to serve static file")
            }
        }

        private fun getMimeType(file: File): String {
            val extension = file.extension.lowercase()
            return MimeTypeMap.getSingleton().getMimeTypeFromExtension(extension)
                ?: URLConnection.guessContentTypeFromName(file.name)
                ?: when (extension) {
                    "php" -> "text/html"
                    "wasm" -> "application/wasm"
                    else -> "application/octet-stream"
                }
        }

        private fun sendError(resp: HttpServletResponse, code: Int, title: String, msg: String) {
            val errPath = apacheConfig.errorDocuments[code]
            if (errPath != null) {
                val f = File(wwwDir, errPath.removePrefix("/"))
                if (f.exists()) {
                    if (f.extension.equals("php", ignoreCase = true)) {
                        executePhp(f, "GET", "error=$code", "", resp)
                    } else {
                        serveStatic(f, resp)
                    }
                    resp.status = code
                    return
                }
            }
            
            val html = """
                <!DOCTYPE html>
                <html>
                <head><title>$code $title</title></head>
                <body style="font-family: sans-serif; padding: 20px;">
                    <h1>$title</h1>
                    <p>$msg</p>
                    <hr>
                    <address>PHPAndro Embedded Server at 127.0.0.1 Port 8080</address>
                </body>
                </html>
            """.trimIndent()
            
            resp.contentType = "text/html; charset=utf-8"
            resp.status = code
            resp.writer.write(html)
        }
    }

    override fun onPause() {
        super.onPause()
        webView.onPause()
    }

    override fun onResume() {
        super.onResume()
        webView.onResume()
    }

    override fun onDestroy() {
        super.onDestroy()
        try {
            server?.stop()
            server?.destroy()
        } catch (e: Exception) {
            Log.e("MainActivity", "Error stopping server", e)
        }
        
        webView.apply {
            stopLoading()
            onPause()
            removeAllViews()
            destroy()
        }
    }
}
