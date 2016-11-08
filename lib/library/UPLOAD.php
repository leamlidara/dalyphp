<?php
class UPLOAD{
    private $path;
    private $extension;
    private $isRandomName;
    private $start;
    private $end;
    private $error;
    /**
     * use when you want to upload file to your website
     * @param string $savedPath Path in server to be save in
     * @param array $allowedExtension array of extension you want to upload
     * @param boolean $randomName if this true mean that you want server to generate the file name for you
     * @param string $start is the string which use to include to the beginning of the name of your file
     * @param string $end is the string which use to include to the end of the name of your file
     */
    public function __construct($savedPath, $allowedExtension = array(), $randomName = TRUE, $start = "", $end = "")
    {
        $this->error = "";
        //$this->path = $savedPath;
        $this->extension = array_change_key_case($allowedExtension, CASE_LOWER);
        $this->isRandomName = $randomName;
        $this->start = $start;
        $this->end = $end;

        if ($this->checkLast($savedPath) == false){
            $this->path = $savedPath.'/';
        }else
        {
            $this->path = $savedPath;
        }

        if (!is_dir($this->path)) mkdir($this->path, 0777, true);

        $htaccess = "{$this->path}.htaccess";

        $rm = 'RemoveHandler pl .cgi .php .php3 .php4 .php5 .py .html .js .css .xml .asp .aspx .avfp .c .csp .go .cfm .gsp .jsp .lp .op .lua .ipl .rhtml .rb .rbw .smx .lasso .tcl .dna .tpl .r .w .pyc .pyo';
        $ad = 'AddType text/plain .pl .cgi .php .php3 .php4 .php5 .py .html .js .css .xml .asp .aspx .avfp .c .csp .go .cfm .gsp .jsp .lp .op .lua .ipl .rhtml .rb .rbw .smx .lasso .tcl .dna .tpl .r .w .pyc .pyo';
        if (file_exists($htaccess) === false){
            $d = $rm.PHP_EOL;
            $d .= $ad.PHP_EOL;

            file_put_contents($htaccess, $d);
        }else{
            $d = '';
            $fh = fopen($htaccess, 'r');
            while($l = fgetc($fh)){
                $d .= $l;
            }
            fclose($fh);

            if (strpos($d, $rm) === FALSE)
                file_put_contents($htaccess, $rm.PHP_EOL);
            if (strpos($d, $ad) === FALSE)
                file_put_contents ($htaccess, $ad.PHP_EOL);
        }
    }

    /**
     * begin upload a single file from your control
     * @param string $ctrlName name of controls which you parse the file in
     * @param integer $sizeLimit limited size of your file (in KB only)
     * @return string name of uploaded file or NULL if fail to upload
     * @Description name of file after upload (not include path)
     */
    public function beginUpload($ctrlName, $sizeLimit = 20000){
        $ctrlName = (string)$ctrlName; $this->error = "";
        if (isset($_FILES[$ctrlName])){
            if(is_array($_FILES[$ctrlName]['name']) === true){
                $this->error = "Cannot upload array file!";
                return null;
            }
            return $this->uploadNormal($ctrlName, $sizeLimit);
        }

        return null;
    }

    /**
     * begin upload multiple files from your controls
     * @param string $ctrlName name of controls which you parse the file in
     * @param integer $sizeLimit limited size of your file (in KB only)
     * @return array of string name of uploaded file or NULL if fail to upload
     * @Description name of file after upload (not include path)
     */
    public function beginUploads($ctrlName, $sizeLimit = 20000){
        $ctrlName = (string)$ctrlName; $this->error = "";
        if (isset($_FILES[$ctrlName])){
            if(is_array($_FILES[$ctrlName]['name']) === false){
                $this->error = "Cannot upload file";
                return null;
            }
            return $this->uploadArrayFile($ctrlName, $sizeLimit);
        }

        return null;
    }

            /**
     * Return error string or null if there were no error
     * @return String Error message
     */
    public function getError(){
        return $this->error;
    }

