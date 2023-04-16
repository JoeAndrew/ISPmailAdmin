<?php
/**
**
**
** @package 
** @author     Ole Jungclaussen
** @version    0.9.0
**/
// types of access control, !! do not edit here !!
define('IMA_LOGINTYPE_ACCOUNT', 1);     // use virtual_users as accounts (allow managing of aliases)
define('IMA_LOGINTYPE_ADM'    , 2);     // admin as only user, need to login
define('IMA_LOGINTYPE_ADMAUTO', 3);     // admin as only user and login automatic (.htaccess protected anyway)
?>