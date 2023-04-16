<?php
/**
**
**
** @package    ISPmail_Admin
** @author     Ole Jungclaussen
** @version    0.9.8
**/
/**
** DATABASE ACCESS
**/
define('IMA_CFG_DB_HOST',     '127.0.0.1');
define('IMA_CFG_DB_PORT',     '3306');
define('IMA_CFG_DB_SOCKET',   null);
/// Alternative, using socket (faster)
//define('IMA_CFG_DB_HOST',     'localhost');
//define('IMA_CFG_DB_PORT',     null);
//define('IMA_CFG_DB_SOCKET',   '/path/to/database.sock');

define('IMA_CFG_DB_USER',     'db_user');
define('IMA_CFG_DB_PASSWORD', 'db_pass');
define('IMA_CFG_DB_DATABASE', 'mailserver');

/**
** ACCESS CONTROL
** uncomment the type you want to use.
**/
// define('IMA_CFG_LOGIN', IMA_LOGINTYPE_ACCOUNT);  
// define('IMA_CFG_LOGIN', IMA_LOGINTYPE_ADM);  
// define('IMA_CFG_LOGIN', IMA_LOGINTYPE_ADMAUTO);  

/// Define the administrator's name and password.
define('IMA_CFG_ADM_USER',  'admin_user');     // admin username
define('IMA_CFG_ADM_PASS',  'admin_Pass');     // admin password

/**
** PASSWORD HASHES
** Enable only *one* of the following
**/
define('IMA_CFG_USE_BCRYPT_HASHES', true);
// define('IMA_CFG_USE_SHA256_HASHES', true);
// define('IMA_CFG_USE_MD5_HASHES', true);

/**
** QUOTAS
**/
/// true or false
define('IMA_CFG_USE_QUOTAS', true);
/// in bytes. 0 is unlimited, 1GB = 2^30 Bytes = 1073741824
define('IMA_CFG_DEFAULT_QUOTA', 0);
/// convenience for input field
define('IMA_CFG_QUOTA_STEP', 1073741824);

/**
** GUI
** Spread long lists on multiple pages.
** Set number of maximum entries per page.
** Changes take effect after login/logout.
** If not defined, defaults to 65535.
**/
// define('IMA_LIST_MAX_ENTRIES', 200);
?>