    private function uploadArrayFile($ctrlName, $sizeLimit = 20000){
        $ctrlName = (string)$ctrlName;
        $result = null;
        if (array_key_exists($ctrlName, $_FILES) == false){
            $this->error = 'No controls bind!';
            return null;
        }
        $ab = count($_FILES[$ctrlName]['name']);

        for ($i=0; $i < $ab; $i++){
            if (is_uploaded_file($_FILES[$ctrlName]['tmp_name'][$i]) === false) return $result;

            if (strpos($_FILES[$ctrlName]['name'][$i], '.') !== false)
                $akf = explode('.', $_FILES[$ctrlName]['name'][$i]);
            else
                $akf = array('', 'daly_no_extesion');

            $ext = strtolower(end($akf));
            if (is_array($this->extension) === false) return $result;
            if (in_array($ext, $this->extension) === false) return $result;
            $size = $sizeLimit * 1024;
            if ($_FILES[$ctrlName]['size'][$i] > $size) return $result;
            if ($_FILES[$ctrlName]['error'][$i] !== 0) return $result;
            $name = $akf[0];
            if ($this->isRandomName == false){
                $name = $this->clearName($name);
                if (strpos($name, '.')>0){
                    $aaa = explode('.', $name);
                    $name = $aaa[0];
                }
            }else{
                $name = date('YmdHis'). $this->getRandom(4);
            }

            $exts = array('php', 'php3', 'php4', 'php5', 'js', 'xml', 'py', 'pl', 'asp', 'aspx', 'jsp');
            if (in_array($ext, $exts)) $ext = 'txt';
            unset($exts);

            $cnt = 1;
            $name1 = $this->start . $name . $this->end;
            while (file_exists($this->path."{$name}.{$ext}")){
                $name = "{$name1} ({$cnt})";
                $cnt = $cnt + 1;
            }
            unset($cnt, $name1);

            $name = $this->start.$name.$this->end;
            try{
                move_uploaded_file($_FILES[$ctrlName]['tmp_name'][$i], $this->path.$name.'.'.$ext);
            }catch(Exception $ex){ return $result; }
            if(is_null($result) === true) $result = array();
            $result[$i] = $name.'.'.$ext;
        }

        return $result;
    }

    private function uploadNormal($ctrlName, $sizeLimit = 20000){
        $ctrlName = (string)$ctrlName;
        if (array_key_exists($ctrlName, $_FILES) == false){
            $this->error = 'No controls bind!';
            return null;
        }

        if (is_uploaded_file($_FILES[$ctrlName]['tmp_name']) == false){
            $this->error = 'No File uploaded!';
            return null;
        }

        if (strpos($_FILES[$ctrlName]['name'], '.') !== false)
            $akf = explode('.', $_FILES[$ctrlName]['name']);
        else
            $akf = array('', 'txt');

        $ext = end($akf);
        if (is_array($this->extension) == false){
            $this->error = 'Allowed extension must be array!';
            return null;
        }

        if (in_array(strtolower($ext), $this->extension) == false){
            $this->error = 'Extension is not allowed!';
            return null;
        }

        $size = $sizeLimit * 1024;
        if ($_FILES[$ctrlName]['size'] < $size == false){
            $this->error = 'File is too large!';
            return null;
        }

        if ($_FILES[$ctrlName]['error'] !== 0) return null;

        $name = $akf[0];
        if ($this->isRandomName == false){
            $name = $this->clearName($name);
            if (strpos($name, '.')>0){
                $aaa = explode('.', $name);
                $name = $aaa[0];
            }
        }else $name = date('YmdHis'). $this->getRandom(4);

        $exts = array('php', 'php3', 'php4', 'php5', 'js', 'xml', 'py', 'pl', 'asp', 'aspx', 'jsp');
        if (in_array($ext, $exts)) $ext = 'txt';
        unset($exts);

        $cnt = 1;
        $name1 = $name;
        while (file_exists($this->path."{$name}.{$ext}")){
            $name = "{$name1} ({$cnt})";
            $cnt = $cnt + 1;
        }
        unset($cnt, $name1);

        $name = $this->start.$name.$this->end;
        move_uploaded_file($_FILES[$ctrlName]['tmp_name'], $this->path.$name.'.'.$ext);
        $this->error = null;
        return $name.'.'.$ext;
    }

    private function clearName($name){
        $result = $name;
        if (strpos($name, '/') !== false){
            $a = explode('/', $name);
            if (strpos('\\', end($a)) !== false){
                $a = explode('\\', end($a));
                $result = end($a);
            }
        }else if(strpos ('\\', $name) !== false){
            $a = explode('\\', $name);
            $result = end($a);
        }
        return $result;
    }

    private function checkLast($str){
        $a = substr($str, strlen($str)-1);
        if ($a == '/') return true;
        else if ($a == '\\') return true;
        return false;
    }

    private function getRandom($length){
        $a = '';
        for ($i = 0; $i<$length; $i++){
            $a .= rand(0, 9);
        }
        return $a;
    }

}
?>