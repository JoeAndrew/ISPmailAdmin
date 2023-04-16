<?php
/**
**
**
** @package    ISPmail_Admin
** @author     Ole Jungclaussen
** @version    0.9.0
**/
require_once ('inc/IspMailAdminApp.inc.php');
$iErr = 0;
$App  = false;

// INIT SYSTEM
if(false===($App = IspMailAdminApp::getInstance()));
else if(0!=($iErr = $App->startScript()));
// PROCESS
else if(0!=($iErr = $App->processCmd()));
else if(0!=($iErr = $App->drawPage()));

// FINALIZE
$App->sendPage($iErr);
$App->endScript();
?>