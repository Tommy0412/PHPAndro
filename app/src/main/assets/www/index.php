<?php
header("Content-Type: text/html; charset=utf-8");

if (isset($_GET['action'])) {
    $action = $_GET['action'];

    switch ($action) {
        case 'phpinfo':
            ob_start();
            phpinfo();
            echo ob_get_clean();
            break;

        case 'extensions':
            echo json_encode(get_loaded_extensions(), JSON_PRETTY_PRINT);
            break;

        case 'phpversion':
            echo json_encode(['version' => phpversion(), 'sapi' => php_sapi_name()]);
            break;

        case 'ini':
            echo json_encode(ini_get_all(null, false), JSON_PRETTY_PRINT);
            break;

        case 'env':
            echo json_encode(['server' => $_SERVER, 'env' => getenv()], JSON_PRETTY_PRINT);
            break;

        case 'status':
            $status = [
                'php_uname' => php_uname(),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'disk_free' => disk_free_space(__DIR__),
                'loaded_ini' => php_ini_loaded_file(),
                'loaded_extensions_count' => count(get_loaded_extensions()),
            ];
            echo json_encode($status, JSON_PRETTY_PRINT);
            break;

        case 'curltest':
            $url = "https://www.google.com";
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            $data = curl_exec($ch);
            $err = curl_error($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            echo json_encode([
                'url' => $url,
                'success' => $data !== false,
                'http_code' => $info['http_code'] ?? null,
                'response_length' => strlen($data ?? ''),
                'error' => $err ?: null
            ], JSON_PRETTY_PRINT);
            break;

        case 'openssl':
            $testStr = "Hello PHP-Android";
            $cipher = "aes-128-cbc";
            $key = "testkey123456789";
            $iv = substr(hash('sha256', 'ivstring'), 0, 16);
            $encrypted = openssl_encrypt($testStr, $cipher, $key, 0, $iv);
            $decrypted = openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
            echo json_encode([
                'openssl_version' => OPENSSL_VERSION_TEXT,
                'cipher' => $cipher,
                'original' => $testStr,
                'encrypted' => $encrypted,
                'decrypted' => $decrypted,
                'match' => $testStr === $decrypted
            ], JSON_PRETTY_PRINT);
            break;

        case 'filesystem':
            $tmpFile = ini_get('upload_tmp_dir') . '/phpandroid_test.txt';
            $data = "PHPAndro test file\n" . date('c');
            $write = file_put_contents($tmpFile, $data);
            $read = @file_get_contents($tmpFile);
            echo json_encode([
                'temp_dir' => sys_get_temp_dir(),
                'write_success' => $write !== false,
                'read_success' => $read !== false,
                'read_content' => $read,
                'exists_after' => file_exists($tmpFile)
            ], JSON_PRETTY_PRINT);
            break;

        case 'sqlite':
            try {
                $tmpDir = ini_get('upload_tmp_dir');
                if (!$tmpDir) $tmpDir = __DIR__;
                $dbPath = $tmpDir . '/phpandroid_test.db';
                $db = new SQLite3($dbPath);
                $db->exec("CREATE TABLE IF NOT EXISTS test (id INTEGER PRIMARY KEY, txt TEXT)");
                $db->exec("INSERT INTO test (txt) VALUES ('Hello SQLite from Android')");
                $res = $db->query("SELECT * FROM test");
                $rows = [];
                while ($r = $res->fetchArray(SQLITE3_ASSOC)) $rows[] = $r;
                $db->close();
                echo json_encode([
                    'db_path' => $dbPath,
                    'rows' => $rows,
                    'success' => true
                ], JSON_PRETTY_PRINT);
            } catch (Throwable $e) {
                echo json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT);
            }
            break;

        case 'network':
            $hostname = gethostname();
            $ipLocal = gethostbyname($hostname);
            $ipExternal = @file_get_contents('https://api.ipify.org');
            echo json_encode([
                'hostname' => $hostname,
                'local_ip' => $ipLocal,
                'external_ip' => $ipExternal ?: 'unavailable',
                'dns_google' => gethostbyname('google.com')
            ], JSON_PRETTY_PRINT);
            break;

        case 'timezone':
            echo json_encode([
                'default_timezone' => date_default_timezone_get(),
                'current_time' => date('c'),
                'locale' => setlocale(LC_ALL, 0)
            ], JSON_PRETTY_PRINT);
            break;

        // Test HTTP Methods
        case 'http_methods':
            $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
            $results = [];

            foreach ($methods as $method) {
                $endpoint = strtolower($method);
                if ($endpoint === 'patch') {
                    $endpoint = 'patch';
                }
                $url = "https://postman-echo.com/$endpoint";

                $ch = curl_init($url);

                $postFields = in_array($method, ['POST', 'PUT', 'PATCH']) ? json_encode(['test' => 'data']) : null;

                curl_setopt_array($ch, [
                    CURLOPT_CUSTOMREQUEST => $method,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => 0,
                    CURLOPT_TIMEOUT => 30,
                    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                    CURLOPT_POSTFIELDS => $postFields
                ]);

                $response = curl_exec($ch);
                $error = curl_error($ch);
                $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $results[$method] = [
                    'url' => $url,
                    'status' => $status,
                    'error' => $error,
                    'response' => $response
                ];
            }

            header('Content-Type: application/json');
            echo json_encode($results, JSON_PRETTY_PRINT);
            break;


        // DOMDocument test
        case 'dom':
            $html = "<html><body><h1>Hello DOMDocument</h1><p>Current time: " . date('c') . "</p></body></html>";
            $dom = new DOMDocument();
            @$dom->loadHTML($html);
            $nodes = [];
            foreach ($dom->getElementsByTagName('*') as $el) {
                $nodes[] = [
                    'tag' => $el->tagName,
                    'content' => trim($el->textContent)
                ];
            }
            echo json_encode($nodes, JSON_PRETTY_PRINT);
            break;

        case 'pcre':
            $subject = "PHPAndro is 100% awesome!";
            $pattern = '/(\w+) is (\d+)% (\w+)/';
            preg_match($pattern, $subject, $matches);
            echo json_encode([
                'subject' => $subject,
                'pattern' => $pattern,
                'matches' => $matches,
                'replaced' => preg_replace('/awesome/', 'powerful', $subject)
            ], JSON_PRETTY_PRINT);
            break;

        case 'zlib':
            $data = "Compress me if you can! " . str_repeat("Repeat ", 10);
            $compressed = gzcompress($data, 9);
            echo json_encode([
                'original_length' => strlen($data),
                'compressed_length' => strlen($compressed),
                'ratio' => round(strlen($compressed) / strlen($data), 2),
                'uncompressed' => gzuncompress($compressed) === $data
            ], JSON_PRETTY_PRINT);
            break;

        case 'bcmath':
            echo json_encode([
                'add' => bcadd('1.234', '5.678', 4),
                'mul' => bcmul('1.234', '5.678', 4),
                'pow' => bcpow('2', '10', 0)
            ], JSON_PRETTY_PRINT);
            break;

        case 'fileinfo':
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            echo json_encode([
                'mime_type_php' => finfo_file($finfo, __FILE__),
                'mime_type_self' => finfo_buffer($finfo, "<?php echo 'hello'; ?>")
            ], JSON_PRETTY_PRINT);
            finfo_close($finfo);
            break;

        case 'filter':
            echo json_encode([
                'email' => filter_var('test@example.com', FILTER_VALIDATE_EMAIL),
                'url' => filter_var('https://google.com', FILTER_VALIDATE_URL),
                'int' => filter_var('123', FILTER_VALIDATE_INT),
                'sanitize' => filter_var('<h1>Hello</h1>', FILTER_SANITIZE_FULL_SPECIAL_CHARS)
            ], JSON_PRETTY_PRINT);
            break;

        case 'hash':
            $data = "PHPAndro";
            echo json_encode([
                'md5' => hash('md5', $data),
                'sha1' => hash('sha1', $data),
                'sha256' => hash('sha256', $data),
                'algos' => array_slice(hash_algos(), 0, 10)
            ], JSON_PRETTY_PRINT);
            break;

        case 'mbstring':
            $str = "こんにちは PHP";
            echo json_encode([
                'strlen' => strlen($str),
                'mb_strlen' => mb_strlen($str),
                'upper' => mb_strtoupper($str),
                'encoding' => mb_detect_encoding($str)
            ], JSON_PRETTY_PRINT);
            break;

        case 'session':
            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['test_key'] = ($_SESSION['test_key'] ?? 0) + 1;
            echo json_encode([
                'session_id' => session_id(),
                'session_data' => $_SESSION,
                'status' => session_status()
            ], JSON_PRETTY_PRINT);
            break;

        case 'random':
            echo json_encode([
                'int' => random_int(1, 100),
                'bytes_hex' => bin2hex(random_bytes(16))
            ], JSON_PRETTY_PRINT);
            break;

        case 'reflection':
            $ref = new ReflectionClass('Exception');
            echo json_encode([
                'name' => $ref->getName(),
                'methods' => array_map(fn($m) => $m->name, array_slice($ref->getMethods(), 0, 5)),
                'is_internal' => $ref->isInternal()
            ], JSON_PRETTY_PRINT);
            break;

        case 'tokenizer':
            $tokens = token_get_all('<?php echo "hi"; ?>');
            $summary = [];
            foreach (array_slice($tokens, 0, 10) as $token) {
                if (is_array($token)) {
                    $summary[] = [token_name($token[0]), $token[1]];
                } else {
                    $summary[] = $token;
                }
            }
            echo json_encode($summary, JSON_PRETTY_PRINT);
            break;

        case 'zip':
            $zip = new ZipArchive();
            $tmpFile = tempnam(sys_get_temp_dir(), 'zip');
            if ($zip->open($tmpFile, ZipArchive::CREATE) === TRUE) {
                $zip->addFromString('test.txt', 'file content');
                $zip->close();
                $res = [
                    'created' => true,
                    'size' => filesize($tmpFile),
                    'exists' => file_exists($tmpFile)
                ];
                unlink($tmpFile);
                echo json_encode($res, JSON_PRETTY_PRINT);
            } else {
                echo json_encode(['error' => 'Could not create zip'], JSON_PRETTY_PRINT);
            }
            break;

        case 'bz2':
            $data = "Bzip2 compression test. " . str_repeat("Data ", 10);
            $compressed = bzcompress($data, 9);
            echo json_encode([
                'original_length' => strlen($data),
                'compressed_length' => strlen($compressed),
                'uncompressed' => bzdecompress($compressed) === $data
            ], JSON_PRETTY_PRINT);
            break;

        case 'ctype':
            echo json_encode([
                'alpha' => ctype_alpha('ABC'),
                'digit' => ctype_digit('123'),
                'alnum' => ctype_alnum('ABC123'),
                'space' => ctype_space(' '),
                'false_test' => ctype_alpha('123')
            ], JSON_PRETTY_PRINT);
            break;

        case 'spl':
            $list = new SplDoublyLinkedList();
            $list->push('first');
            $list->push('second');
            $list->push('third');
            echo json_encode([
                'count' => $list->count(),
                'bottom' => $list->bottom(),
                'top' => $list->top(),
                'array' => iterator_to_array($list)
            ], JSON_PRETTY_PRINT);
            break;

        case 'exif':
            echo json_encode([
                'supported_types' => exif_imagetype(__FILE__) === false ? 'No images to test' : 'Image detected',
                'functions' => get_extension_funcs('exif')
            ], JSON_PRETTY_PRINT);
            break;

        default:
            echo json_encode(['error' => 'Unknown action']);
            break;
    }
    exit;
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>PHPAndro Diagnostic</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        pre {
            white-space: pre-wrap;
            word-break: break-word;
        }

        .card {
            @apply bg-white/80 dark:bg-gray-800/70 backdrop-blur rounded-2xl shadow-lg p-4;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-sky-400 via-indigo-500 to-violet-600 min-h-screen text-gray-800 dark:text-gray-100">
    <div class="max-w-5xl mx-auto p-6">
        <header class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-extrabold">PHPAndro Diagnostic</h1>
            <div class="text-xs opacity-80">
                PHP <span id="phpVer">...</span> • SAPI <span id="phpSapi">...</span>
            </div>
        </header>

        <main class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2 card">
                <h2 class="font-semibold mb-3">Test Actions</h2>
                <div class="grid grid-cols-2 gap-3">
                    <button class="action-btn p-2 bg-blue-600 text-white rounded" data-action="phpinfo">phpinfo()</button>
                    <button class="action-btn p-2 bg-blue-600 text-white rounded" data-action="extensions">Extensions</button>
                    <button class="action-btn p-2 bg-blue-600 text-white rounded" data-action="phpversion">Version</button>
                    <button class="action-btn p-2 bg-blue-600 text-white rounded" data-action="ini">INI</button>
                    <button class="action-btn p-2 bg-blue-600 text-white rounded" data-action="env">Env</button>
                    <button class="action-btn p-2 bg-blue-600 text-white rounded" data-action="status">Status</button>
                    <button class="action-btn p-2 bg-emerald-600 text-white rounded" data-action="curltest">cURL Test</button>
                    <button class="action-btn p-2 bg-emerald-600 text-white rounded" data-action="openssl">OpenSSL Test</button>
                    <button class="action-btn p-2 bg-emerald-600 text-white rounded" data-action="filesystem">Filesystem</button>
                    <button class="action-btn p-2 bg-emerald-600 text-white rounded" data-action="sqlite">SQLite Test</button>
                    <button class="action-btn p-2 bg-emerald-600 text-white rounded" data-action="network">Network</button>
                    <button class="action-btn p-2 bg-emerald-600 text-white rounded" data-action="timezone">Timezone</button>
                    <button class="action-btn p-2 bg-purple-600 text-white rounded" data-action="http_methods">HTTP Methods Test</button>
                    <button class="action-btn p-2 bg-purple-600 text-white rounded" data-action="dom">DOMDocument Test</button>
                    <button class="action-btn p-2 bg-orange-600 text-white rounded" data-action="pcre">PCRE (Regex)</button>
                    <button class="action-btn p-2 bg-orange-600 text-white rounded" data-action="zlib">Zlib (Zip)</button>
                    <button class="action-btn p-2 bg-orange-600 text-white rounded" data-action="bcmath">BCMath</button>
                    <button class="action-btn p-2 bg-orange-600 text-white rounded" data-action="fileinfo">Fileinfo</button>
                    <button class="action-btn p-2 bg-pink-600 text-white rounded" data-action="filter">Filter</button>
                    <button class="action-btn p-2 bg-pink-600 text-white rounded" data-action="hash">Hash</button>
                    <button class="action-btn p-2 bg-pink-600 text-white rounded" data-action="mbstring">MBString</button>
                    <button class="action-btn p-2 bg-pink-600 text-white rounded" data-action="session">Session</button>
                    <button class="action-btn p-2 bg-cyan-600 text-white rounded" data-action="random">Random</button>
                    <button class="action-btn p-2 bg-cyan-600 text-white rounded" data-action="reflection">Reflection</button>
                    <button class="action-btn p-2 bg-cyan-600 text-white rounded" data-action="tokenizer">Tokenizer</button>
                    <button class="action-btn p-2 bg-cyan-600 text-white rounded" data-action="zip">ZipArchive</button>
                    <button class="action-btn p-2 bg-red-600 text-white rounded" data-action="bz2">BZ2</button>
                    <button class="action-btn p-2 bg-red-600 text-white rounded" data-action="ctype">Ctype</button>
                    <button class="action-btn p-2 bg-red-600 text-white rounded" data-action="spl">SPL</button>
                    <button class="action-btn p-2 bg-red-600 text-white rounded" data-action="exif">Exif</button>
                </div>

                <div id="output" class="mt-4 bg-gray-50 dark:bg-gray-900 p-3 rounded-lg overflow-auto" style="max-height:60vh">
                    <div class="text-sm opacity-70">Press a button to run an action.</div>
                </div>
            </div>

            <aside class="card">
                <h3 class="font-semibold mb-2">Tools</h3>
                <div class="space-y-2 text-sm">
                    <button id="copyBtn" class="w-full p-2 rounded bg-indigo-600 text-white">Copy</button>
                    <button id="downloadBtn" class="w-full p-2 rounded bg-emerald-600 text-white">Download</button>
                    <button id="clearBtn" class="w-full p-2 rounded bg-red-600 text-white">Clear</button>
                </div>
            </aside>
        </main>

        <footer class="mt-6 text-xs opacity-80 text-center">Last updated <?= date('Y-m-d H:i') ?></footer>
    </div>

    <script>
        const output = document.getElementById('output');
        let lastOutput = '';

        async function callAction(action) {
            output.innerHTML = 'Loading...';
            const res = await fetch('?action=' + action);

            if (action === 'phpinfo') {
                output.innerHTML = await res.text();
                return;
            }

            const json = await res.text();
            try {
                const data = JSON.parse(json);

                if (action === 'http_methods') {
                    let html = '';
                    for (let method in data) {
                        html += `<details class="mb-2"><summary class="font-semibold">${method}</summary><pre>${escapeHtml(data[method])}</pre></details>`;
                    }
                    output.innerHTML = html;
                    lastOutput = JSON.stringify(data, null, 2);
                    return;
                }

                lastOutput = JSON.stringify(data, null, 2);
                output.innerHTML = '<pre>' + escapeHtml(lastOutput) + '</pre>';
            } catch {
                output.innerHTML = '<pre>' + escapeHtml(json) + '</pre>';
            }
        }

        document.querySelectorAll('.action-btn').forEach(b => b.onclick = () => callAction(b.dataset.action));

        function escapeHtml(s) {
            return s.replace(/[&<>]/g, m => ({
                "&": "&amp;",
                "<": "&lt;",
                ">": "&gt;"
            } [m]));
        }

        document.getElementById('copyBtn').onclick = () => navigator.clipboard.writeText(lastOutput || output.innerText);
        document.getElementById('downloadBtn').onclick = () => {
            const blob = new Blob([lastOutput || output.innerText], {
                type: 'text/plain'
            });
            const a = document.createElement('a');
            a.href = URL.createObjectURL(blob);
            a.download = 'php-output.txt';
            a.click();
        }
        document.getElementById('clearBtn').onclick = () => {
            output.innerHTML = 'Cleared.';
        }

        fetch('?action=phpversion').then(r => r.json()).then(i => {
            document.getElementById('phpVer').innerText = i.version;
            document.getElementById('phpSapi').innerText = i.sapi;
        });
    </script>
</body>

</html>