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
    
    /**
     * Backup database and save to specific path. if you are using with large database please considered to use ini_set('max_execution_time', 0);
     * @param string $filePath File path of backed up database
     * @param boolean $isCompress
     * @return string filename in file path which you are provided
     */
    function backupDatabase($filePath, $isCompress = true){
        $date = strtotime(date('Y-m-d H:i:s'));
        $fn = function($fileName, $text){
            file_put_contents($fileName, $text, FILE_APPEND);
        };
        
        if(is_dir($filePath) === false){
            if (mkdir($filePath, 0777, true) == true)
                $fn($filePath . '/index.php', '<?php echo "Access Denie!"; ?>');
        }else if(file_exists($filePath . '/index.php') === false)
            $fn($filePath . '/index.php', '<?php echo "Access Denie!"; ?>');
        
        $fileName = $filePath . '/db_' . date('Y_m_d_H_i_s') . '.sql';
        unset($filePath);
        
        $db_host = array();
        preg_match("/(?<=mysql:host=).*(?=;dbname)/", $this->connectionString, $db_host);
        $db_host = $db_host[0];
        
        $db_name = array();
        preg_match("/(?<=;dbname=).*(?=;)/", $this->connectionString, $db_name);
        $db_name = $db_name[0];
        
        $tables = $this->executeQuery('SHOW FULL TABLES IN `'.$db_name.'`;', array(), PDO::FETCH_KEY_PAIR);
        $result = '';
        $fn($fileName, '# ************************************************************' . PHP_EOL .
                '# Auto Generator written by DalyPHP' . PHP_EOL.
                '# ' . PHP_EOL .
                '# https://www.dalyphp.com/' . PHP_EOL .
                '# Facebook: https://www.facebook.com/narith123' . PHP_EOL .
                '# ' . PHP_EOL .
                '# Host: ' . $db_host . PHP_EOL .
                '# Database: ' . $db_name . PHP_EOL .
                '# Generate Time: ' . date('Y-m-d H:i:s', $date) . PHP_EOL .
                '# ************************************************************' . PHP_EOL . PHP_EOL . PHP_EOL .
                '/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;' . PHP_EOL . 
                '/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;' . PHP_EOL . 
                '/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;' . PHP_EOL .
                '/*!40101 SET NAMES utf8 */;' . PHP_EOL . 
                '/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;' . PHP_EOL . 
                '/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE=\'NO_AUTO_VALUE_ON_ZERO\' */; ' . PHP_EOL . 
                '/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;' .PHP_EOL . PHP_EOL);
        
        foreach($tables as $table=>$type){
            if(strpos($type, 'VIEW') !== false) continue;
            $fn($fileName, '# Dump of table '.$table . PHP_EOL . '# ------------------------------------------------------------'.PHP_EOL);
            $fn($fileName, 'DROP TABLE IF EXISTS `'.$table.'`;'.PHP_EOL);
            $ab = $this->executeQuery('SHOW CREATE TABLE `'.$table.'`', array(), PDO::FETCH_BOTH);
            $ab = $ab[0][1];
            $result .= $ab . ';' . PHP_EOL.PHP_EOL;
            
            $ab = $this->executeQuery('select * from `' . $table . '`', array(), PDO::FETCH_ASSOC);
            if(count($ab) > 0){
                $a_key = array_keys($ab[0]);
                $result .= 'LOCK TABLES `'.$table.'` WRITE;'.PHP_EOL.'/*!40000 ALTER TABLE `'.$table.'` DISABLE KEYS */;'.PHP_EOL.PHP_EOL.'INSERT INTO `'.$table . '`(';
                foreach($a_key as $key){
                    $result .= '`'.$key.'`, ';
                }
                $result = substr($result, 0, strlen($result) - 2) . ') VALUES' . PHP_EOL . ' (';
                foreach($ab as $val){
                    foreach($a_key as $key){
                        if(is_numeric($val[$key]) === false)
                            $result .= '\'' . str_replace('\n', "\\n", addslashes($val[$key])) . '\', ';
                        else $result .= "{$val[$key]}, ";
                    }
                    $result = substr($result, 0, strlen($result) - 2) . '),'.PHP_EOL.' (';
                }
                $result = substr($result, 0, strlen($result) - (3 + strlen(PHP_EOL))) . ';' . PHP_EOL . PHP_EOL . '/*!40000 ALTER TABLE `'.$table.'` ENABLE KEYS */;'.PHP_EOL.'UNLOCK TABLES;'.PHP_EOL;
                
            }
            $fn($fileName, $result . PHP_EOL . PHP_EOL);
            $result = '';
            unset($ab);
        }
        
        foreach($tables as $table=>$type){
            if(strpos($type, 'VIEW') === false) continue;
            $tables = $this->executeQuery('SHOW CREATE VIEW `'.$table.'`;', array(), PDO::FETCH_COLUMN);
            foreach($tables as $table){
                $fn($fileName, '# Dump of view '.$table . PHP_EOL . '# ------------------------------------------------------------'.PHP_EOL);
                $fn($fileName, 'DROP VIEW IF EXISTS `'.$table.'`;'.PHP_EOL);
                $ab = $this->executeQuery('SHOW CREATE VIEW `'.$table.'`', array(), PDO::FETCH_BOTH);
                $ab = preg_replace("/ALGORITHM=.* DEFINER=`.*`@`.*` SQL SECURITY DEFINER /", '', $ab[0][1]);
                
                $fn($fileName, $ab . ';' . PHP_EOL.PHP_EOL);
            }
        }
        unset($tables, $table);
        
        $a_key = array('FUNCTION', 'PROCEDURE');
        foreach($a_key as $key){
            $tables = $this->executeQuery('SHOW '.$key.' STATUS WHERE Db=\''.$db_name.'\';', array(), PDO::FETCH_ASSOC);
            foreach($tables as $table){
                $fn($fileName, '# Dump of ' . strtolower($key) . ' '.$table['Name'] . PHP_EOL . '# ------------------------------------------------------------'.PHP_EOL);
                $fn($fileName, 'DROP '.$key.' IF EXISTS `'.$table['Name'].'`;'.PHP_EOL . 'DELIMITER @$'.PHP_EOL);
                $ab = $this->executeQuery('SHOW CREATE '.$key.' `'.$table['Name'].'`', array(), PDO::FETCH_BOTH);
                $ab = preg_replace("/DEFINER=`.*`@`.*` /", '', $ab[0][2]);
                $fn($fileName, $ab . '@$' . PHP_EOL . 'DELIMITER ;'.PHP_EOL . PHP_EOL);
            }
        }
        
        $fn($fileName, '/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;' . PHP_EOL .
                '/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;' . PHP_EOL . 
                '/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;' . PHP_EOL .
                '/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;' . PHP_EOL .
                '/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;' . PHP_EOL.
                '/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;');
        
        $file = explode('/', $fileName);
        $file = end($file);
        if($isCompress == true && extension_loaded('zip') === true){
            $zip = new ZipArchive();
            if($zip->open($fileName . '.zip', ZipArchive::CREATE) !== true) return $file;
            $zip->addFromString($file, file_get_contents($fileName));
            $zip->close();
            @unlink($fileName);
            $file = $file . '.zip';
        }
        return $file;
    }
    
    /**
     * Restore database from *.sql, *.zip, *.gzip, *.bzip2 file. A compressed file's name must end in .[format].[compression].
     * @param type $fileName File name of your backed up database.
     * @return TRUE if success or string represent error
     */
    function restoreDatabase($fileName){
        if(is_file($fileName) === false) return 'File not exist!';
        
        $isCompress = false;
        $file1 = explode('.', $fileName);
        $extension = strtolower(end($file1));
        if(in_array($extension, array('zip', 'gzip', 'bzip2')) === true){
            if(extension_loaded('zip') !== true) return 'Unable to uncompress file!';
            
            $filePath = explode('/', $fileName);
            array_pop($filePath);
            $filePath = implode('/', $filePath);
            
            $zip = new ZipArchive();
            $zip->open($fileName);
            $zip->extractTo($filePath);
            
            array_pop($file1);
            $fileName = implode('.', $file1);
            
            $isCompress = true;
            unset($filePath, $zip);
        }else if($extension != 'sql') return 'Unsupported extension!';
        unset($file1, $extension);
        
        $this->executeUpdate(file_get_contents($fileName));
        if($isCompress === true) @unlink($fileName);
    }
}
?>