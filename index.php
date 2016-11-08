<?php

    $currentPath = dirname(__FILE__);
    $dirMVCPath = $currentPath . '/application/';
    $publicPath = $currentPath . '/';

    //Security Configuration
    $showError = TRUE; //Show error or not when errors occure
    $disable_ip_verify = FALSE;
    $disable_check_expire_session = FALSE;

    $show_session_error = TRUE;
    
    // Database Configuration
    define("CHAR_SET", 'utf8');
    define("DB_HOST", 'localhost');
    define("DB_NAME", 'martkhmer.com');
    define("DB_USER", 'root');
    define("DB_PASS", '');

    define("_htdocs_", __DIR__);
    
    require_once $currentPath.'/lib/core.php';
?>