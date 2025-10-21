#include <jni.h>
#include <string>
#include <android/log.h>
#include <cstdio>
#include <cstring>
#include <netdb.h>         // struct hostent, gethostbyname
#include <netinet/in.h>    // in_addr, htonl
#include <arpa/inet.h>     // optional, inet functions
#include <sys/system_properties.h>
#include <unistd.h>

#ifndef explicit_bzero
static inline void explicit_bzero(void *s, size_t n) {
    volatile unsigned char *p = (volatile unsigned char*)s;
    while (n--) *p++ = 0;
}
#endif

#define LOG_TAG "PHPEmbed"
#define LOGI(...) __android_log_print(ANDROID_LOG_INFO, LOG_TAG, __VA_ARGS__)
#define LOGE(...) __android_log_print(ANDROID_LOG_ERROR, LOG_TAG, __VA_ARGS__)

extern "C" {
#include "php.h"
#include "php_embed.h"
#include "zend_compile.h"
#include "zend_execute.h"
#include "main/php_main.h"

// --- SYSTEM / HOST STUBS ---

// getloadavg stub - returns system load average
//int getloadavg(double loadavg[], int nelem) {
//    // Return 0 for all load averages (system not available in Android)
//    for (int i = 0; i < nelem; i++) {
//        loadavg[i] = 0.0;
//    }
//    return nelem; // Return number of elements set
//}

// SQLite stub functions
const char *sqlite3_column_table_name(void *stmt, int col) {
    return NULL; // Return NULL since we don't have the table name
}

struct hostent *gethostbyname(const char *name) {
    static struct hostent he;
    static char *addr_list[2];
    static char addr[4] = {127,0,0,1};
    static const char *h_name = "localhost";

    he.h_name = (char*)h_name;
    he.h_aliases = NULL;
    he.h_addrtype = AF_INET;
    he.h_length = 4;
    he.h_addr_list = addr_list;
    addr_list[0] = addr;
    addr_list[1] = NULL;
    return &he;
}

int gethostname(char *name, size_t len) {
    const char* hostname = "android";
    if (name && len > 0) {
        strncpy(name, hostname, len);
        name[len-1] = '\0';
        return 0;
    }
    return -1;
}

// DNS stubs
__attribute__((used)) void zif_dns_check_record(INTERNAL_FUNCTION_PARAMETERS) { RETURN_FALSE; }
__attribute__((used)) void zif_dns_get_mx(INTERNAL_FUNCTION_PARAMETERS) { RETURN_FALSE; }
__attribute__((used)) void zif_dns_get_record(INTERNAL_FUNCTION_PARAMETERS) { RETURN_FALSE; }

// Host stubs
__attribute__((used)) void zif_gethostbyaddr(INTERNAL_FUNCTION_PARAMETERS) { RETURN_STRING("localhost"); }
__attribute__((used)) void zif_gethostbyname(INTERNAL_FUNCTION_PARAMETERS) { RETURN_STRING("127.0.0.1"); }
__attribute__((used)) void zif_gethostbynamel(INTERNAL_FUNCTION_PARAMETERS) { array_init(return_value); }

} // extern "C"

static size_t android_ub_write(const char *str, size_t len) {
    fwrite(str, 1, len, stdout);
    fflush(stdout);
    std::string chunk(str, len);
    LOGI("%s", chunk.c_str());
    return len;
}

