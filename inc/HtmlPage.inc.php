<?php
/**
**
**
** @package    ISPmail_Admin
** @author     Ole Jungclaussen
** @version    0.9.0
**/
/**
** @public
**/
class HtmlPage {
// ########## PROPS PUBLIC
    /**
    **
    ** @type IspMailAdminApp
    **/
    public $App = false;
// ########## PROPS PROTECTED
// ########## PROPS PRIVATE
    /**
    **
    ** @type string
    **/
    private $sMenu = '';
    /**
    **
    ** @type string
    **/
    private $sTitle = '';
    /**
    **
    ** @type string
    **/
    private $sHelp = '';
    /**
    **
    ** @type string
    **/
    private $sBody = '';
    /**
    **
    ** @type string
    **/
    private $sMsgBox = '';
    /**
    **
    ** @type string
    **/
    private $sDebug = '';
    /**
    **
    ** @type array
    **/
    private $aCssLink = array();
// ########## CONST/DEST
    function __construct(IspMailAdminApp &$App)
    {
        $this->App = &$App;
    }
    function __destruct()
    {
        
    }
// ########## METHOD PUBLIC
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    public function setTitle($sTxt)
    {
        $this->sTitle .= $sTxt;
        return(0);
    }
    /**
    ** Add css file(s) links.
    ** @param string $sName (string) Path/Name of the desired css file<br>
    ** @returns int
    ** @return 0 on success !0 on error
    **/
    public function addCss($sName)
    {
        $this->aCssLink[] = '<link rel="stylesheet" type="text/css" media="all" href="'.$sName.'">';
        return(0);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    public function setHelp($sHtml)
    {
        $this->sHelp .= $sHtml;
        return(0);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    public function setMenu($sHtml)
    {
        $iErr = 0;
        $this->sMenu .= $sHtml;
        return($iErr);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    public function addBody($sHtml)
    {
        $this->sBody .= $sHtml;
        return(0);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    public function drawMsgError($sTxt)
    {
        $this->sMsgBox .= '<div class="MsgError">'.$sTxt.'</div>';
        return(0);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    public function drawMsgSuccess($sTxt)
    {
        $this->sMsgBox .= '<div class="MsgSuccess">'.$sTxt.'</div>';
        return(0);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    public function drawMsg($bError, $sTxt)
    {
        return(!$bError ? $this->drawMsgSuccess($sTxt) : $this->drawMsgError($sTxt));
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    public function addDebug($s)
    {
        $this->sDebug .= $s;
        return(0);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    public function send()
    {
        $bCfgErr = 0!=count($this->App->aCfgErr);
        
        print
            '<!DOCTYPE html>'
            .'<html lang="en">'
            .'<head>'
              .'<meta charset="utf-8">'
              .'<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'
              .'<meta http-equiv="X-UA-Compatible" content="IE=edge" />'
              
              .'<title>'.$this->App->getName().'</title>'
              .'<meta name="description" content="Mailserver administration: domains, accounts, and aliases" />'

              .'<link rel="shortcut icon" type="image/x-icon" href="img/favicon.ico" >'
              .'<link rel="icon" type="image/x-icon" href="img/favicon.ico" >'

              .implode('', $this->aCssLink)

              .'<script src="js/ispmailadm.js" type="text/javascript"></script>'
            .'</head>'
            .'<body>'
              .'<div id="page">'

                .'<div id="page_top">'
                  .'<table class="page_top">'
                    .'<tr>'
                        .'<td class="page_top_title">'
                          .'<h1>'.$this->App->getName().'</h1>'
                          .'<span class="page_top_server">@'.$_SERVER['HTTP_HOST'].'</span>'
                        .'</td>'
                        .'<td class="page_top_logo"><img src="img/logo128.png" alt="ispmailadm logo" /></td>'
                    .'</tr>'
                    .'<tr>'
                        .'<td colspan="2" class="page_top_menu_container">'
                          .(''==$this->sMenu ? '&nbsp;': '<table class="page_top_menu"><tr><td id="page_top_menu">'.$this->sMenu.'</td></tr></table>')
                          .(defined('IMA_CFG_LOGIN') && IMA_CFG_LOGIN==IMA_LOGINTYPE_ADMAUTO || !$this->App->isLoggedIn() ? '' :
                            '<form name="menu_logout" action="'.$_SERVER['PHP_SELF'].'" method="POST">'
                            .'<input type="hidden" name="cmd" value="cmd_logout" />'
                            .'<img class="menu_logout" onClick="document.menu_logout.submit()" alt="logout icon" title="Click here to logout" src="img/logout.png" />'
                            .'</form>'
                           )
                        .'</td>'
                    .'</tr>'
                  .'</table>'
                .'</div>'
                
                .'<div id="page_content">'
                  .'<h2>'.$this->sTitle.'</h2>'
                  .'<table class="content">'
                    .'<colgroup><col class="left"><col class="right"></colgroup>'
                    .'<tr>'
                      .'<td id="content">'
                        .$this->sMsgBox
                        .(!$bCfgErr ? $this->sBody :
                            '<div style="border:2px solid red;border-radius:5px;padding:2em;text-align:left;color:red;font-weight:normal;">'
                            .implode('<br><br>', $this->App->aCfgErr)
                            .'</div>'
                        )
                      .'</td>'
                      .'<td id="page_help">'
                        .$this->sHelp
                      .'</td>'
                    .'</tr>'
                  .'</table>'
                .'</div>'
              .'</div>'
              .'<div id="page_footer">'
                .'<i>'.$this->App->getName().'</i> by Ole Jungclaussen (<a href="http://ima.jungclaussen.com">http://ima.jungclaussen.com</a>), <b>version '.$this->App->getVersion().'</b>.'
                .' Icons by <a href="http://www.freepik.com" title="Freepik">Freepik</a> from <a href="http://www.flaticon.com" title="Flaticon">www.flaticon.com</a>.'
              .'</div>'
              .$this->sDebug
            .'</body>'
            .'</html>'
        ;
        return(0);
    }
// ########## METHOD PROTECTED
// ########## METHOD PRIVATE
};
?>