<?php
class SECURITY{
    /**
     * Clean input string to get a new safe string
     * @param string $string String to be cleaned
     */
    public static function cleanXXS($string){
        // Fix &entity\n;
        $string = str_replace(array('&amp;','&lt;','&gt;'), array('&amp;amp;','&amp;lt;','&amp;gt;'), $string);
        $string = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $string);
        $string = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $string);
        $string = html_entity_decode($string, ENT_COMPAT, 'UTF-8');

        // Remove any attribute starting with "on" or xmlns
        $string = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $string);

        // Remove javascript: and vbscript: protocols
        $string = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $string);
        $string = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $string);
        $string = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $string);

        // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
        $string = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $string);
        $string = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $string);
        $string = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $string);

        // Remove namespaced elements (we do not need them)
        $string = preg_replace('#</*\w+:\w[^>]*+>#i', '', $string);

        // we are done...
        return $string;
    }

    public static function DDOS(){
        $uri = md5($_SERVER['REQUEST_URI']);
        $exp = 1; // 1 seconds
        $hash = $uri .'|'. time();
        if (!isset($_SESSION['session.daly@ddos_protex'])) {
            $_SESSION['session.daly@ddos_protex'] = $hash;
            $uri = 'daly_default_ddos_protection_page';
        }

        list($_uri, $_exp) = explode('|', $_SESSION['session.daly@ddos_protex']);
        if ($_uri == $uri && time() - $_exp < $exp) {
            header('HTTP/1.1 401 DDOS Protected'); exit();
        }
        $_SESSION['session.daly@ddos_protex'] = $hash;
    }

}
?>