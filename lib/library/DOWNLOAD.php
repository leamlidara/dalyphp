<?php
class DOWNLOAD{

    private $file, $name, $error='';
/**
     * 
     * @param type $filepath STRING absolute path of file
     * @param type $name STRING this name is a name which will be save into local
     */
    public function __construct($filepath, $name){
            $a = array('//'=>'/', '\\\\'=>'\\', '\\/'=>'/', '/\\'=>'/');
            foreach($a as $key=>$val) $filepath = str_replace($key, $val, $filepath);
        $this->file = $filepath; $this->name = $name;
    }

    /**
     * @return boolean TRUE if success or FALE if fail and getError() to get error message
     */
    public function download(){
        return $this->output_file($this->file, $this->name);
    }

    public function getError(){return $this->error;}

    private function output_file($file, $name, $mime_type='')
    {
         //Check the file premission
         if(!is_readable($file)){ 
             $this->error = 'File not found or inaccessible!';
             return false;
         }

         $size = filesize($file);
         $name = rawurldecode($name);

         /* Figure out the MIME type | Check in array */
         $known_mime_types=array(
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'html' => 'text/html',
            'htm' => 'text/html',
            'exe' => 'application/octet-stream',
            'zip' => 'application/zip',
            'doc' => 'application/msword',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            'gif' => 'image/gif',
            'png' => 'image/png',
            'jpeg'=> 'image/jpg',
            'jpg' =>  'image/jpg',
            'php' => 'text/plain'
         );

         if($mime_type==''){
             $file_extension = strtolower(substr(strrchr($file,'.'),1));
             if(array_key_exists($file_extension, $known_mime_types)){
                $mime_type=$known_mime_types[$file_extension];
             } else {
                $mime_type='application/force-download';
             }
         }

        //turn off output buffering to decrease cpu usage
         @ob_end_clean();
         
         // required for IE, otherwise Content-Disposition may be ignored
         if(ini_get('zlib.output_compression'))
          ini_set('zlib.output_compression', 'Off');

         /* Will output the file itself */
         $chunksize = 1*(1024*1024); //you may want to change this
         $bytes_send = 0;
         if ($file = fopen($file, 'r'))
         {
            header('Content-Type: ' . $mime_type);
            header('Content-Disposition: attachment; filename="'.$name.'"');
            header('Content-Transfer-Encoding: binary');
            header('Accept-Ranges: bytes');

            // The three lines below basically make the download non-cacheable
            header('Cache-control: private');
            header('Pragma: private');
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

            if(isset($_SERVER['HTTP_RANGE'])){
                list($a, $range) = explode('=',$_SERVER['HTTP_RANGE'],2);
                list($range) = explode(',',$range,2);
                list($range, $range_end) = explode('-', $range);
                $range=intval($range);
                if(!$range_end) $range_end=$size-1;
                else $range_end=intval($range_end);

                $new_length = $range_end-$range+1;
                header('HTTP/1.1 206 Partial Content');
                header("Content-Length: $new_length");
                header("Content-Range: bytes $range-$range_end/$size");
                fseek($file, $range);
            }else{
                $new_length=$size;
                header("Content-Length: {$size}");
            }

            while(!feof($file) && (!connection_aborted()) && ($bytes_send<$new_length)){
                $buffer = fread($file, $chunksize);
                echo $buffer;
                flush();
                $bytes_send += strlen($buffer);
            }
            fclose($file);
            exit(0);
         } else{
             $this->error = 'Error - can not open file.';
             return false;
         }
    }
}
?>