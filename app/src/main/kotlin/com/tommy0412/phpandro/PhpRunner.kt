package com.tommy0412.phpandro

class PhpRunner {

    external fun runPhpFile(
        path: String,  
        iniPath: String,     
        method: String,       
        query: String,         
        body: String
    ): String

    companion object {
        init {
            System.loadLibrary("native-lib")
        }
    }
}