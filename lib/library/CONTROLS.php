<?php
    class Text{
        private $name, $sub, $controls, $ct_cache, $ct_encKey;

        public function __construct($name){
            $session = new SESSION();
            $this->name = $name;
            $this->controls = &$session->get('ctrl_php_dara_frmWork168');
            $this->sub = &$session->get('ctrl_php_dara_frmWork168SUB');
            $this->ct_cache = &$session->get('ctrl_php_dara_frmWork168Cache');
            $this->ct_encKey = &$session->get('ctrl_php_dara_frawork168Key');
            if($this->ct_encKey === null){
                $enc = new ENCRYPT();
                $this->ct_encKey = $enc->getRandom(9);
            }
        }
        
        /**
         * Get Name of the control
         * @return string
         * @Description Get the control name
         */
        public function getName($strControlClientName){
            $ctrl_orName = $strControlClientName;
            $strControlClientName = substr($strControlClientName, strlen($this->controls));
            
            $result = substr($strControlClientName, 0, strlen($strControlClientName) - strlen($this->sub));
            $strControlClientName = $this->decrypt($result);
            if ($strControlClientName === $ctrl_orName) $strControlClientName = null;
           
            return $strControlClientName;
        }
        
        public function __get($name) {
            if (isset($_POST["{$this->name}V{$name}"]))
                return $_POST["{$this->name}V{$name}"];
            return NULL;
        }
        
        public function __set($name, $value) {
            $_POST["{$this->name}V{$name}"] = $value;
        }

        public function getText(){
            $str = $this->name;
            if (isset($_POST[$str])){
                $a = $_POST[$str];
                if (is_array($a) == TRUE) $a = end($a);
                $b = preg_replace('/[0-9]/', '', str_replace(array(',', '.', '-'), '', $a));
                if ($b == ''){
                    $a = str_replace(',', '', $a);
                }
                return trim($a);
            }
            
            return null;
        }

        public function getTexts(){
            $str = $this->name;
            if (isset($_POST[$str])){
                $a = $_POST[$str];
                if (is_array($a) == FALSE) $a = array($a);
                for($i = 0; $i < count($a); $i++){
                    $a[$i] = trim($a[$i]);

                    $b = preg_replace('/[0-9]/', '', str_replace(array(',', '.', '-'), '', $a[$i]));
                    if ($b == ''){
                        $a[$i] = str_replace(',', '', $a[$i]);
                    }
                }
                
                return $a;
            }
            
            return null;
        }

        /**
         * Get Decoded Text
         * @return String
         */
        public function getDText(){
            return $this->getName($this->getText());
        }

        public function getDTexts(){
            $strNames = $this->getTexts();
            $c = 0; $arr = array();
            foreach($strNames as $name){
                $a = $this->getName($name);
                if ($a != NULL) {
                    $arr[$c] = $a;
                    $c++;
                }
            }
            return $arr;
        }

        public function setText($text){
            $str = $this->name;
            $_POST[$str] = $text; 
        }

        public function __toString(){
            $str = $this->name;
            $ctl = new CONTROLS();
            return $str;
        }
        
        private function decrypt($str){
            $ab = array_search($str, $this->ct_cache);
            if ($ab !== false) return $ab;
            
            $str = str_replace('_min_', '-', $str);
            $str = str_replace('_equ_', '=', $str);
            $str = str_replace('_pls_', '+', $str);
            
            $enc = new ENCRYPT();
            return $enc->decrypt($str, $this->ct_encKey);
       }
    
    }

    class CONTROLS{
        private $controls, $sub, $ct_cache, $ct_encKey;

        public function __construct(){
            $session = new SESSION();
            $this->controls = &$session->get('ctrl_php_dara_frmWork168');
            $this->sub = &$session->get('ctrl_php_dara_frmWork168SUB');
            $this->ct_encKey = &$session->get('ctrl_php_dara_frawork168Key');
            if($this->ct_encKey === null){
                $enc = new ENCRYPT();
                $this->ct_encKey = $enc->getRandom(7);
            }
            $this->ct_cache = &$session->get('ctrl_php_dara_frmWork168Cache');
            if (is_array($this->ct_cache) == false) $this->ct_cache = array();
        }
                
        private function encrypt($str){
            if (array_key_exists($str, $this->ct_cache) == true)
                    return $this->ct_cache[$str];
            
            $enc = new ENCRYPT();
            $result = $enc->encrypt($str, $this->ct_encKey);
            
            $result = str_replace('=', '_equ_', $result);
            $result = str_replace('-', '_min_', $result);
            $result = str_replace('+', '_pls_', $result);
            $this->ct_cache[$str] = $result;
            
            return $result;
	}

	private function decrypt($str){
            $ab = array_search($str, $this->ct_cache);
            if ($ab !== false) return $ab;
             
            $str = str_replace('_min_', '-', $str);
            $str = str_replace('_equ_', '=', $str);
            $str = str_replace('_pls_', '+', $str);
            
            $enc = new ENCRYPT();
            return $enc->decrypt($str, $this->ct_encKey);
	}

        /**
         * get : use to get control name with random string
         * @param type $name name of controls 
         * @return String
         * @Description Control name in random string
         */
        public function get($name){
            $abc = $this->randomCtrl();
            $sub = $this->randomSUB();
            $name = $this->encrypt($name);
            $txt = new Text($abc.$name.$sub);
            return $txt;
        }
        
        /**
         * Get Name of the control
         * @return string
         * @Description Get the control name
         */
        public function getName($strControlClientName){
            $ctrl_orName = $strControlClientName;
            $strControlClientName = substr($strControlClientName, strlen($this->controls));
            
            $result = substr($strControlClientName, 0, strlen($strControlClientName) - strlen($this->sub));
            $strControlClientName = $this->decrypt($result);
            if ($strControlClientName === $ctrl_orName) $strControlClientName = null;
           
            return $strControlClientName;
        }

        private function randomCtrl(){
            if ($this->controls === NULL)
            {
                $str1 = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $s = '';
                $str2 = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890_';
                for ($i=0; $i<2; $i++){
                    $rnd = rand(0, strlen($str1)-1);
                    $s .= $str1[$rnd];
                }
                $str1 = rand(5, 20);
                for ($i=0; $i<$str1; $i++){
                    $rnd = rand(0, strlen($str2)-1);
                    $s .= $str2[$rnd];
                }
               $this->controls = $s;
            }

            return $this->controls;
        }
        
        private function randomSUB(){
            if ($this->sub === NULL){
                $leng = '56789';
                $l = rand(0, strlen($leng)-1);
                $l += rand(5,10);
                $leng = 'abcdefghijklmnopqrstuvwABCDEFGHIJKLMQRSTUVWXYZ';
                $s = '';
                for ($i=0; $i < $l; $i++){
                    $s .= $leng[rand(0, strlen($leng) - 1)];
                }
                $this->sub = $s;
            }
            return $this->sub;
        }

        public function __get($name)
        {
            $abc = $this->randomCtrl();
            $sub = $this->randomSUB();
            $name = $this->encrypt($name);
            $txt = new Text($abc.$name.$sub);
            return $txt;
        }
        
        function javascript_escape($str) {
            $new_str = '';

            $str_len = strlen($str);
            for($i = 0; $i < $str_len; $i++) {
                $new_str .= '\\x' . dechex(ord(substr($str, $i, 1)));
            }

            return $new_str;
        }

        /**
         * clear : use to clear all controls
         */
        public function clear(){
            unset($_SESSION['ctrl_php_dara_frmWork168']);
            $this->controls = $_SESSION['ctrl_php_dara_frmWork168'];
        }
    }
?>