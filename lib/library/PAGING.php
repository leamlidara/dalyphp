<?php
class PAGING{
    private $db;
    private $itemPerPage;
    private $sql;
    private $data;
    private $cachingPageCount = array();

    private $pcount, $icount;
    
    /**
     * Separator between page and page number
     * Ex: www.example.com/index.php?page=1
     * @var ?page= is a separator
     * @var type String
     */
    public $separator;

    public function __construct($DB_HOST, $DB_NAME, $DB_USER = 'root', $DB_PASS = '', $itemPerPage = 5, $CHAR_SET = '')
    {
        $this->db = new DATABASE($DB_HOST, $DB_NAME, $DB_USER, $DB_PASS, $CHAR_SET);
        $this->itemPerPage = $itemPerPage;
        if ($this->itemPerPage < 2) $this->itemPerPage = 5;
        $this->separator = '/';
    }
    
    /**
     * query data from your sql database
     * @param string $sql query sql to query data
     * @param type $data list of array which contents parameters and value
     * @param int $pageNumber Page number which you want to query data
     * @param type $setFetchmode fetch mode of your SQL
     * @return array data return data of your sql
     */
    public function executeQuery($sql, $data = array(), $pageNumber = 1, $setFetchmode = PDO::FETCH_ASSOC){
        if (is_numeric($pageNumber) == false || $pageNumber < 1) $pageNumber = 1;
        
        $offset = ($pageNumber - 1) * $this->itemPerPage;
        $this->sql = $sql;
        $this->data = $data;
        $orSql = $sql;
        if (substr(strtolower($sql), 0, 5) !== 'call ')
            $sql = $sql." limit {$offset}, {$this->itemPerPage}";
        
        $rows = $this->db->executeQuery($sql, $data, $setFetchmode);
        $this->cachingPageCount[$sql . serialize($data)] = count($rows);
        $this->db->close();
        return $rows;
    }
    
    /**
     * Set Items per page to be query
     * @param int $itemsPerPage 
     */
    public function setItemsPerPage($itemsPerPage){
        $this->itemPerPage = $itemsPerPage;
    }

    /**
    * Get total items count of the currenct query. This must be called after getPageCount or getPagingControl
    *
    */
    public function getCountItems(){ return $this->icount; }

    /**
    * Get total pages count of the currenct query. This must be called after getPageCount or getPagingControl
    *
    */
    public function getCountPage(){ return $this->pcount; }

    /**
     * get number of page from your select sql statement
     * @param type $normalSql sql which you use to query data
     * @return int number of page
     */
    public function getPageCount($normalSql, $data = array()){
        if (array_key_exists($normalSql.  serialize($data), $this->cachingPageCount)) $this->icount = $this->cachingPageCount[$normalSql.  serialize($data)];
        else{
            if (substr(strtolower($normalSql), 0, 5) !== 'call ')
                $this->icount = $this->db->executeScalare("select count(1) from ({$normalSql}) a", $data);
            else
                $this->icount = $this->db->executeScalare($normalSql, $data);
            
            $this->cachingPageCount[$normalSql . serialize($data)] = $this->icount;
        }
        $this->db->close();
        
        $page = 0;
        if (is_numeric($this->icount)){
            if ($this->icount > $this->itemPerPage){
                $page = (int)($this->icount / $this->itemPerPage);
                
                if ($this->icount % $this->itemPerPage > 0) $page += 1;
            }else
                $page = 1;
        }
        $this->pcount=$page;
        //$this->sql = $normalSql;
        //$this->data = $data;
        return $page;
    }
   
