<?php
class DATABASE {
    private $connectionString, $db_pwd, $db_usr, $db;

    /**
    * Connect to Database using PDO Object. If you want to connect as UTF-8 you need to add ';charset=utf8' to DB_NAME after your Database'name
    */
    public function __construct($DB_HOST, $DB_NAME, $DB_USER = 'root', $DB_PASS = '', $CHAR_SET = '') {
        if ($CHAR_SET != '') $CHAR_SET = "charset={$CHAR_SET}";
        // $this->connectionString = 'mysql:host=' . $DB_HOST . ';dbname=' . $DB_NAME . ';' . $CHAR_SET;
        $this->connectionString = "mysql:host={$DB_HOST};dbname={$DB_NAME};{$CHAR_SET}";
        $this->db_pwd = $DB_PASS;
        $this->db_usr = $DB_USER;
        //parent::__construct('mysql:host='.$DB_HOST.';dbname='.$DB_NAME, $DB_USER, $DB_PASS);  
        //parent::setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * TRUE if you want this object to be close when finish execute, or FALSE if you do not want this object to be close when finish execute
     * @var Boolean 
     */
    public $autoClose = true;

    /**
     * Open connection for DATABASE object
     * @return TRUE on success of FALSE on failure
     */
    function open(){
        try{
            if ($this->db == null){
                $this->db = new PDO($this->connectionString, $this->db_usr, $this->db_pwd);
                $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                return TRUE;
            }
        }catch(Exception $ex){
            echo $ex->getMessage();
        }
        return FALSE;
    }

    /**
     * Close connection of DATABASE object to release memory connection  and make sure it still work with other object 
     * without showing message MAX_USER_CONNECTION
     */
    function close(){
        $this->db = null;
    }

    /**
     * Begin Transaction, after you this method is being called you need to use commit() to make the query (insert/delete/update) works.
     * @return TRUE on success or FALSE on failure
     */
    function beginTransaction(){
        $this->open();
        $this->autoClose = false;
        return $this->db->beginTransaction();
    }

    /**
     * Commit Transaction, commit your database work that is query after beginTransaction is called.
     * @return TRUE on success or FALSE on failure
     */
    function commit(){
        $this->autoClose = true;
        try{
            $a = $this->db->commit();
        }catch(Exception $ex){
            $a = FALSE;
        }
        $this->close();
        return $a;
    }

    /**
     * Rollback Transaction, rollback transaction that you parse in the past after beginTransaction is called.
     * @return TRUE on success or FALSE on failure
     */
    function rollback(){
            $this->autoClose = true;
        try{
            $a = $this->db->rollBack();
        }catch(Exception $ex){
            $a = FALSE;
        }
        $this->close();
        return $a;
    }

    /**
     * executeQuery use to get data from current database
     * @param stirng $sql An sql string
     * @param array $data parameter to bind (include parameter name and value)
     * @param constant $setFetchmode A PDO fetch mode 
     * @return array array of rows
     */
    public function executeQuery($sql, $data=array(), $setFetchmode=PDO::FETCH_ASSOC){
        $this->open();
        $prepare = $this->db->prepare($sql);
        $this->bindData($prepare, $data, true);

        $result = $prepare->execute() or die();
        $rows = array();
        if (count($result) > 0)
            $rows = $prepare->fetchAll($setFetchmode);
        
        if ($this->autoClose == TRUE) $this->close();
        $prepare = null;
        return $rows;
    }

    private function bindData(&$db, $data, $isSelect=false){
        if (is_array($data)){
            foreach ($data as $key=>$value){
                if (!is_array($value)){
                    $value1 = $this->securityEnc($value);
                    if ($isSelect == true){while(strpos($value1, '%%') !== false) $value1 = str_replace ('%%', '%', $value1);}
                    $db->bindValue($key,$value1);
                }else{
                    foreach($data as $d1)
                        $this->bindData($db, $d1);
                }
            }
        }
    }

    private function securityEnc($str){
        //XSS Prevention 
        $ret = SECURITY::cleanXXS($str);

        //SSI Prevention
        $ret = str_replace('#', htmlspecialchars('#'), $ret);

        //Other Prevention
        $ret = str_replace('\\', htmlspecialchars('\\'), $ret);

        return trim($ret);
    }

    /**
     * Use to select the first element of the last row
     * @param string $sql  An sql string
     * @param array $data parameter to bind (include parameter and value)
     * @return Mix the first element of the last row or NULL on fail
     */
    public function executeScalare($sql, $data = array()){
        $result = $this->executeQuery($sql, $data, PDO::FETCH_NUM);
        if (count($result) > 0){
            $row = $result[count($result) - 1];
            return $row[count($row)-1];
        }
        return null;
    }

    /**
     * executeUpdate use to change value in database
     * @param string $sql An sql string
     * @param array $data parameter to bind (include parameter and value)
     * @return INT return number of effected rows
     */
    public function executeUpdate($sql, $data=array()){
        $this->open();
        $prepare = $this->db->prepare($sql);
        $this->bindData($prepare, $data);

        $prepare->execute();
        $b = $prepare->rowCount();
        if ($this->autoClose == TRUE) $this->close();
        $prepare = null;
        return $b;
    }

    /**
     *
     * @param string $table
     * @param string $condition
     * @param integer $limit
     * @return integer Affected Rows
     * 
     */
    public function delete($table,$condition,$limit=1){
        $this->open();
        return $this->executeUpdate("DELETE FROM {$table} WHERE {$condition} LIMIT {$limit}");
    }
}
?>