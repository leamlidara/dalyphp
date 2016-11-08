<?php
class RESOURCES{
    private $fileName;

    public function __construct($skipUrlIndexCount = 0)
    {
        ob_clean();
        $this->fileName = &$_SESSION['dirDefualt_dara_frmWork1168'];
        $url1 = new URL($skipUrlIndexCount);
        if ($url1->getPage()=='captcha'){
            include_once 'CaptchaSecurityImages.php';
            $session = new SESSION();
            $a = new CaptchaSecurityImages(120, 40, $session->security_code168_length_168);
            ob_flush();
            exit();
        }
    }
}
?>