    /**
     * Get paging control
     * @param String $currentUrlOrAjaxUrl Current URL that you are standing on
     * @param String $currentPage Current page that you are standing on
     * @param String $postfixString string to be add to the end of your URL string
     * @param String $method <b>GET | POST</b> the ajax request method or empty for normal query.<b>Note:</b> all data such as search parameters (but not page number) must be include in URL.
     * @param String $callback callback Javascript after ajax performed with 'msg' as parameter which represent respond text.
     * @param String $sqlForCountItems SQL which use to count page (Manually)
     * @param Array $data Data for this query
     * @return String control String of your Control paging
     */
    public function getPagingControl($currentUrlOrAjaxUrl, $currentPage, $postfixString = '', $method='', $callback='', $sqlForCountItems = '', $data = array()){
        if (is_numeric($currentPage) === false || $currentPage < 1) $currentPage = 1;
        if ($sqlForCountItems === '')
            $count = $this->getPageCount($this->sql, $this->data);
        else
            $count = $this->getPageCount($sqlForCountItems, $data);
        
        $this->db->close();
        
        if ($currentPage > $count) $currentPage = $count;
        
        if ($count > 1){
            $method = strtolower($method);
            if ($method != ''){
                if ($method != 'get' && $method != 'post') $method = 'get';
                $method = "$.$method(currentUrl + num + post, function(msg){ {$callback} });";
            }
            
            $p = "<script type='text/javascript'>
                if (typeof($) == 'undefined') console.error('Error: JQuery is missing! Please import at least JQuery 2.x.');
                else{
                    $(function(){
                        if (typeof(window['gotoPageNumber_']) == 'undefined'){
                            window['gotoPageNumber_'] = function(ctrl, num){
                                ctrl = ctrl.closest('div.pagination');
                                var currentUrl = decodeURIComponent(decodeURIComponent(ctrl.find('.pagingURL').val()));
                                var post = decodeURIComponent(decodeURIComponent(ctrl.find('.pagingPost').val()));
                                var met = ctrl.find('.pagingCallback').val();
                                if (met != '') eval(decodeURIComponent(met));
                                else window.location.href = currentUrl + num + post;
                            }
                        }
                        $('.btnPagingFirst, .btnPagingBack, .btnPagingNext, .btnPagingLast').off('click');
                        $('.btnPagingFirst').on('click', function(){ gotoPageNumber_($(this), 1); });
                        $('.btnPagingLast').on('click', function(){ gotoPageNumber_($(this), $(this).closest('div.pagination').find('.pagingLast').val()); });
                        $('.btnPagingBack').on('click', function(){ gotoPageNumber_($(this), parseInt($(this).closest('div.pagination').find('.pagingCurrentPage').val()) - 1); });
                        $('.btnPagingNext').on('click', function(){ gotoPageNumber_($(this), parseInt($(this).closest('div.pagination').find('.pagingCurrentPage').val()) + 1); });
                        $('.txtPagingControl').keypress(function(e){
                            var c = e.keyChar || e.keyCode || e.which;
                            if (c>47&&c<58) return true;
                            if (c==13) {
                                if(parseInt($(this).closest('div.pagination').find('.pagingLast').val()) >= parseInt($(this).val())) gotoPageNumber_($(this), $(this).val());
                            }
                            return false;
                        });
                    });
                }
        </script>";
            
            $p = str_replace(PHP_EOL, '', $p . $this->getPagingControlOnly($currentUrlOrAjaxUrl, $currentPage, $postfixString, $count, $method));
            while(strpos($p, '  ') !== false) $p = str_replace('  ', ' ', $p);
        }else{
            $p = '';
        }
        return $p;
    }
    
    private function getPagingControlOnly($currentURL, $currentPage, $postfixString = '', $totalPage = 0, $method = ''){
        $p = '';
        $postfixString = urlencode(urlencode($postfixString));
        $currentURL = urlencode(urlencode($currentURL . $this->separator));
        $method = htmlentities($method, ENT_QUOTES);
        $p .= "<div class='pagination' style='display:block; margin:0; padding:0;'>";
        $p .= "<input type='hidden' class='pagingURL' value='{$currentURL}'/>";
        $p .= "<input type='hidden' class='pagingCurrentPage' value='{$currentPage}'/>";
        $p .= "<input type='hidden' class='pagingPost' value='{$postfixString}'/>";
        $p .= "<input type='hidden' class='pagingLast' value='{$totalPage}'/>";
        $p .= "<input type='hidden' class='pagingCallback' value='{$method}'/>";

        if ($currentPage > 1){
            $ab = 1;
            $p .= "<input type='button' name='btnPagingFirst' class='btnPagingFirst' value='First'/>";
            $ab = $currentPage - 1;
            $p .= "<input type='button' name='btnPagingBack' class='btnPagingBack' value='Previous'/>";
        }
        $p .= '<input type="text" class="txtPagingControl" name="txtPagingControl" placeholder="'.$currentPage
                .' OF '.$totalPage.'" style="text-align: center;" />';

        if ($currentPage < $totalPage){
            $ab = $currentPage + 1;
            $p .= "<input type='button' name='btnPagingNext' class='btnPagingNext' value='Next'/>";
            $ab = $totalPage;
            $p .= "<input type='button' name='btnPagingLast'  class='btnPagingLast' value='Last'/>";
        }
        $p .= "</div>";
        return $p;
    }
}
?>