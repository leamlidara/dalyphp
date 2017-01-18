<?php
class ROUTE{
    private $p = null;
    public $showError;
    public $errorFile;
    public $skipUrlIndexCount;
    public function __construct(){
        $this->errorFile = '../error-log';
        $this->skipUrlIndexCount = 0;
    }

    /**
     * use to set default page for your website in case other page not found.
     * @param String $pageName set default page
     */
    public function defualt($pageName){
        if (file_exists(CONTROLLER_.$pageName.'Controller.php'))
            $this->p = $pageName;
    }

    /**
     * run your page
     */
    public function run(){
        if(is_numeric($this->skipUrlIndexCount) === false || $this->skipUrlIndexCount < 0) $this->skipUrlIndexCount = 0;
        $url = new URL($this->skipUrlIndexCount);
        if ($this->showError == false){
            error_reporting(E_ALL ^ E_NOTICE);
            ini_set('display_errors', 0);
        }else{
              error_reporting(E_ALL);
              ini_set('display_errors', 1);
       }
        @header('X-Powered-By: DalyPHP');
        @header('x-frame-options: SAMEORIGIN');
        @header('X-Content-Type-Options: nosniff');

        $session = new SESSION();
        $session->checkSecurity();
        $session->{DATA::urlString} = $url;
        $session->{DATA::ctrlString} = new CONTROLS();
        $session->{DATA::controllerString} = new CONTROLLER();
        $session->{DATA::modelString} = new MODEL();
        $session->{DATA::viewString} = new VIEW();

        $Controller = new CONTROLLER($this->skipUrlIndexCount);
        $Controller->defaultPage = $this->p;

        $obj = str_replace(array('-', '.'), '_', $url->getPage());
        $obj = $Controller->get($obj);

        //run if page not found
        if ($obj == null) {
            if ($this->p != null) $obj = $Controller->get($this->p);
            else{
                echo 'There were no default page on this server.'; exit();
            }
        }

        //get request method
        $method = isset($_SERVER['REQUEST_METHOD']) === true ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';

        //Check if Local or hosting
        $isHosting = true;
        $ip = new SERVER();
        $ip = $ip->get(SERVER::CLIENT_IP);
        if ($ip == '127.0.0.1' || $ip == '::1') $isHosting = false;
        unset($ip);

        //loading controller with request method
        if (method_exists($obj, $method)){
            $this->invokeMethod($obj, $method, $url);
        }else if ($isHosting === true){
            header('HTTP/1.1 301 Moved Permanently');
            exit();
        }else{
            echo 'No method were found!'; exit();
        }

        if ($isHosting === true){
            $content = @ob_get_contents();
            @ob_clean();

            echo $this->minify($content);
            unset($content);
        }
        @ob_flush();

        if ($this->showError == false){
            $this->removeOldLog("error_log");
            $error = error_get_last();
            if ($error !== null){
                $this->removeOldLog($this->errorFile);
                if(strpos($error['message'], 'Automatically populating $HTTP_RAW_POST_DATA') === false)
                    file_put_contents($this->errorFile, '['.date("Y-m-d h:i:s A")."] Error: ".$error['type']." ".$error['message']." On file: ".$error['file'].' line '.$error['line'].PHP_EOL, FILE_APPEND);
            }
        }

        $session->destroy(DATA::ctrlString);
        $session->destroy(DATA::controllerString);
        $session->destroy(DATA::modelString);
        $session->destroy(DATA::viewString);
        $session->destroy(DATA::urlString);
        if (count($session->get("ctrl_php_dara_frmWork168Cache")) > 50) $session->destroy("ctrl_php_dara_frmWork168Cache");
    }
    
