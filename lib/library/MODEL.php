<?php
 class MODEL{
     private $modelC;
     private $session;
     private $url;
     public function __construct()
     {
         $this->session = new SESSION();
     }

     public function __get($name)
     {   
        return $this->get($name);
     }

    public function get($name){
        $name  = trim(strtolower($name));
        if (CONFIG::isStartWithNumber($name)) $name = '_'.$name;

        $class = $name.'Model';
        if (isset($this->modelC[$name]) === false){
            if (file_exists(MODEL_ . "{$class}.php") == false) return null;

            include_once MODEL_ . "{$class}.php";
            if (class_exists($class) == false) return null;

            $this->modelC[$name] = new $class();
            $this->modelC[$name]->model = &$this;
            $this->modelC[$name]->controller = &$this->session->{DATA::controllerString};
            $this->modelC[$name]->ctrl = &$this->session->{DATA::ctrlString};

            $this->modelC[$name]->url = &$this->session->{DATA::urlString};
            $this->modelC[$name]->session = &$this->session;

            if (method_exists($this->modelC[$name], 'init'))
                $this->modelC[$name]->init();
        }

        return $this->modelC[$name];
    }
 }
?>