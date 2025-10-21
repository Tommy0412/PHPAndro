/*
   +----------------------------------------------------------------------+
   | Copyright (c) The PHP Group                                          |
   +----------------------------------------------------------------------+
   | This source file is subject to version 3.01 of the PHP license,      |
   | that is bundled with this package in the file LICENSE, and is        |
   | available through the world-wide-web at the following url:           |
   | https://www.php.net/license/3_01.txt                                 |
   | If you did not receive a copy of the PHP license and are unable to   |
   | obtain it through the world-wide-web, please send a note to          |
   | license@php.net so we can mail you a copy immediately.               |
   +----------------------------------------------------------------------+
   | Author: Stig Sæther Bakken <ssb@php.net>                             |
   +----------------------------------------------------------------------+
*/

#define CONFIGURE_COMMAND " '../php-8.4.2/configure'  '--host=aarch64-linux-android' '--prefix=/root/php-android-output' '--enable-embed=shared' '--with-openssl=/root/openssl-install' '--with-curl=/root/curl-install' '--with-sqlite3' '--with-pdo-sqlite' '--with-zip' '--with-libxml' '--enable-dom' '--disable-simplexml' '--disable-xml' '--disable-xmlreader' '--disable-xmlwriter' '--disable-cli' '--disable-cgi' '--disable-fpm' '--disable-posix' '--without-pear' '--disable-phar' '--disable-phpdbg' '--disable-opcache' '--disable-opcache-jit' '--disable-pcntl' '--disable-shmop' '--disable-sysvshm' '--disable-sysvsem' '--disable-sysvmsg' 'SQLITE_CFLAGS=-I/root/sqlite-amalgamation-3470200' 'SQLITE_LIBS=-lsqlite3 -L/root/sqlite-amalgamation-3470200' 'CFLAGS=-DOPENSSL_NO_EGD -DRAND_egd\(file\)=0 -DANDROID -fPIE -fPIC -Dexplicit_bzero\(a,b\)=memset\(a,0,b\) -I/opt/android-ndk-r27c/toolchains/llvm/prebuilt/linux-x86_64/sysroot/usr/include -I/root/sqlite-amalgamation-3470200 -I/root/openssl-install/include -I/root/curl-install/include -I/root/onig-install/include -I/root/libxml2-install/include/libxml2' 'LDFLAGS=-pie -shared -Wl,--whole-archive /root/openssl-install/lib/libssl.a /root/openssl-install/lib/libcrypto.a -Wl,--no-whole-archive -L/root/sqlite-amalgamation-3470200 -L/root/curl-install/lib -L/root/onig-install/lib -L/root/libzip-install/lib -L/root/libxml2-install/lib -L/opt/android-ndk-r27c/toolchains/llvm/prebuilt/linux-x86_64/sysroot/usr/lib/aarch64-linux-android/28 -lc -ldl -lz' 'host_alias=aarch64-linux-android' 'PKG_CONFIG_PATH=/root/libzip-install/lib/pkgconfig:/root/onig-install/lib/pkgconfig:/root/openssl-install/lib/pkgconfig:/root/curl-install/lib/pkgconfig:/root/libxml2-install/lib/pkgconfig' 'OPENSSL_CFLAGS=-I/root/openssl-install/include' 'OPENSSL_LIBS=/root/openssl-install/lib/libssl.a /root/openssl-install/lib/libcrypto.a' 'CURL_CFLAGS=-I/root/curl-install/include' 'CURL_LIBS=-L/root/curl-install/lib -lcurl' 'ONIG_CFLAGS=-I/root/onig-install/include' 'ONIG_LIBS=-L/root/onig-install/lib -lonig' 'LIBZIP_CFLAGS=-I/root/libzip-install/include' 'LIBZIP_LIBS=-L/root/libzip-install/lib -lzip'"
#define PHP_ODBC_CFLAGS	""
#define PHP_ODBC_LFLAGS		""
#define PHP_ODBC_LIBS		""
#define PHP_ODBC_TYPE		""
#define PHP_PROG_SENDMAIL	"/usr/sbin/sendmail"
#define PEAR_INSTALLDIR         ""
#define PHP_INCLUDE_PATH	".:"
#define PHP_EXTENSION_DIR       "/root/php-android-output/lib/php/extensions/no-debug-non-zts-20240924"
#define PHP_PREFIX              "/root/php-android-output"
#define PHP_BINDIR              "/root/php-android-output/bin"
#define PHP_SBINDIR             "/root/php-android-output/sbin"
#define PHP_MANDIR              "/root/php-android-output/php/man"
#define PHP_LIBDIR              "/root/php-android-output/lib/php"
#define PHP_DATADIR             "/root/php-android-output/share/php"
#define PHP_SYSCONFDIR          "/root/php-android-output/etc"
#define PHP_LOCALSTATEDIR       "/root/php-android-output/var"
#define PHP_CONFIG_FILE_PATH    "/root/php-android-output/lib"
#define PHP_CONFIG_FILE_SCAN_DIR    ""
#define PHP_SHLIB_SUFFIX        "so"
#define PHP_SHLIB_EXT_PREFIX    ""
