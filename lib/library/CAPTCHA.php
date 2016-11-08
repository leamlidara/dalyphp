<?php
class CAPTCHA{
    public function __construct()
    {

    }

    /**
     * get captcha link 
     * 
     * You can use $session->security_code to get security code of this captcha
     * @ex  $session = new SESSION();
     *      echo $session->security_code;
     * Or you can use getCode() function to obtain the captcha code
     */
    public function get($length = 6){
        $session = new SESSION();
        $server = new SERVER();
        if ($session->security_code168_length_168 == NULL)
            $session->security_code168_length_168 = $length;
        return $server->getSiteURL().'/captcha/?'.$this->getRandom();

    }

    public function __toString()
    {
        return $this->get();
    }

    /**
     * Get the security code of your Captchar and also release the memory for next usage
     * @return string security code
     * @note You need to use this function in order to release the code for next process
     */
    public function getCode(){
        $session = new SESSION();
        $code = $session->security_code168;            
        $session->destroy('security_code168');
        //$session->destroy("security_code168_length_168");

        return $code;
    }

    private function getRandom(){
        $abc = '1234567890abcdefghijkl';
        $a = '';
        for ($i=0; $i<4; $i++){
            $num = rand(0, strlen($abc) - 1);
            $a .= $abc[$num];
        }
        return $a;
    }
}
?>