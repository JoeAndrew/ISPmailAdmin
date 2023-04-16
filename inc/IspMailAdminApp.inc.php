<?php
/**
**
**
** @package    ISPmail_Admin
** @author     Ole Jungclaussen
** @version    0.9.9
**/
require_once('inc/defs.inc.php');
require_once('cfg/config.inc.php');
require_once('inc/lib.inc.php');
require_once('inc/HtmlPage.inc.php');
// Version
define('IMA_VERSION_STR', '0.9.9');
/**
** @public
**/
class IspMailAdminApp {
// ########## PROPS PUBLIC
    /**
    **
    ** @type HtmlPage
    **/
    public $Page = false;
    /**
    **
    ** @type array
    **/
    public $aReqParam  = array();
    /**
    **
    ** @type Database
    **/
    public $DB = false;
    /**
    ** Logged in user
    **      < 0: the admin
    **      > 0: virtual_users.id
    ** @type integer
    **/
    public $iIdUser = 0;
    /**
    **
    ** @type integer
    **/
    public $iIdDomSel = 0;
    /**
    **
    ** @type string
    **/
    public $sDomSel   = '';
    /**
    **
    ** @type integer
    **/
    public $iIdAccSel = 0;
    /**
    **
    ** @type string
    **/
    public $sAccSel   = '';
    /**
    **
    ** @type string
    **/
    public $sIdPage   = '';
    /**
    ** State of the overview page.
    ** @type array
    **/
    public $aOvrStat = array(
        // Sorting of the overview.
        'sSort' => 'sSrcUser ASC, sSrcDomain ASC, sTarUser ASC, sTarDomain ASC'
        // pagination
        ,'iIdxPage'  => null
        ,'iShowMax'  => 65535
    );
    /**
    ** State of the domain page
    ** @type array
    **/
    public $aDomStat = array(
        // pagination
         'iIdxPage'  => null
        ,'iShowMax'  => 65535
    );
    /**
    ** State of the accounts page.
    ** @type array
    **/
    public $aAccStat = array(
        // pagination
         'iIdxPage'  => null
        ,'iShowMax'  => 65535
    );
    /**
    ** State of the alias page.
    ** @type array
    **/
    public $aAlsStat = array(
        // pagination
         'iIdxPage'  => null
        ,'iShowMax'  => 65535
    );
    /**
    ** State of the redirect page.
    ** @type array
    **/
    public $aRedStat = array(
        // pagination
         'iIdxPage'  => null
        ,'iShowMax'  => 65535
    );
    /**
    **
    ** @type array
    **/
    public $aCfgErr = array();
// ########## PROPS PROTECTED
    /**
    **
    ** @type array
    **/
    protected $aPages = array(
         'page_welcome'   => array('aAccess'=> array('a'),     'sMenu' => 'Home')
        ,'page_overview'  => array('aAccess'=> array('a'),     'sMenu' => 'Overview')
        ,'page_domains'   => array('aAccess'=> array('a'),     'sMenu' => 'Domains')
        ,'page_accounts'  => array('aAccess'=> array('a'),     'sMenu' => 'Accounts')
        ,'page_aliases'   => array('aAccess'=> array('a','u'), 'sMenu' => 'Aliases')
        ,'page_redirects' => array('aAccess'=> array('a'),     'sMenu' => 'Redirects')
    );
    /**
    **
    ** @var object $Instance
    **/
    protected static $Instance = false;
// ########## PROPS PRIVATE
// ########## CONST/DEST
    function __construct()
    {
        if(defined('IMA_LIST_MAX_ENTRIES')){
            $this->aOvrStat['iShowMax'] = IMA_LIST_MAX_ENTRIES;
            $this->aDomStat['iShowMax'] = IMA_LIST_MAX_ENTRIES;
            $this->aAccStat['iShowMax'] = IMA_LIST_MAX_ENTRIES;
            $this->aAlsStat['iShowMax'] = IMA_LIST_MAX_ENTRIES;
            $this->aRedStat['iShowMax'] = IMA_LIST_MAX_ENTRIES;
        }
    }
    function __destruct()
    {
    }
// ########## METHOD PUBLIC
    /**
    **
    **
    ** @retval boolean
    ** @returns true if user is logged in
    **/
    public function isLoggedIn()
    {
        return(0!=$this->iIdUser);
    }
    /**
    **
    **
    ** @retval string
    ** @returns Name of App
    **/
    public static function getName()
    {
        return('ISPmail Admin');
    }
    /**
    **
    **
    ** @retval string
    ** @returns Name of App
    **/
    public static function getVersion()
    {
        return(IMA_VERSION_STR);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    public static function getInstance()
    {
        if(false===self::initSession(self::getName()));
        else if(self::$Instance);
        else if(isset($_SESSION['Sess'])){
            self::$Instance = &$_SESSION['Sess'];
        }
        else{ 
            self::$Instance = new IspMailAdminApp();
            $_SESSION['Sess'] = &self::$Instance;
        }
        return(self::$Instance);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    public function startScript()
    {
        $iErr = 0;
    
        if(false===($this->Page = new HtmlPage($this)));
        else if(0!=($iErr = $this->initDatabase()));
        else if(0!=($iErr = $this->handleParams()));
        else if(0!=$this->iIdUser && false===$this->regenerateSessionId());
    // (AUTO)LOGIN USER
        else if(!$this->isLoggedIn() && 0!=($iErr = $this->login()));
        
        return($iErr);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    public function endScript()
    {
        $iErr = 0;
        $this->aReqParam  = array();
        $this->Page       = false;
        $this->DB->close();
        $this->DB         = false;
        return($iErr);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    public function processCmd()
    {
        $iErr = 0;
        if(!isset($this->aReqParam['cmd']));
        else switch($this->aReqParam['cmd']){
            case 'cmd_logout':
                $iErr = $this->logout();
                break;
            case 'cmd_openPage':
                if(0!=($iErr = lib\verifyParam($bOk, 'spage', 'string')) || !$bOk);
                else if(0!=($iErr = $this->verifyPageAccess($bOk, $this->aReqParam['spage'])) || !$bOk);
                else $this->sIdPage = $this->aReqParam['spage'];
                break;
        }
        return($iErr);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    public function drawPage()
    {
        $iErr = 0;
    // LOGIN FORM
        if(!$this->isLoggedIn()){
            $iErr = $this->drawLoginForm();
        }
    // MENU
        else if(0!=($iErr = $this->drawMenu()));
    // CONTENT
        else switch($this->sIdPage){
            case 'page_welcome':
                if(0!=($iErr = $this->Page->setTitle('Welcome')));
                else if(0!=($iErr = $this->Page->setHelp( 
                    '<ul>'
                      .'<li><b>Domains</b>: Add and remove domains (<i>example.com</i>) handled by this mailserver</li>'
                      .'<li><b>Accounts</b>: Add and remove real email-accounts (<i>user1@example.com</i>) with user and passwords</li>'
                      .'<li><b>Aliases</b>: Add and remove aliases to existing email-accounts (<i>info@example.com</i> as an alias of <i>user1@example.com</i>)</li>'
                      .'<li><b>Redirects</b>: Add and remove redirects (forward emails for <i>not.an.account@example.com</i> to <i>somebody.else@over.there.com</i>) </li>'
                    .'</ul>'
                )));
                else if(0!=($iErr = $this->Page->addBody( 
                    '<noscript><div class="MsgError">Please enable Javascript!</div></noscript>'
                    .'<h3>'.$this->getName().' v'.$this->getVersion().'</h3>'
                    .'<ul class="welcome">'
                      .'<li class="welcome">This was made for the <a href="http://workaround.org/ispmail">ISPmail</a> guide and works on the database tables used in that guide</li>'
                      .'<li class="welcome">Praise, suggestions, and bug reports are all welcome at <a href="mailto:ima@jungclaussen.net">ima@jungclaussen.net</a></li>'
                      .'<li class="welcome">Check <a href="http://ima.jungclaussen.com">ima.jungclaussen.com</a> for updates.</a></li>'
                    .'</ul>'
                )));
                break;
                
            case 'page_overview':
                require_once('inc/EmailOverview.inc.php');
                $EOvr = new EmailOverview($this);
                
                if(0!=($iErr = $EOvr->setTitleAndHelp($this->Page)));
                else if(0!=($iErr = $EOvr->processCmd()));
                else if(0!=($iErr = $EOvr->drawCreate($this->Page)));
                else if(0!=($iErr = $EOvr->drawList($this->Page)));
                break;
                
            case 'page_domains':
                require_once('inc/EmailDomains.inc.php');
                $EDom = new EmailDomains($this);
                
                if(0!=($iErr = $EDom->setTitleAndHelp($this->Page)));
                else if(0!=($iErr = $EDom->processCmd()));
                else if(0!=($iErr = $EDom->drawCreate($this->Page)));
                else if(0!=($iErr = $EDom->drawList($this->Page)));
                break;
                
            case 'page_accounts':
                require_once('inc/EmailDomains.inc.php');
                require_once('inc/EmailAccounts.inc.php');
                $EDom = new EmailDomains($this);
                $EAcc = new EmailAccounts($this, $EDom);
                
                if(isset($this->aReqParam['iddomain'])) $this->iIdDomSel = intval($this->aReqParam['iddomain']);

                if(0!=($iErr = $EAcc->setTitleAndHelp($this->Page)));
                else if(0!=($iErr = $EAcc->processCmd()));
                // also verifies (and possibly changes) $this->iIdDomSel
                else if(0!=($iErr = $EDom->drawSelect($this->Page, $this->iIdDomSel, $this->sDomSel)));
                else if(0==$this->iIdDomSel);
                else if(0!=($iErr = $EAcc->drawCreate($this->Page)));
                else if(0!=($iErr = $EAcc->drawList($this->Page)));
                break;
                
            case 'page_aliases':
                require_once('inc/EmailDomains.inc.php');
                require_once('inc/EmailAliases.php');
                require_once('inc/EmailAccounts.inc.php');
                $EDom   = new EmailDomains($this);
                $EAcc   = new EmailAccounts($this, $EDom);
                $EAlias = new EmailAliases($this, $EDom, $EAcc);
                
                if(isset($this->aReqParam['idaccount'])) $this->iIdAccSel = intval($this->aReqParam['idaccount']);
                
                if(0!=($iErr = $EAlias->setTitleAndHelp($this->Page)));
                else if(0!=($iErr = $EAlias->processCmd()));
                // also verifies (and possibly changes) $this->iIdAccSel
                else if(0!=($iErr = $EAcc->drawSelect($this->Page)));
                else if(0==$this->iIdAccSel);
                else if(0!=($iErr = $EAlias->drawCreate($this->Page)));
                else if(0!=($iErr = $EAlias->drawList($this->Page)));
                break;

            case 'page_redirects':
                require_once('inc/EmailDomains.inc.php');
                require_once('inc/EmailRedirects.php');
                $EDom   = new EmailDomains($this);
                $ERedir = new EmailRedirects($this, $EDom);

                if(0!=($iErr = $ERedir->setTitleAndHelp($this->Page)));
                else if(0!=($iErr = $ERedir->processCmd()));
                else if(0!=($iErr = $ERedir->drawCreate($this->Page)));
                else if(0!=($iErr = $ERedir->drawList($this->Page)));
                break;
        }
        return($iErr);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    public function sendPage($iErr)
    {
    // SHOW ERRORS
        if(!lib\ErrLog::getInstance()->hasError());
        else if(0!=lib\ErrLog::getInstance()->getLog($sErrMsg));
        else if(0!=$this->Page->drawMsgError($sErrMsg)); 
        
        $this->Page->addCss('css/ispmailadm.css');
        return($this->Page->send());
    }
    /**
    **
    **
    ** @retval boolean
    ** @returns true on pwd and hash matching
    **/
    public function verifyPwd_DbHash($sPwdPlain, $sPwdHash)
    {
        $bRetVal = false;
        if(!preg_match("/^\{(BLF-CRYPT|SHA256-CRYPT|PLAIN-MD5)\}(.*)$/i", $sPwdHash, $aRes));
        else if('PLAIN-MD5'==$aRes[1]) $bRetVal = ($aRes[2] === md5($sPwdPlain));
        else $bRetVal = password_verify($sPwdPlain, $aRes[2]);
        return($bRetVal);
    }
// ########## METHOD PROTECTED
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    protected static function initSession($sAppName)
    {
        session_cache_limiter('nocache');
        session_name(preg_replace('/[^a-z0-9_]+/i','_',$sAppName));
        session_set_cookie_params(0, dirname($_SERVER['PHP_SELF']), '', false, true);
        return(session_start());
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    protected function initDatabase()
    {
        require_once('inc/Database_mysqli.inc.php');
        $iErr = 0;
        if(false===($this->DB = new Database(IMA_CFG_DB_HOST, IMA_CFG_DB_PORT, IMA_CFG_DB_SOCKET, IMA_CFG_DB_DATABASE, IMA_CFG_DB_USER, IMA_CFG_DB_PASSWORD))); // rethoric
        else if(0!=($iErr = $this->DB->connect()));
        return($iErr);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    protected function handleParams()
    {
        $iErr = 0;
        foreach(array_keys($_GET) As $sKey) $this->handleParam($_GET, $sKey);
        foreach(array_keys($_POST) As $sKey) $this->handleParam($_POST, $sKey);
        foreach(array_keys($_FILES) As $sKey){ $this->aReqParam[$sKey] = $_FILES[$sKey]; unset($_FILES[$sKey]); }
        return($iErr);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    protected function handleParam(&$aGetPost, &$sKey)
    {
    // treat a password parameter special
        if(preg_match('/^pwd_/', $sKey)){
            if(0==strlen($this->aReqParam[$sKey] = trim($aGetPost[$sKey])));
            else $this->aReqParam[$sKey] = $this->makePwd_DbHash($aGetPost[$sKey]);
        }
    // sanitize all others    
        else $this->aReqParam[$sKey] = lib\sanitizeParam($aGetPost[$sKey]);
        unset($aGetPost[$sKey]);
        return(0);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    protected function makePwd_DbHash($sPwdPlain)
    {
        $sPwdHash  = '';
        if(defined('IMA_CFG_USE_MD5_HASHES'))
        {
            $sPwdHash = md5($sPwdPlain);
        }
        else if(defined('IMA_CFG_USE_SHA256_HASHES'))
        {
            $sRand     = strval(rand());
            $sRandSh1  = sha1($sRand);
            $sSalt     = substr($sRandSh1, -16);
            $sPwdHash  = '{SHA256-CRYPT}'.crypt($sPwdPlain, '$5$'.$sSalt.'$');
        }
        else
        {
            $sPwdHash = '{BLF-CRYPT}'.password_hash($sPwdPlain, PASSWORD_BCRYPT);
        }
        return($sPwdHash);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    protected function verifyPageAccess(&$bOk, $sIdPage)
    {
        $iErr = 0;
        
        $bOk = false;
        
        if(!in_array($sIdPage, array_keys($this->aPages)));
        else if($this->iIdUser > 0 && !in_array('u', $this->aPages[$sIdPage]['aAccess']));
        else if($this->iIdUser < 0 && !in_array('a', $this->aPages[$sIdPage]['aAccess']));
        else $bOk = true;
        
        return($iErr);
    }
// #####################################
// LOGIN / LOGOUT    
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    protected function login()
    {
        $iErr = 0;
        
        if(!defined('IMA_CFG_LOGIN'));
    // single user and login disabled (e.g. when protected by .htaccess/.htpwd)
        else if(IMA_CFG_LOGIN==IMA_LOGINTYPE_ADMAUTO){
            $this->iIdUser = -1;
            $this->sIdPage = 'page_welcome';
            $this->regenerateSessionId();
            $bRetVal = true;
        }
        else if(isset($this->aReqParam['loginform'])){
            if(0!=($iErr = lib\verifyParam($bOk, 'sloginuser', 'string', 'preg_match("/^[^\r\n\t]+$/", $$$)')) || !$bOk);
    // is config.php user
            else if(($this->aReqParam['sloginuser'] == IMA_CFG_ADM_USER) && ($this->aReqParam['sloginpass'] === IMA_CFG_ADM_PASS)){
                $this->iIdUser = -1;
                $this->sIdPage = 'page_welcome';
                $this->regenerateSessionId();
            }
    // multiuser allowed: check virtual_users
            else if(IMA_CFG_LOGIN==IMA_LOGINTYPE_ACCOUNT){
                require_once('inc/EmailAccounts.inc.php');
                $iIdAcc=0;
                if(0!=($iErr = EmailAccounts::loginAccount($this, $iIdAcc, $this->aReqParam['sloginuser'], $this->aReqParam['sloginpass'])));
                else if(0==$iIdAcc);
                else{
                    $this->iIdUser = $iIdAcc;
                    $this->sIdPage = 'page_aliases';
                }
            }
            
            if(0==$this->iIdUser) lib\ErrLog::getInstance()->push("Unknown username/password.");
        }
        return($iErr);
    }
    /**
    **
    **
    ** @retval boolean
    ** @returns true if user is logged in
    **/
    protected function logout()
    {
        if(0!=$this->iIdUser){
            session_unset();
            session_destroy();
            $this->reset();
        }
        return(0);
    }
// #####################################
// DRAW ELEMENTS
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    protected function drawLoginForm()
    {
        $iErr = 0;
        if(0!=($iErr = $this->Page->setTitle('Login')));
        if(!defined('IMA_CFG_LOGIN')){
            if(0!=($iErr = $this->Page->setHelp( 
                '<ul>'
                  .'<li>Check the README.txt</li>'
                  .'<li>Read the installation instructions at <a href="http://ma.jungclaussen.com">ima.jungclaussen.com</a></li>'
                .'</ul>'
            )));
            else if(0!=($iErr = $this->Page->addBody(
                'Sorry, but <i>'.$this->getName().'</i> has not been configured correctly yet.'
            )));
        }
        else if(0!=($iErr = $this->Page->setHelp( 
            '<ul>'
              .'<li>Enter administrator\'s username and password</li>'
              .(IMA_CFG_LOGIN!=IMA_LOGINTYPE_ACCOUNT ? '' : '<li>Or enter email-account (you@example.com) and password</li>')
            .'</ul>'
        )));
        else if(0!=($iErr = $this->Page->addBody(
          '<div class="InputForm">'
            .'<form id="login_form" name=login_form" action="'.$_SERVER['PHP_SELF'].'" method="POST">'
              .'<input type="hidden" name="loginform" value="1">'
              .'<table cellpadding="0" cellspacing="0">'
                .'<tr>'
                  .'<td class="value">'
                    .'<input name="sloginuser" id="sloginuser" type="text" placeholder="Username" value="'.(!isset($this->aReqParam['sloginuser']) ? '' : $this->aReqParam['sloginuser']).'">'
                  .'</td>'
                .'</tr>'
                .'<tr>'
                  .'<td class="value">'
                    .'<input name="sloginpass" id="sloginpass" type="password" placeholder="Password" value="">'
                  .'</td>'
                .'</tr>'
                .'<tr>'
                  .'<td class="submit">'
                   .'<input type="submit" value="Login">'
                  .'</td>'
                .'</tr>'
              .'</table>'
            .'</form>'
          .'</div>'
        )));
        return($iErr);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    protected function drawMenu()
    {
        $iErr = 0;
        $aEntries = array();
        foreach($this->aPages as $sId => $aPage){
            if($this->iIdUser > 0 && !in_array('u', $aPage['aAccess']));
            else if($this->iIdUser < 0 && !in_array('a', $aPage['aAccess']));
            else $aEntries[] =
                '<div class="menu_entry">'
                  .'<form name="menu_page_'.$sId.'" action="'.$_SERVER['PHP_SELF'].'" method="POST">'
                    .'<input type="hidden" name="cmd" value="cmd_openPage" />'
                    .'<input type="hidden" name="spage" value="'.$sId.'" />'
                    .'<a class="menu_entry" onClick="menu_page_'.$sId.'.submit();">['.$aPage['sMenu'].']</a>'
                  .'</form>'
                .'</div>'
            ;
        }
        
        $this->Page->setMenu('<div id="page_menu">'.implode('<div class="menu_sep">&#9830;</div>', $aEntries).'</div>');
        
        return($iErr);
    }
    /**
     * When you have properties that fail to serialize (e.g. SQlite3), you can use
     *   $tmpProp = $this->tmpProp;
     *   $this->tmpProp = null;
     *   $bReturn = session_regenerate_id();
     *   $this->tmpProp = $tmpProp;
     *   return $bReturn;
     */
    protected function regenerateSessionId()
    {
        return session_regenerate_id();
    }
// ########## METHOD PRIVATE
    private function reset()
    {
        $this->iIdUser   = 0;
        $this->iIdDomSel = 0;
        $this->sDomSel   = '';
        $this->iIdAccSel = 0;
        $this->sAccSel   = '';
        $this->sIdPage   = '';
        return(0);
    }
};
?>