<?php
class VIEW{
    private $session;
    public function __construct(){
        $this->session = new SESSION();
    }

    public function __get($name) {
        return $this->show($name);
    }

    public function show($name){
        $name  = trim($name);

        if (CONFIG::isStartWithNumber($name)) $name = "_{$name}";

        $name = str_replace(array(null), '', $name);
        $v = array('../', '..\\', '//', '\\\\');
        foreach($v as $param){
            while(strpos($name, $param) !== false)
                $name = str_replace($param, "/", $name);
        }
        $v = substr($name, 0, 1);
        if($v == '/' || $v == '\\')
        $name = substr($name, 1, strlen($name)-1);

        return $this->getPage(VIEW_."{$name}.php", $name);
    }

    private function getPage($path, $class){
        $v = null;
        if (file_exists($path) === true){
            $controller = &$this->session->{DATA::controllerString};
            $model = &$this->session->{DATA::modelString};
            $ctrl = &$this->session->{DATA::ctrlString};
            $url = &$this->session->{DATA::urlString};
            $session = &$this->session;

            include $path;
        }
        return $v;
    }
}
?>