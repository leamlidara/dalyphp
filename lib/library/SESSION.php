<?php
class SESSION{
    public function __construct(){
    }

    public function &__get($name){
        return $_SESSION[$name];
    }

    public function &get($name){
        return $_SESSION[$name];
    }

    public function __set($name, $value){
        if (is_null($value) === true) $this->destroy($name);
        else $_SESSION[$name] = $value;
    }

    public function checkSecurity(){
        if(DISABLE_CHECK_EXPIRE_SESSION  === FALSE) $this->verifyExpiredSession();
        $this->verifySessionHijack();

        if (DISABLE_IP_FILTER === FALSE) $this->validateIpAddress();
    }

            /**
     * Destroy session of the current Name
     * @param string $name name of session
     * @note you cannot use array of session on this
     */
    public function destroy($name = ''){
        if ($name !== ''){
            if (is_array($name) === true){
                    foreach($name as $n) $this->destroy($n);
                return;
            }
            if(is_null($name) === true){ $this->destroy(); return; }

            if(isset($_SESSION[$name])) unset($_SESSION[$name]);
        }else{
            if(isset($_SESSION)){
                $ses_id = session_id();
                @session_destroy();
                @session_write_close();
                @session_regenerate_id(true);
                @session_start();
                $ses_id1 = session_id();
                if($ses_id == $ses_id1){
                    @session_write_close();
                    @session_regenerate_id(true);
                    $ses_id = uniqid();
                    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    $ses_id1 = strlen($chars) - 1;
                    for($i=strlen($ses_id); $i < 37; $i++)
                        $ses_id .= $chars[rand(0, $ses_id1)];

                    @session_id($ses_id);
                    unset($chars);
                    @session_start();
                }
                unset($ses_id, $ses_id1);
                $this->createSessionHijackCookie();
            }
        }
    }

    //Verify to make sure that session are not currently use with 2 webbrowser
    private function verifyUserAgent(){
        /*$param = 'dara.framework.userAgent_168';
        $server = new SERVER();
        if (!isset($_SESSION[$param])){
            $_SESSION[$param] = $server->get(SERVER::HTTP_USER_AGENT);
        }else{
            $userAgent = $server->get(SERVER::HTTP_USER_AGENT);
            if ($_SERVER[$param] !== $userAgent){
                $this->destroy();
            }
            unset($userAgent);
        }
        unset($param);
        unset($server);*/
    }

    //Verify to make sure that user is currently use
    public function verifyExpiredSession(){
        if (!isset($_SESSION['dara.session.expireSec'])){
            $_SESSION['dara.session.expireSec'] = time();
        }else{
            $a = time() - $_SESSION['dara.session.expireSec'];
            if($a > 900){
                $this->destroy();
                if($a > 2400){
                    @header('refresh:0'); exit();
                }
                
                if (SHOW_SESSION_ERROR == true)
                    echo "Your session is expired, this page will be refresh in <span id='time'>4</span>. Or <a href='#' onclick='javascript:clearInterval(a);window.location.reload();return false;'>click here</a> to refresh your page.<script>var a=setInterval(function(){var b=parseInt(document.getElementById('time').innerHTML);b=b-1;document.getElementById('time').innerHTML=b;if(b<1){clearInterval(a);window.location.reload();}}, 1000);</script>";
                else 
                    @header('refresh:0'); 
                exit();
                
            }else
                $_SESSION['dara.session.expireSec'] = time();
        }
    }

    //Make sure that session are safe with current ip address
    private function validateIpAddress(){
        $server = new SERVER();
        $ipAddress = $server->getIP();

        if (isset($_SESSION['dara.session.IpAddr'])){
            if ($_SESSION['dara.session.IpAddr'] != $ipAddress){
                $this->destroy();

                if (SHOW_SESSION_ERROR === true)
                    echo "Your IP address has been changed. This page will be refresh in <span id='time'>4</span>. Or <a href='#' onclick='javascript:clearInterval(a);window.location.reload();return false;'>click here</a> to refresh your page.<script>var a=setInterval(function(){var b=parseInt(document.getElementById('time').innerHTML);b=b-1;document.getElementById('time').innerHTML=b;if(b<1){clearInterval(a);window.location.reload();}}, 1000);</script>";
                else
                    @header('refresh:0');
                exit();
            }
        }
        else $_SESSION['dara.session.IpAddr'] = $ipAddress;
    }

    //Verify to make sure that session is secure.
    private function verifySessionHijack(){
        $cookie_name = 'daly_id';
        $a = 'dara.session.HijackProtex';
        if (isset($_SESSION[$a]) === false || is_null($_SESSION[$a]) === true){
            $this->createSessionHijackCookie($cookie_name);
            return;
        }
        if (count($_COOKIE) > 0){
            if (isset($_COOKIE[$cookie_name]) === false || is_string($_COOKIE[$cookie_name]) === false || $_COOKIE[$cookie_name] != $_SESSION[$a]){
                $this->destroy();

                if (SHOW_SESSION_ERROR == true)
                    echo "We detect that you are trying to hijack the session of this website. All sessions which is the same with this session will be expired. This page will be refresh in <span id='time'>4</span>. Or <a href='#' onclick='javascript:clearInterval(a);window.location.reload();return false;'>click here</a> to refresh your page.<script>var a=setInterval(function(){var b=parseInt(document.getElementById('time').innerHTML);b=b-1;document.getElementById('time').innerHTML=b;if(b<1){clearInterval(a);window.location.reload();}}, 1000);</script>";
                else 
                    @header('refresh:0'); 
                exit();
            }
        }
    }

    private function createSessionHijackCookie($cookie_name = 'daly_id'){
        $b = 'abcdefghijklmnopqqrstuvwxyzABDFIKMOPRSXZ0123456789';
        $cnt = strlen($b) - 1;
        $c = '';
        for($i=0; $i<22; $i++)
            $c .= $b[rand(0, $cnt)];

        $_SESSION['dara.session.HijackProtex'] = $c;
        setcookie($cookie_name, $c, time() + (86400 * 30), '/');
    }
}
?>