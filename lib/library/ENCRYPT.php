<?php
class ENCRYPT
{
    const NORMAL = 'normal';
    /**
     * RIJNDAEL_128 is require 16 characters of IV and Key.
     */
    const RIJNDAEL_128 = MCRYPT_RIJNDAEL_128;
    /**
     * RIJNDAEL_192 is require 24 characters of IV and Key.
     */
    const RIJNDAEL_192 = MCRYPT_RIJNDAEL_192;
    /**
     * RIJNDAEL_256 is require 32 characters of IV and Key.
     */
    const RIJNDAEL_256 = MCRYPT_RIJNDAEL_256;
    /**
     * TWOFISH is require 16 characters of IV and Key.
     */
    const TWOFISH = MCRYPT_TWOFISH;
    /**
     * BLOWFISH is require minimum 8 characters of IV and Key.
     */
    const BLOWFISH = MCRYPT_BLOWFISH;
    /**
     * RC2 is require minimum 8 characters of IV and Key.
     */
    const RC2 = MCRYPT_RC2;

    private $algo, $error, $session, $_iv='';
    public function __construct($algorithm = ENCRYPT::NORMAL){
        $this->session = new SESSION();
        if($algorithm != self::NORMAL && $algorithm != self::RIJNDAEL_128 && $algorithm != self::RIJNDAEL_256 && $algorithm != self::TWOFISH && $algorithm != self::BLOWFISH && $algorithm != self::RC2 && $algorithm != self::RIJNDAEL_192){
            $this->algo = '';
            throw new Exception('Invalid algorithm provided!');
        }
        $this->algo = $algorithm;
    }
    
    //Prevent from outsite class access
    private function generateKey($length = 8){
        if ($this->session->dara_framework_encKey_session == null){
            $a = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $b = '1234567890';
            $result = $a[rand(0, strlen($a) - 1)];
            $a .= $b;
            for($i = 0; $i < $length - 1; $i++){
                $result .= $a[rand(0, strlen($a) - 1)];
            }
            $this->session->dara_framework_encKey_session = $result;
        }

        return $this->session->dara_framework_encKey_session;
    }
    
    function getRandom($length = 8){
        $a = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $b = '1234567890';
        $result = $a[rand(0, strlen($a) - 1)];
        $a .= $b;
        for($i = 0; $i < $length - 1; $i++)
            $result .= $a[rand(0, strlen($a) - 1)];
        
        return $result;
    }
    
    /**
     * Get or Set IV
     * @param String $iv String if you want to set IV, or NULL if you want to set IV
     * @return String IV
     * @throws Exception Key not valid
     */
    function iv($iv=null){
        $iv_size = mcrypt_get_iv_size($this->algo, MCRYPT_MODE_CBC);
        if(is_null($iv) === false){
            if(strlen($iv) != $iv_size){
                $iv = 'Normal';
                if($this->algo === self::RIJNDAEL_128) $iv = 'RIJNDAEL_128';
                else if($this->algo === self::RIJNDAEL_192) $iv = 'RIJNDAEL_192';
                else if($this->algo === self::RIJNDAEL_256) $iv = 'RIJNDAEL_256';
                else if($this->algo === self::TWOFISH) $iv = 'TWOFISH';
                else if($this->algo === self::BLOWFISH) $iv = 'BLOWFISH';
                else if($this->algo === self::RC2) $iv = 'RC2';
                throw new Exception("Key length for {$iv} algorithm should be {$iv_size}.");
            }
            $this->_iv = $iv;
        }else if($this->_iv === ''){
            $defIv = 'w#3eWJvh8%64@2cTn3TPnH!xfed^Bzda#htry^qYv%BWTz4H2ReN3jkcTD^TxNje8x#usQ8&jGDdqdwf2V$4SUDJsuEysyAD^krhYTw4B*5Z6P5#XwZ7Bm%&Bs%9Tw^y4G7x&U$8Dv5Qjy!#4QWcD#bU@PE3qYCgFUAm!WCM2QBmmF2XJvR9$6rvM7Gj^8NEgDy*wXkbFCPmQtpnDbGXZ73J!YMncuAT5x%VPX!ug2Y6N%APGWKauz%PgSEJPc8t';
            $this->_iv = substr($defIv, 0, $iv_size);
            $defIv = '';
        }
        return $this->_iv;
    }
    
    /**
     * Get error when encrypt or decrypt fail
     */
    function getError(){
        return $this->error;
    }


