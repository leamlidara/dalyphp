<?php
/**
 * This framework is written by LEAM LIDARA
 * Version: 1.0.3
 * 
 */

/**
 * Configuration parameters:
 * $dirMVCPath = MVC Path of your website where you store directory (Controller, Model, View) in
 *               Ex: /root/leamlidara/mvc/app
 * $dirLibPath = Library Path
 *               Ex: /root/leamlidara/library
 * $publicPath = public directory of your website where you store your entire files
 *               Ex: /root/leamlidara/website/
 * 
 * Optional parameters:
 * $skipUrlIndexCount : (Integer) Number of skipped URL (This is important if you want to make your whole project as alias)
 *                      Ex: 0, 1, 2, 3, 4, 6, 10
 * $showError : (Boolean) let system show an error or save an error to a file
 * $errorFile : file (include file path) to be save error log in. I am not recommend to change it because it will be unsecure if you changed it
 * $disable_ip_verify : (Boolean) let system to disable IP verify on session
 * $disable_check_expire_session : (Boolean) let system to disable session expire checking process
 */
if (!isset($skipUrlIndexCount)){
    $skipUrlIndexCount = 0;
}
if (!isset($showError)){
    $showError = FALSE;
}
if (!isset($errorFile)){
    $errorFile = $publicPath.'errorlog';
}
if (!isset($disable_ip_verify)){
    $disable_ip_verify = FALSE;
}
if(!isset($show_session_error)){
    $show_session_error = true;
}
if(!isset($disable_check_expire_session)){
    $disable_check_expire_session = false;
}

if (!isset($_SESSION)){
    @session_name('DLSESID');
    @session_write_close();
    $config = session_name();
    if (isset($_COOKIE[$config]) == true){
        if(is_string($_COOKIE[$config]) == false || preg_match('/^[a-zA-Z0-9,\-]{22,40}$/', $_COOKIE[$config]) == false){
            @session_regenerate_id(true);
            $config = uniqid();
            $controllerPath = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $route = strlen($controllerPath) - 1;
            for($i=strlen($config); $i < 37; $i++)
                $config .= $controllerPath[rand(0, $route)];

            @session_id($config);
            unset($config, $route);
        }
        unset($controllerPath);
    }
    try{
        @session_start();
    } catch(Exception $ex){
        usleep(600);
        @session_start();
    }
}

ob_start();
@ini_set("memory_limit", "256M"); 
date_default_timezone_set("Asia/Phnom_Penh");

class CONFIG{
    private $website = '';
    public function __construct($websitePath){
        $this->website = $this->appendLastChar($websitePath);
    }
    public function getWebsitePath(){ return $this->website; }
    
    private function appendLastChar($path){
        $path = trim($path);
        $a = substr($path, strlen($path) - 1);
        if($path == '/' || $path == '\\') return $path;
        return "{$path}/";
    }
    
    public static function isStartWithNumber($str){
        $a = substr($str, 0, 1);
        $ret = false;
        switch ($a){
            case '1': case '2': case '3': case '4': case '5': case '6': case '7': case '8': case '9': case '0':
                $ret = true;
        }
        return $ret;
    }
}

//Initialize Configuration
$config = new CONFIG($dirMVCPath);

//Initialize Autoload
spl_autoload_register(function($name){
    try{
        if(class_exists($name) === true) return;
        $path = dirname(__FILE__).'/library/';
        if(file_exists("{$path}{$name}.php") == true){
            include "{$path}{$name}.php";
        }else if(file_exists(CONTROLLER_."{$name}.php") == true){
            include CONTROLLER_."{$name}.php";
        }
    }catch(Exception $ex){
        @ob_clean();
        echo $ex->getMessage();
        exit(1);
    }
});

//Initialize path
define('MODEL_', $config->getWebsitePath().'Model/');
define('VIEW_', $config->getWebsitePath().'View/');
define('CONTROLLER_', $config->getWebsitePath()."Controller/");
define("DISABLE_IP_FILTER", $disable_ip_verify);
define("SHOW_SESSION_ERROR", $show_session_error);
define("DISABLE_CHECK_EXPIRE_SESSION", $disable_check_expire_session);

SECURITY::DDOS();

//Loading Resource
new RESOURCES($skipUrlIndexCount);

//Initialize Class Components
$route = new ROUTE();
$route->defualt('index');
$route->showError = $showError;
$route->errorFile = $errorFile;
$route->skipUrlIndexCount = $skipUrlIndexCount;
unset($showError, $errorFile, $skipUrlIndexCount, $publicPath, $config, $disable_ip_verify, $disable_check_expire_session, $controllerPath, $show_session_error);
$route->run();
?>