static void set_php_superglobals(const char* method, const char* query, const char* body)
{
    // Initialize _SERVER
    zval *server_zv;
    if ((server_zv = zend_hash_str_find(&EG(symbol_table), "_SERVER", sizeof("_SERVER") - 1)) == NULL) {
        zval tmp;
        array_init(&tmp);
        zend_hash_str_update(&EG(symbol_table), "_SERVER", sizeof("_SERVER") - 1, &tmp);
        server_zv = zend_hash_str_find(&EG(symbol_table), "_SERVER", sizeof("_SERVER") - 1);
    }

    add_assoc_string(server_zv, "REQUEST_METHOD", method ? method : "GET");
    add_assoc_string(server_zv, "QUERY_STRING", query ? query : "");

    // Initialize _GET
    zval get_arr;
    array_init(&get_arr);
    if (query && strlen(query) > 0) {
        char* q = strdup(query);
        char* token = strtok(q, "&");
        while (token) {
            char* eq = strchr(token, '=');
            if (eq) {
                *eq = '\0';
                zend_string* var = zend_string_init(token, strlen(token), 0);
                zend_string* val = zend_string_init(eq + 1, strlen(eq + 1), 0);
                add_assoc_str(&get_arr, ZSTR_VAL(var), val);
                zend_string_release(var);
            }
            token = strtok(NULL, "&");
        }
        free(q);
    }
    zend_hash_str_update(&EG(symbol_table), "_GET", sizeof("_GET") - 1, &get_arr);

    // Initialize _POST
    if (method && strcmp(method, "POST") == 0 && body && strlen(body) > 0) {
        zval post_arr;
        array_init(&post_arr);
        char* b = strdup(body);
        char* token = strtok(b, "&");
        while (token) {
            char* eq = strchr(token, '=');
            if (eq) {
                *eq = '\0';
                zend_string* var = zend_string_init(token, strlen(token), 0);
                zend_string* val = zend_string_init(eq + 1, strlen(eq + 1), 0);
                add_assoc_str(&post_arr, ZSTR_VAL(var), val);
                zend_string_release(var);
            }
            token = strtok(NULL, "&");
        }
        free(b);
        zend_hash_str_update(&EG(symbol_table), "_POST", sizeof("_POST") - 1, &post_arr);
    }
}

extern "C" JNIEXPORT jstring JNICALL
Java_org_example_PhpRunner_runPhpFile(
    JNIEnv *env, jobject,
    jstring jPath, jstring jIniPath,
    jstring jMethod, jstring jQuery, jstring jBody
) {
    const char *path = env->GetStringUTFChars(jPath, nullptr);
    const char *iniPath = env->GetStringUTFChars(jIniPath, nullptr);
    const char *method = env->GetStringUTFChars(jMethod, nullptr);
    const char *query = env->GetStringUTFChars(jQuery, nullptr);
    const char *body = env->GetStringUTFChars(jBody, nullptr);

    LOGI("Running PHP file: %s", path);
    LOGI("Using php.ini: %s", iniPath);
    LOGI("HTTP method: %s", method);

    php_embed_module.php_ini_path_override = (char *)iniPath;
    php_embed_module.ub_write = android_ub_write;
    php_embed_init(0, nullptr);

    set_php_superglobals(method, query, (strcmp(method, "POST") == 0) ? body : nullptr);

    zend_file_handle file_handle;
    ZEND_SECURE_ZERO(&file_handle, sizeof(file_handle));
    file_handle.filename = zend_string_init(path, strlen(path), 0);
    file_handle.type = ZEND_HANDLE_FILENAME;

    php_output_start_user(nullptr, 0, PHP_OUTPUT_HANDLER_STDFLAGS);
    if (!php_execute_script(&file_handle)) {
        LOGE("PHP execution failed");
    }

    zval buf;
    ZVAL_NULL(&buf);
    std::string result;
    if (php_output_get_contents(&buf) == SUCCESS && Z_TYPE(buf) == IS_STRING) {
        result.assign(Z_STRVAL(buf), Z_STRLEN(buf));
    } else {
        result = "[no PHP output]";
    }

    php_output_discard();
    php_output_end_all();
    zval_ptr_dtor(&buf);
    zend_string_release(file_handle.filename);
    php_embed_shutdown();

cleanup:
    env->ReleaseStringUTFChars(jPath, path);
    env->ReleaseStringUTFChars(jIniPath, iniPath);
    env->ReleaseStringUTFChars(jMethod, method);
    env->ReleaseStringUTFChars(jQuery, query);
    env->ReleaseStringUTFChars(jBody, body);

    return env->NewStringUTF(result.c_str());
}