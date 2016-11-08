<?php
class URL{
    private $url;
    private $fullUrl;
    private $index = 'index';
    public $skipIndexCount;

    /**
     * Get URL
     * @param interger $skipUrlIndexCount number of index that you want to skip
     */
    public function __construct($skipUrlIndexCount = 0) {
        $this->skipIndexCount = $skipUrlIndexCount;
        $abc = $this->removeStartSlash($_SERVER['REQUEST_URI']);
        if (strpos($abc, '/') == true){
            $abc = $this->getFirstPage($abc);
            $this->url = explode('/', $abc);
        }
        else
        {
            if (strpos($abc, '\\') == true)
                $this->url = explode('\\', $abc);

            if ($abc == '')
                $abc = $this->index;

            $this->url[0] = $this->getFirstPage($abc);
        }

        $this->fullUrl = $abc;
    }

    /**
     * Get the first page if there were a GET method in URL
     */
    private function getFirstPage($url){
        $pos = strpos($url, '?');
        if ($pos !== FALSE){
            //If this is a url which use index
            if ($pos === 0) $url = $this->index;
            else $url = substr($url, 0, $pos - strlen($url));
        }
        return $url;
    }


    /**
     * Count current page 
     * @return integer number of Page
     */
    public function countPage(){
        $cnt = explode('/', $this->fullUrl);
        return count($cnt);
    }

    /**
     * Get the first page of your URL
     * @return string first current page
     */
    public function getPage(){
        if ($this->url == '' || $this->url == NULL) return $this->index;
        else {
            if (isset($this->url[$this->skipIndexCount]))
                return $this->url[$this->skipIndexCount];
            else return null;
        }
    }

    /**
     * Get sub page of specific index or String name with GET method
     * @param int $indexOrStringName order number of page
     * @return string name of page or NULL if name of page on this index is undefined
     */
    public function getSubPage($indexOrStringName){
        if (is_numeric($indexOrStringName)){
            $index = (((int)$indexOrStringName) + $this->skipIndexCount);

            if ($index < $this->skipIndexCount) $index = $this->skipIndexCount;

            if ($this->url == '') return $this->index;
            else {
                if (isset($this->url[$index]))
                    return $this->url[$index];
                else
                    return null;
            }
        }else{
            if (isset($_GET[$indexOrStringName]) === false) return null;
            $index = $_GET[$indexOrStringName];
            while(is_array($index) == true){
                if(count($index)> 1) break;
                reset($index);
                $index = $index[key($index)];
            }
            return $index;
        }
    }

    /**
     * Get sub page from specific index or string name with GET method by convert into integer
     * @param int|string $indexOrStringName order number of page or name of GET method
     * @return string name of page in integer or NULL if page on this index is undefined
     */
    public function getInt($indexOrStringName){
        $i = $this->getSubPage($indexOrStringName);
        if (is_numeric($i) === false) return null;
        return (int)$i;
    }

    /**
     * Get sub page from specific index or string name with GET method by convert into  float
     * @param int|string $indexOrStringName order number of page or name of GET method
     * @return string name of page in float or NULL if page on this index is undefined
     */
    public function getFloat($indexOrStringName){
        $i = $this->getSubPage($indexOrStringName);
        if (is_float($i) === false) return null;
        return (float)$i;
    }

    /**
     * Get sub page of specific index or String name with GET method
     * @param int $indexOrStringName order number of page
     * @return string name of page or NULL if name of page on this index is undefined
     */
    public function getString($indexOrStringName){
        return $this->getSubPage($indexOrStringName);
    }

    /**
     * Convert current URL to array
     * @return array Array of URL
     */
    public function toArray(){
        return $this->url;
    }

    /**
     * Get current url of your web browser navegator
     * @return string Full url
     */
    public function getFullUrl(){
        return $this->removeStartSlash($_SERVER['REQUEST_URI']);
    }

    private function removeStartSlash($str){
        $abc = substr($str, 0, 1);
        if ($abc == '\\' || $abc == '/') $str = substr($str, 1);

        return strip_tags($str);
    }
}
?>
