<?php
class CONTROLLER{
    private $controller;
    public $defaultPage;
    private $session;
    public function __construct()
    {
        $this->session = new SESSION();
    }

    /**
     * get : use to execute controller in controller dirctory on website path
     * @param string $name represent the controller name
     * @return controller syntax
     */ 
    public function get($name){
        try{
            $name  = trim(strtolower($name));
            if ($name == '') $name = 'index';
            else if (CONFIG::isStartWithNumber($name)) $name = '_'.$name;

            $class = $name.'Controller';
            if (isset($this->controller[$name]) === true) return $this->controller[$name];

            if (file_exists(CONTROLLER_  . "{$class}.php") === false){
                $class = $this->defaultPage.'Controller';
                $this->controller[$name] = $this->getPage(CONTROLLER_ . "{$class}.php", $class);
                if ($this->controller[$name] == null) echo 'Page Not Found!';
                return null;
            }

            include_once CONTROLLER_ . "{$class}.php";
            if (class_exists($class) === true){
                $this->controller[$name] = new $class();
                $this->controller[$name]->controller = &$this->session->{DATA::controllerString};
                $this->controller[$name]->view = &$this->session->{DATA::viewString};
                $this->controller[$name]->model = &$this->session->{DATA::modelString};
                $this->controller[$name]->ctrl = &$this->session->{DATA::ctrlString};
                $this->controller[$name]->url = &$this->session->{DATA::urlString};
                $this->controller[$name]->session = &$this->session;

                if (method_exists($this->controller[$name], 'init') === true)
                    $this->controller[$name]->init();
            }
            else
                $this->controller[$name] = $this->getPage(CONTROLLER_ . "{$this->defaultPage}.php", $this->defaultPage.'Controller');

            return $this->controller[$name];
        }catch (Exception $e){ }
        return null;
    }

    private function getPage($path, $class){
        try{
            $c = null;
            if(file_exists($path) === false) return null;
            include_once $path;

            if (class_exists($class) === false) return null;

            $c = new $class();
            $c->controller = &$this->session->{DATA::controllerString};
            $c->view = &$this->session->{DATA::viewString};
            $c->model = &$this->session->{DATA::modelString};
            $c->ctrl = &$this->session->{DATA::ctrlString};
            $c->url = &$this->session->{DATA::urlString};
            $c->session = &$this->session;

            if (method_exists($c, 'init')) $c->init();

            return $c;
        } catch (Exception $e) {
            return null;
        }
    }

    public function __get($name)
    {
        return $this->get($name);
    }
}
?>