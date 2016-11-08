<?php
class ROUTE{
    private $p = null;
    public $showError;
    public $errorFile;
    public $skipUrlIndexCount;
    public function __construct(){
        $this->errorFile = '../error-log';
        $this->skipUrlIndexCount = 0;
    }

    /**
     * use to set default page for your website in case other page not found.
     * @param String $pageName set default page
     */
    public function defualt($pageName){
        if (file_exists(CONTROLLER_.$pageName.'Controller.php'))
            $this->p = $pageName;
    }

    /**
     * run your page
     */
    public function run(){
        if(is_numeric($this->skipUrlIndexCount) === false || $this->skipUrlIndexCount < 0) $this->skipUrlIndexCount = 0;
        $url = new URL($this->skipUrlIndexCount);
        if ($this->showError == false){
            error_reporting(E_ALL ^ E_NOTICE);
            ini_set('display_errors', 0);
        }else{
              error_reporting(E_ALL);
              ini_set('display_errors', 1);
       }
        @header('X-Powered-By: DaLy-PHP');
        @header('x-frame-options: SAMEORIGIN');
        @header('X-Content-Type-Options: nosniff');

        $session = new SESSION();
        $session->checkSecurity();
        $session->{DATA::urlString} = $url;
        $session->{DATA::ctrlString} = new CONTROLS();
        $session->{DATA::controllerString} = new CONTROLLER();
        $session->{DATA::modelString} = new MODEL();
        $session->{DATA::viewString} = new VIEW();

        $Controller = new CONTROLLER($this->skipUrlIndexCount);
        $Controller->defaultPage = $this->p;

        $obj = str_replace(array('-', '.'), '_', $url->getPage());
        $obj = $Controller->get($obj);

        //run if page not found
        if ($obj == null) {
            if ($this->p != null) $obj = $Controller->get($this->p);
            else{
                echo 'There were no default page on this server.'; exit();
            }
        }

        //get request method
        $method = strtoupper($_SERVER['REQUEST_METHOD']);

        //Check if Local or hosting
        $isHosting = true;
        $ip = new SERVER();
        $ip = $ip->get(SERVER::CLIENT_IP);
        if ($ip == '127.0.0.1' || $ip == '::1') $isHosting = false;
        unset($ip);

        //loading controller with request method
        if (method_exists($obj, $method)){
            $this->invokeMethod($obj, $method, $url);
        }else if ($isHosting === true){
            header('HTTP/1.1 301 Moved Permanently');
            exit();
        }else{
            echo 'No method were found!'; exit();
        }

        if ($isHosting===true){
            $content = @ob_get_contents();
            @ob_clean();

            $content = preg_replace('/<!--(?!<!)[^\[>].*?-->/', '', $content);
            $content = preg_replace('/\s*$^\s*/m', ' ', $content);
            $content = preg_replace('/[ \t]+/', ' ', $content);
            $content = str_replace("\n ", "\n", $content);

            $content = str_replace(array(': ', ' :'), ':', $content);
            $content = str_replace(array(' =', '= '), '=', $content);
            $content = str_replace("\r\n\r\n\r\n", "\r\n\r\n", $content);
            $content = str_replace("\r\r\r", "\r\r", $content);
            echo str_replace("\n\n\n", "\n\n", $content);
            unset($content);
        }
        @ob_flush();

        if ($this->showError == false){
            $this->removeOldLog("error_log");
            $error = error_get_last();
            if ($error !== null){
                $this->removeOldLog($this->errorFile);
                if(strpos($error['message'], 'Automatically populating $HTTP_RAW_POST_DATA') === false)
                    file_put_contents($this->errorFile, '['.date("Y-m-d h:i:s A")."] Error: ".$error['type']." ".$error['message']." On file: ".$error['file'].' line '.$error['line'].PHP_EOL, FILE_APPEND);
            }
        }

        $session->destroy(DATA::ctrlString);
        $session->destroy(DATA::controllerString);
        $session->destroy(DATA::modelString);
        $session->destroy(DATA::viewString);
        $session->destroy(DATA::urlString);
        if (count($session->get("ctrl_php_dara_frmWork168Cache")) > 50) $session->destroy("ctrl_php_dara_frmWork168Cache");
    }

    private function appendHtmlToHead($html){
        $abc = @ob_get_contents();
        $result = "";
        $headPattern = "<head>";
        @ob_clean();
        echo $abc;
    }

    private function invokeMethod($obj, $method, $url){
        $refl = new ReflectionMethod(get_class($obj), $method);
        $numParams = $refl->getNumberOfParameters();

        if ($numParams > 0)
        {
            $param = explode('/', $url->getFullUrl());
            $cnt = count($param);
            if (strpos($param[$cnt-1], "?") > 0){
                $c = explode("?", $param[$cnt-1]);
                $param[$cnt-1] = $c[0];
            }
            foreach($_GET as $key=>$val){
                $param[$key] = $val;
            }
            $obj->$method($param);
        }else{
            $obj->$method();
        }
    }

    private function removeOldLog($filename){
        if (is_file($filename) === false) return;
        $contet = file_get_contents($filename);
        $arr = array();
        preg_match_all("/(?<=\[)\d{2,4}-.{2,3}-\d{2,4}(?=\s\d{2}:\d{2}:\d{2}\s.*?])/u", $contet, $arr);
        if (count($arr) < 1) return;
        $arr = array_unique($arr[0]);
        $date = strtotime(date("Y-m-d") . " - 7day");
        $spliter = "2000-01-01";
        foreach($arr as $a){
            $d = strtotime($a);
            if ($d > $date) continue;
            if ($d > strtotime($spliter)) $spliter = $a;
        }
        if ($spliter == "2000-01-01") return;
        $contet = explode($spliter, $contet);
        $contet = $contet[count($contet) - 1];
        $contet = str_replace(strtok($contet, "\n"), "", $contet);
        @unlink($filename);
        file_put_contents($filename, $contet, FILE_APPEND);
    }
}
?>