    private function minify($html){
        $fnCmt = function($input){
            $tRegex = '"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'';
            
            $texts = array();
            preg_match_all("/{$tRegex}/", $input, $texts);
            if(count($texts) > 0) $texts = $texts[0];
            foreach($texts as $text){
                if(preg_match("/\\\n/", $text) === 1){
                    $input = str_replace($text, str_replace(array("\\\r\n", "\\\n"), '', $text), $input);
                }
            }
            
            $split = preg_split('/\n/', $input);
            $input = '';
            foreach($split as $s){
                if(strpos($s, '//') !== false){
                    if(preg_match('/^(\s*\/\/.*)/', $s) === 1) continue;
                    $s1 = explode('//', $s);
                    if(count($s1) === 2){
                        $s2 = substr_count($s1[0], '\'');
                        $s3 = substr_count($s1[0], '"');
                        if(($s2 > 0 && $s2 % 2 == 0) || ($s3 > 0 && $s3 % 2 == 0)) $input .= $s1[0];
                        else $input .= $s1[0] . '//' . $s1[1];
                    }else{
                        $output = $s1[0];
                        if(strpos($output, '\'') === false && strpos($output, '"') === false){
                            $input .= $output;
                        }else{
                            $cnnnn = count($s1);
                            for($i=1; $i<$cnnnn; $i++){
                                $s2 = substr_count($output, '\'');
                                $s3 = substr_count($output, '"');
                                if(($s2 > 0 && $s2 % 2 == 0) || ($s3 > 0 && $s3 % 2 == 0)) break;

                                $output .= '//'.$s1[$i];
                            }
                            $input .= $output;
                        }
                        unset($output);
                    }
                    unset($s1, $s2, $s3);
                }else $input .= $s;
                unset($s);
            }
            unset($split);
            
            $cmts = array();
            preg_match_all('/^(\s*\/\/.*)|^(\s*\/\*[\s\S]*?\*\/)/m', $input, $cmts);
            if(count($cmts) > 0){
                $cmts = $cmts[0];
                foreach($cmts as $cmt){
                    $isContain = 0;
                    foreach($texts as $text){
                        if(strpos($text, $cmt) !== false){
                            $isContain = 1;
                            break;
                        }
                    }
                    if($isContain === 0) $input = str_replace($cmt, '', $input);
                }
            }
            return $input;
        };
        
        $fncss = function($css) use (&$fnCmt){
            $css = $fnCmt($css);
            $css = preg_split('/(\/\*[\s\S]*?\*\/|"(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\')/', $css, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            $output = '';
            foreach($css as $cc){
                if(trim($cc) === '') continue;
                $c1 = substr($cc, -1);
                if(($cc[0] === '"' && substr($cc, -1) === '"') || ($cc[0] === "'" && substr($cc, -1) === "'") || (strpos($cc, '/*') === 0 && substr($cc, -2) === '*/')){
                    if($cc[0] === '/' && strpos($cc, '/*!') !== 0) continue;
                    $output .= $cc;
                } else {
                    if(stripos($cc, 'calc(') !== false) {
                        $c1 = array();
                        if(preg_match_all('/\b(calc\()\s*(.*?)\s*\)/i', $cc, $c1) === 1){
                            $cc = str_replace($c1[0][0], preg_replace(array('/\s+/', '/\s*([~!@*\(\)=\{\}\[\]:;,>\/])\s*/'), array(' ', '$1'), $c1[2][0]), $cc);
                        }
                    }else{
                        $cc = preg_replace(array("/\b0\.(\d)*/", "/\b0+px/"), array('.$1', '0'), $cc);
                        $cc = preg_replace(array('/;+([;\}])/', '/\s*([~!@*\(\)+=\{\}\[\]:;,>\/])\s*/', '/(^|[\{\}])(?:[^\{\}]+)\{\}/', '/;+([;\}])/'), '$1', $cc);
                        $cc = preg_replace(array('/\s+/'), array(''), $cc);
                        $output .= $cc;
                    }
                }
            }
            return $output;
        };
        
        $fnjs = function($js) use (&$fnCmt){
            $js = $fnCmt($js);
            $js = preg_split('/("(?:[^"\\\]++|\\\.)*+"|\'(?:[^\'\\\\]++|\\\.)*+\'|\/\*[\s\S]*?\*\/|\/[^\n]+?\/(?=[.,;]|[gimuy]|$))/', $js, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            $output = '';
            
            foreach($js as $jj){
                if(trim($jj) == '') continue;
                $j1 = substr($jj, -1);
                if(($jj[0] === '"' && $j1 === '"') || ($jj[0] === '\'' && $j1 === '\'') || ($jj[0] === '/' && $j1 === '/')) {
                    if(strpos($jj, '//') === 0 || (strpos($jj, '/*') === 0 && strpos($jj, '/*!') !== 0 && strpos($jj, '/*@cc_on') !== 0)) continue;
                    $output .= $jj;
                }else{
                    $output .= preg_replace(array('/(\s*\/\/.*)$/m', '/(\/\*.*\*\/)/m', '/\s*([!%&*\(\)\-=+\[\]\{\}|;:,.<>?\/])\s*/', '/[;,]([\]\}])/', '/\breturn\s+/'), array('', '', '$1', '$1', 'return '), $jj);
                }
            }
            return $output;
        };

        $fnhtml = function($html) use (&$fncss){
            $html = trim($html);
            $fnInlineStyle = function($input) use (&$fncss){
                $matches1 = array();
                preg_match_all('/\sstyle=[\'"](.*?)[\'"](\s[a-z]|\s+>|>)/i', $input, $matches1);
                if(count($matches1) > 1){
                    $matches1 = $matches1[1];
                    foreach($matches1 as $m){
                        $input = str_replace($m, $fncss($m), $input);
                    }
                }
                return $input;
            };
            
            if(preg_match('/^<(pre|code|textarea)/i', $html) === 1) return $fnInlineStyle($html);
            
            $matches = array();
            if(preg_match_all('/<\s*([^\/\s]+)\s*(?:>|(\s[^<>]+?)\s*>)/', $html, $matches) !== 1){
                return preg_replace('/\s+/', ' ', $fnInlineStyle($html));
            }
            
            $matches[1] = $matches[1][0];
            
            if(isset($matches[2]) === false) return '<'.$matches[1] . '>';
            $matches[2] = $matches[2][0];
            
            $m1 = $matches[1];
            $m2 = $matches[2];
            
            $matches[2] = $fnInlineStyle($matches[2]);
            
            $m = '<' . $matches[1] . preg_replace(array(
                            '/\s(checked|selected|async|autofocus|autoplay|controls|defer|disabled|hidden|ismap|loop|multiple|open|readonly|required|scoped)(?:=([\'"]?)(?:true|\1)?\2)/i',
                            '/\s*([^\s=]+?)(=(?:\S+|([\'"]?).*?\3)|$)/', '/\s+\/$/'
                        ), array(' $1', ' $1$2', '/'),  str_replace("\n", ' ', $matches[2])) . '>';
            unset($matches, $ms);
            return str_replace('<'.$m1.$m2.'>', $m, $html);
        };
        
        $html = preg_replace('/(<(?:img|input)(?:\s[^<>]*?)?\s*\/?>)\s+/i', '$1', $html);
        $html = preg_split('/(<\!--[\s\S]*?-->|<pre(?:>|\s[^<>]*?>)[\s\S]*?<\/pre>|<code(?:>|\s[^<>]*?>)[\s\S]*?<\/code>|<script(?:>|\s[^<>]*?>)[\s\S]*?<\/script>|<style(?:>|\s[^<>]*?>)[\s\S]*?<\/style>|<textarea(?:>|\s[^<>]*?>)[\s\S]*?<\/textarea>)/i', $html, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $output = '';
        
        foreach($html as $v) {
            $v = trim($v);
            if($v == '') continue;
            if(preg_match('/<script.*>/', $v) === 1) $output .= $fnjs($v);
            else if(preg_match('/<style.*>/', $v) === 1) $output .= $fncss($v);
            else if($v[0] === '<' && substr($v, -1) === '>'){
                if($v[1] === '!' && strpos($v, '<!--') === 0) {
                    if(substr($v, -12) !== '<![endif]-->') continue;
                    $output .= $v;
                } else $output .= $fnhtml($v);
            }else $output .= preg_replace('/\s+/', ' ', $v);
        }
        
        unset($html);
        $output = preg_replace(array('/>([\n\r\t]\s*|\s{2,})</', '/\s+(<\/[^\s]+?>)/', '/\s<\/?(meta|head|body|link|script|html)/i'), array('><', '$1', '<$1'), $output);
        
        return $output;
    }

    private function appendHtmlToHead($html){
        $abc = @ob_get_contents();
        $result = "";
        $headPattern = "<head>";
        @ob_clean();
        echo $abc;
    }

    private function invokeMethod($obj, $method, $url){
        $refl = new ReflectionMethod(get_class($obj), $method);
        $numParams = $refl->getNumberOfParameters();

        if ($numParams > 0)
        {
            $param = explode('/', $url->getFullUrl());
            $cnt = count($param);
            if (strpos($param[$cnt-1], "?") > 0){
                $c = explode("?", $param[$cnt-1]);
                $param[$cnt-1] = $c[0];
            }
            foreach($_GET as $key=>$val){
                $param[$key] = $val;
            }
            $obj->$method($param);
        }else{
            $obj->$method();
        }
    }

    private function removeOldLog($filename){
        if (is_file($filename) === false) return;
        $contet = file_get_contents($filename);
        $arr = array();
        preg_match_all("/(?<=\[)\d{2,4}-.{2,3}-\d{2,4}(?=\s\d{2}:\d{2}:\d{2}\s.*?])/u", $contet, $arr);
        if (count($arr) < 1) return;
        $arr = array_unique($arr[0]);
        $date = strtotime(date("Y-m-d") . " - 7day");
        $spliter = "2000-01-01";
        foreach($arr as $a){
            $d = strtotime($a);
            if ($d > $date) continue;
            if ($d > strtotime($spliter)) $spliter = $a;
        }
        if ($spliter == "2000-01-01") return;
        $contet = explode($spliter, $contet);
        $contet = $contet[count($contet) - 1];
        $contet = str_replace(strtok($contet, "\n"), "", $contet);
        @unlink($filename);
        file_put_contents($filename, $contet, FILE_APPEND);
    }
}
?>