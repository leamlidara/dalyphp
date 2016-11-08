<?php
class SERVER
{
    function getIP(){
    	if (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
    		return $_SERVER['HTTP_CF_CONNECTING_IP'];
    	if (isset($_SERVER['REMOTE_ADDR']))
    		return $_SERVER['REMOTE_ADDR'];
    	if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
    		return $_SERVER['HTTP_X_FORWARDED_FOR'];
        if (isset($_SERVER['HTTP_CLIENT_IP']))
    		return $_SERVER['HTTP_CLIENT_IP'];
    	if (isset($_SERVER['HTTP_FORWARDED']))
    		return $_SERVER['HTTP_FORWARDED'];
    	
    	return 'Unknown';
    }
    
    function getSiteURL() {
        $protocol = '//';
        $domainName = $_SERVER['HTTP_HOST'];
        return $protocol.$domainName;
    }
    
    private function getProtocol(){
        return ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
    }

    private $index = array(
        1 => 'PHP_SELF', 
        2 => 'HTTP_HOST', 
        3 => 'HTTP_ACCEPT', 
        4 => 'HTTP_USER_AGENT',
        5 => 'SERVER_SOFTWARE',
        6 => 'SERVER_NAME',
        7 => 'SERVER_ADDR',
        8 => 'SERVER_PORT',
        9 => 'REQUEST_METHOD',
        10 => 'REQUEST_URI',
        11 => 'REDIRECT_STATUS',
        13 => 'SITE_PROTOCOL',
        14 => 'HTTP_REFERER'
    );

    /**
     * Get IP address of client marchine
     */
    const CLIENT_IP = 0;
    /**
     * The filename of the currently executing script, relative to the document root. For instance, $_SERVER['PHP_SELF'] in a script at the address http://example.com/foo/bar.php would be /foo/bar.php. The __FILE__ constant contains the full path and filename of the current (i.e. included) file. If PHP is running as a command-line processor this variable contains the script name since PHP 4.3.0. Previously it was not available.
     */
    const PHP_SELF = 1;
    /**
     * Contents of the Host: header from the current request, if there is one.
     */
    const HTTP_HOST = 2;
    /**
     * Contents of the Accept: header from the current request, if there is one.
     */
    const HTTP_ACCEPT = 3;
    /**
     * Contents of the User-Agent: header from the current request, if there is one. This is a string denoting the user agent being which is accessing the page. A typical example is: Mozilla/4.5 [en] (X11; U; Linux 2.2.9 i586). Among other things, you can use this value with get_browser() to tailor your page's output to the capabilities of the user agent.
     */
    const HTTP_USER_AGENT = 4;
    /**
     * Server identification string, given in the headers when responding to requests.
     */
    const SERVER_SOFTWARE = 5;
    /**
     * The name of the server host under which the current script is executing. If the script is running on a virtual host, this will be the value defined for that virtual host.
     */
    const SERVER_NAME = 6;
    /**
     * The IP address of the server under which the current script is executing.
     */
    const SERVER_ADDR = 7;
    /**
     * The port on the server machine being used by the web server for communication. For default setups, this will be '80'; using SSL, for instance, will change this to whatever your defined secure HTTP port is.
     */
    const SERVER_PORT = 8;
    /**
     * Which request method was used to access the page; i.e. 'GET', 'HEAD', 'POST', 'PUT'.
     */
    const REQUEST_METHOD = 9;
    /**
     * The URI which was given in order to access this page; for instance, '/index.html'.
     */
    const REQUEST_URI = 10;
    /**
     * The status of error pages<br/>
     * 202 : Document has been processed and sent to you.<br/>
     * 400 : Bad HTTP request<br/>
     * 401 : Unauthorized - Iinvalid password<br/>
     * 403 : Forbidden<br/>
     * 500 : Internal Server Error<br/>
     */
    const REDIRECT_STATUS = 11;

    /**
     * Get Current website url.
     */
    const SITE_URL = 12;
    
    /**
     * Get requested protocol
     */
    const SITE_PROTOCOL = 13;

    /**
    * The address of the page (if any) which referred the user agent to the current page. This is set by the user agent. Not all user agents will set this, and some provide the ability to modify HTTP_REFERER as a feature. In short, it cannot really be trusted.
    **/
    const HTTP_REFERER = 14;


    /**
     * 
     * @param Server $serverGlobalVariable
     * @return Mix the value of server variable
     */
    function get($serverGlobalVariable){
        if ($serverGlobalVariable == 0) return $this->getIP ();
        if ($serverGlobalVariable == 12) return $this->getSiteURL() ;
        if ($serverGlobalVariable == 13) return $this->getProtocol();
        if (isset($this->index[$serverGlobalVariable])){
        	if(isset($_SERVER[$this->index[$serverGlobalVariable]]) === false) return null;
            return $_SERVER[$this->index[$serverGlobalVariable]];
        }
        return NULL;
    }
}
?>