    /**
     * Encrypt text
     * @param String $str Text to be encrypt
     * @param String $passw Password to encrypt
     * @return String encrypted text
     */
    function encrypt($str, $passw = 'daly_php_default_pwd'){
        $this->error = '';
        if($this->algo === ENCRYPT::NORMAL) return $this->normalAlg($str, $passw, 0);
        else if($this->algo == ''){
            $this->error = 'Invalid algorithm provided!';
            return null;
        }
        
        if(function_exists('mcrypt_encrypt') === false){
            $this->error = 'MCRYPT library is not support with this server. Please use ENCRYPT::NORMAL instead!';
            return null;
        }
        
        $a = strlen($passw);
        if($this->algo == self::BLOWFISH || $this->algo == self::RC2){
            if($a < 8){
                $this->error = 'This algorithm is required minimum 8 character of key size.';
                return null;
            }
        }else if($a !== 10 && $a !== 24 && $a !== 32){
            $this->error = "Key of size {$a} not supported by this algorithm. Only keys of sizes 16, 24 or 32 supported.";
            return null;
        }
        
        try{
            $a = mcrypt_encrypt($this->algo, $passw, trim($str), MCRYPT_MODE_CBC, $this->iv());
            return base64_encode($a);
        }catch(Exception $ex){
            $this->error = $ex->getMessage();
        }
        return null;
    }

    /**
     * Decrypt text
     * @param String $str Encrypted text to be decrypt
     * @param String $passw Password to decrypt
     * @return String decrypted text or NULL on fail
     */
    public function decrypt($str,$passw = 'daly_php_default_pwd'){
        $this->error = '';
        if($this->algo === ENCRYPT::NORMAL) return $this->normalAlg($str, $passw, 1);
        else if($this->algo == ''){
            $this->error = 'Invalid algorithm provided!';
            return null;
        }
        
        if(function_exists('mcrypt_decrypt') === false){
            $this->error = 'MCRYPT library is not support with this server. Please use ENCRYPT::NORMAL instead!';
            return null;
        }
        
        $a = strlen($passw);
        if($this->algo == self::BLOWFISH || $this->algo == self::RC2){
            if($a < 8){
                $this->error = 'This algorithm is required minimum 8 character of key size.';
                return null;
            }
        }else if($a !== 10 && $a !== 24 && $a !== 32){
            $this->error = "Key of size {$a} not supported by this algorithm. Only keys of sizes 16, 24 or 32 supported.";
            return null;
        }
        
        try{
            $str = base64_decode($str, true);
            if($str === false){
                $this->error = 'Encrypted string is not base64 characters!';
                return null;
            }
            $a = mcrypt_decrypt($this->algo, $passw, $str, MCRYPT_MODE_CBC, $this->iv());
            return trim($a);
        }catch(Exception $ex){
            $this->error = $ex->getMessage();
        }
        return null;
    }

    private function normalAlg($str, $passw, $isDecrypt=0){
        $_mixing_passw = function ($b,$passw){
            $s='';
            $c=$b;
            $b=str_split($b);
            $passw=str_split(sha1($passw));
            $lenp=count($passw);
            $lenb=count($b);
            for($i=0;$i<$lenp;$i++){
                for($j=0;$j<$lenb;$j++){
                    if($passw[$i]==$b[$j]){
                        $c=str_replace($b[$j],'',$c);
                        if(!preg_match('/'.$b[$j].'/',$s)){
                            $s.=$b[$j];
                        }
                    }
                }
            }
            return $c.''.$s;
        };
        
        if($isDecrypt === 0){
            $r='';
            $md=$passw?substr(md5($passw),0,16):'';
            $str=base64_encode($md.$str);
            $abc='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            $a=str_split('+/='.$abc);
            $b=strrev('-_='.$abc);
            if($passw){
                $b=$_mixing_passw($b,$passw);
            }else{
                $r=rand(10,65);
                $b=mb_substr($b,$r).mb_substr($b,0,$r);
            }
            $s='';
            $b=str_split($b);
            $str=str_split($str);
            $lens=count($str);
            $lena=count($a);
            for($i=0;$i<$lens;$i++){
                for($j=0;$j<$lena;$j++){
                    if($str[$i]==$a[$j]){
                        $s.=$b[$j];
                    }
                }
            }
            $result = $s.$r;
            return $result;
        }else{
            $abc='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            $a=str_split('+/='.$abc);
            $b=strrev('-_='.$abc);
            if($passw){
                $b=$_mixing_passw($b,$passw);
            }else{
                $r=mb_substr($str,-2);
                $str=mb_substr($str,0,-2);
                $b=mb_substr($b,$r).mb_substr($b,0,$r);
            }
            $s='';
            $b=str_split($b);
            $str=str_split($str);
            $lens=count($str);
            $lenb=count($b);
            for($i=0;$i<$lens;$i++){
                for($j=0;$j<$lenb;$j++){
                    if($str[$i]==$b[$j]){
                        $s.=$a[$j];
                    }
                }
            }
            $s=base64_decode($s);
            if($passw&&substr($s,0,16)==substr(md5($passw),0,16))
                return substr($s,16);
            
            $this->error = 'Invalid password to decrypt this string!';
            return NULL;
        }
    }
}
?>
