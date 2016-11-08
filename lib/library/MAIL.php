<?php    
include dirname(__FILE__).'/PHPMailer/PHPMailerAutoload.php';
class MAIL{
    private $mail, $smtp_ = array(), $from = array();
    public function __construct(){
        $this->mail = new PHPMailer();
    }

    /**
     * Field recipients are the audience of the message
     * @param String $mail_address
     */
    public function addTo($mail_address, $name = ''){
        return $this->mail->addAddress($mail_address, $name);
    }

    /**
     * Field recipients are others whom the author wishes to publicly inform of the message (carbon copy)
     * @param String $mail_address
     */
    public function addCc($mail_address, $name = ''){
        return $this->mail->addCC($mail_address, $name);
    }

    /**
     * Field recipients are those being discreetly or surreptitiously informed of the communication and cannot be seen by any of the other addressees.
     * @param String $mail_address
     */
    public function addBcc($mail_address, $name = ''){
        return $this->mail->addBCC($mail_address, $name);
    }

    /**
     * Set which E-mail address that you are going to use as mail header
     * @param String $mail_address
     */
    public function setFrom($mail_address, $name = ''){
        $this->from = array($name);
        return $this->mail->setFrom($mail_address, $name);
    }

    /**
     * Set Path of file or file controls name which you want to attach to your mail
     * @param String $pathOfFile exact path of file / Controls name
     * @param String $name the name of file or NULL if you want to send default name
     */
    public function attach($pathOfFile, $name = ''){
        $this->mail->addAttachment($pathOfFile, $name);
    }

    /**
     * Set subject of your mail
     * @param String $subject
     */
    public function setSubject($subject){
        $this->mail->Subject = $subject;
    }

    /**
     * Set body of your mail
     * @param String $body
     */
    public function setBody($body){
        $this->mail->isHTML($this->is_html($body));
        $this->mail->Body = $body;
        $this->mail->AltBody = strip_tags($body);
    }

    /**
     * Set Mailer to user SMTP
     * @param String $host Specify main and backup server
     * @param String $username SMTP username
     * @param String $password SMTP password
     * @param Integer $port Set the SMTP port number - 587 for authenticated TLS
     * @param String $encryption Enable encryption, 'tls' and 'ssl' are accepted
     */
    public function smtp($host, $username, $password, $port=26, $encryption=''){
        $this->mail->isSMTP();
        $this->mail->Host = $host;               // Specify main and backup server
        $this->mail->SMTPAuth = true;            // Enable SMTP authentication
        $this->mail->Username = $username;       // SMTP username
        $this->mail->Password = $password;       // SMTP password
        if($encryption == ''){
            if ($port == 465) $encryption = 'ssl';
            else if ($port == 587) $encryption = "tls";
        }
        if($encryption !== '') $this->mail->SMTPSecure = $encryption;   // Enable encryption, 'ssl' also accepted
        $this->mail->Port = $port;               //Set the SMTP port number - 587 for authenticated TLS
    }

    /**
    * Get a detailed transcript of the SMTP conversation, If you're using SMTP.
    * @param Integer $level
    *
    **/
    public function smtpDebug($level){
        if($level < 0) $level = 0;
        else if ($level > 4) $level = 4;
        $this->mail->SMTPDebug = $level;
    }

    /**
     * Start send mail
     * @return Boolean FALSE if error
     */
    public function send(){
        if(count($this->from) < 1){
            $server = new SERVER();
            $sname = explode(".", $server->get(SERVER::SERVER_NAME));
            $subK = end($sname);
            if($sname[0] == "www" || count($sname) > 2) $sname=$sname[1];
            else $sname=$sname[0];
            $this->mail->setFrom("noreply@{$sname}.{$subK}");
        }
        try{
            return $this->mail->send();
        }catch(Exception $ex){
            $this->mail->ErrorInfo = $ex->getMessage();
        }
        return false;
    }

    /**
     * get Error of previous sending process
     */
    function getError(){
        return $this->mail->ErrorInfo;
    }

    private function is_html($string){
        $m = preg_match("/<[^<]+>/",$string) != 0;
        if ($m == 1) return true;
        return false;
    }
}
?>