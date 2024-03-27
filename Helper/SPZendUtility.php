<?php

namespace MiniOrange\SP\Helper;

/**
 * This class lists down some replacement functions for common PHP functions not allowed by
 * coding standards. These functions use the zend framework functions for the same functionality
 */
class SPZendUtility
{
    /**
     * Base 64 decode alternative
     */
    public static function base64Decode($value)
    {
        // $base64Obj = new \Zend_XmlRpc_Value_Base64($value,TRUE);
        $base64Obj = base64_decode($value);
        return $base64Obj;
    }

    /**
     * gfdeflate alternative
     */
    public static function gzDeflate($content)
    {
        $_options = [ 'level' => 9, 'mode' => 'deflate', 'archive' => null, ];
        $gzObj = new \Zend_Filter_Compress_Gz($_options);
        return $gzObj->compress($content);
    }

    /**
     * gzinflate alternative
     */
    public static function gzInflate($content)
    {
        $_options = [ 'level' => 9, 'mode' => 'deflate', 'archive' => null, ];
        $gzObj = new \Zend_Filter_Compress_Gz($_options);
        return $gzObj->decompress($content);
    }

    /**
     * Our own alternative to chr function
     * @note - 1 to 31 characters have been ommitted
     */
    public static function getRandomASCIIString($pos)
    {
        $ascii_chars = ["�"," ","!","\"","#","$","%","&","'","(",")","*","+",",","-",".","/","0","1","2","3","4","5","6","7","8","9",":",";",
                                "<","=",">","?","@","A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y",
                                "Z","[","\\","]","^","_","`","a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w",
                                "x","y","z","{","|","}","~","",];

        $size = sizeof($ascii_chars);
        if ($pos < 0) {
            $pos += $size-1;
        }
        $pos %= $size;
        return $ascii_chars[$pos];
    }

    /**
     * Logic replace of XMLSecurityKey encryptMcrypt functionality
     *
     * @todo - might need to extend their class in the future for better functionality and control
     */
    public static function encryptMcrypt($value)
    {
        $mcryptObj = new \Zend_Filter_Encrypt_Mcrypt(['compression'=>'']);
        return $mcryptObj->encrypt($value);
    }

    /**
     * Logic replace of XMLSecurityKey decryptMcrypt functionality
     *
     * @todo - might need to extend their class in the future for better functionality and control
     */
    public static function decryptMcrypt($value)
    {
        $mcryptObj = new \Zend_Filter_Encrypt_Mcrypt(['compression'=>'']);
        return $mcryptObj->decrypt($value);
    }
}
