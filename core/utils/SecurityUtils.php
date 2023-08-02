<?php
class SecurityUtils
{
    const Public_Ecryption_Method = "AES-256-CBC";
    const Public_Secret_Key = "";
    const Public_Secret_IV = "";

    const Advanced_Secret_Key = "";
    const Advanced_Secret_IV = "";

    private static function public_encrypt_decrypt($action, $string)
    {
        $output = false;

        $key = hash('sha256', SecurityUtils::Public_Secret_Key);

        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', SecurityUtils::Public_Secret_IV), 0, 16);

        if ($action == 'encrypt') {
            $input = $string;
            $output = openssl_encrypt($input, SecurityUtils::Public_Ecryption_Method, $key, 0, $iv);
            $output = base64_encode($output);
        } else if ($action == 'decrypt') {
            if (strlen($string) < 16) {
                return "";
            }
            $output = openssl_decrypt(base64_decode($string), SecurityUtils::Public_Ecryption_Method, $key, 0, $iv);
        }

        return $output;
    }
    private static function advanced_encrypt_decrypt($action, $string)
    {
        $output = false;

        $key = hash('sha256', SecurityUtils::Advanced_Secret_Key);

        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(md5(hash('sha256', SecurityUtils::Advanced_Secret_IV)), 0, 16);

        if ($action == 'encrypt') {
            $input = $string;
            $output = openssl_encrypt($input, SecurityUtils::Public_Ecryption_Method, $key, 0, $iv);
            $output = base64_encode($output);
            $output = substr_replace($output, "f", 3, 0);
            $output = substr_replace($output, "E", 7, 0);
            $output = substr_replace($output, "k", 10, 0);
            $output = substr_replace($output, "z", 12, 0);
        } else if ($action == 'decrypt') {
            if (strlen($string) < 16) {
                return $string;
            }
            $normalString = $string;
            $normalString = substr_replace($normalString, "", 3, 1);
            $normalString = substr_replace($normalString, "", 6, 1);
            $normalString = substr_replace($normalString, "", 8, 1);
            $normalString = substr_replace($normalString, "", 9, 1);
            $output = openssl_decrypt(base64_decode($normalString), SecurityUtils::Public_Ecryption_Method, $key, 0, $iv);
        }

        return $output;
    }

    public static function publicEncryptText($str)
    {
        return urlencode(SecurityUtils::public_encrypt_decrypt("encrypt", $str));
    }

    public static function publicDecryptText($hash)
    {
        return SecurityUtils::public_encrypt_decrypt("decrypt", urldecode($hash));
    }

    public static function advancedEncryptText($str)
    {
        return urlencode(SecurityUtils::advanced_encrypt_decrypt("encrypt", $str));
    }

    public static function advancedDecryptText($hash)
    {
        return SecurityUtils::advanced_encrypt_decrypt("decrypt", urldecode($hash));
    }
}
