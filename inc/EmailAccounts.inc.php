<?php
/**
**
**
** @package    ISPmail_Admin
** @author     Ole Jungclaussen
** @version    0.9.4
**/
/**
** @public
**/
class EmailAccounts {
// ########## PROPS PUBLIC
    /**
    ** @type IspMailAdminApp
    **/
    public $App;
    /**
    **
    ** @type array
    **/
    public $aStat = null;
// ########## PROPS PROTECTED
    /**
    **
    ** @type EmailDomains
    **/
    protected $EDom = false;
// ########## PROPS PRIVATE
// ########## CONST/DEST
    public function __construct(IspMailAdminApp &$App, EmailDomains &$Domains)
    {
        $this->App   = &$App;
        $this->aStat = &$App->aAccStat;
        $this->EDom  = &$Domains;
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
    public function setTitleAndHelp(HtmlPage &$Page)
    {
        $this->App->Page->setTitle('Email accounts');
        $this->App->Page->setHelp(
            '<div class="Heading">Manage real email-accounts (<i>user1@example.com</i>) that users can access with POP3(s), IMAP(s), and SMTP</div>'
            .'<ul>'
            .'<li>Choose the domain you want to modify/view from the dropdown list.</li>'
            .'<li>Create an account: Enter user name, password, and click "Create".</li>'
            .'<li>Delete an account: Click on <img class="icon" src="./img/trash.png" alt="delete icon" />.</li>'
            .'<li>Set a new password: Click on <img class="icon" src="./img/key.png" alt="change password icon" />, enter the new password and click "Set".</li>'
            .(!IMA_CFG_USE_QUOTAS ? '' : '<li>Change the quota: Click on <img style="width:1em;" class="icon" src="./img/edit_pen.svg" alt="change quota icon" />, enter the new quota and click "Set".</li>')
            .'<li>Aliases to an account: Click on <img class="icon" src="./img/edit.png" alt="edit icon"/>.</li>'
            .(!IMA_CFG_USE_QUOTAS ? '' : '<li>Quota: Value is in <i>bytes</i>. Use "0" (zero) for unlimited. One GB is 1073741824 bytes (2<sup>30</sup>). Do not use dots or commas.')
            .'<li><b>Note</b>: If you delete an account, all aliases associated with it <i>will</i> be deleted, too.</li>'
            .'</ul>'
        );
        return(0);
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
        
        if(!isset($this->App->aReqParam['cmd']));
        else switch($this->App->aReqParam['cmd']){
            case 'cmd_create':
                $bSuccess = false;
                $iQuota = 0;

                if(!isset($this->App->aReqParam['saccount']) || 0==strlen($sAccount = trim($this->App->aReqParam['saccount'])));
                else if(!isset($this->App->aReqParam['pwd_spassword']));
                else if(IMA_CFG_USE_QUOTAS && (!isset($this->App->aReqParam['quota']) || 0 > intval($iQuota = $this->App->aReqParam['quota'])));
                else if(0!=($iErr = $this->createAccount($sMsg, $bSuccess, $sAccount, $this->App->aReqParam['pwd_spassword'], $this->App->iIdDomSel, $iQuota)));
                else  $this->App->Page->drawMsg(!$bSuccess, $sMsg);
                break;

            case 'cmd_delete':
                $bSuccess = false;

                if(!isset($this->App->aReqParam['idaccount']));
                else if(0>=($iIdAccount = intval($this->App->aReqParam['idaccount'])));
                else if(0!=($iErr = $this->delete($sMsg, $bSuccess, $iIdAccount)));
                else  $this->App->Page->drawMsg(!$bSuccess, $sMsg);
                break;

            case 'cmd_resetPassword':
                $bSuccess = false;

                if(!isset($this->App->aReqParam['idaccount']));
                else if(0>=($iIdAccount = intval($this->App->aReqParam['idaccount'])));
                else if(!isset($this->App->aReqParam['pwd_spassword']));
                else if(0!=($iErr = $this->setPassword($sMsg, $bSuccess, $iIdAccount, $this->App->aReqParam['pwd_spassword'])));
                else $this->App->Page->drawMsg(!$bSuccess, $sMsg);
                break;

            case 'cmd_changeQuota':
                if(!isset($this->App->aReqParam['idaccount']));
                else if(0>=($iIdAccount = intval($this->App->aReqParam['idaccount'])));
                else if(!isset($this->App->aReqParam['quota']));
                else if(0!=($iErr = $this->setQuota($sMsg, $bSuccess, $iIdAccount, $this->App->aReqParam['quota'])));
                else $this->App->Page->drawMsg(!$bSuccess, $sMsg);
                break;

            case 'cmd_listpage':
                $this->aStat['iIdxPage'] = $this->App->aReqParam['idxpage'];
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
    public function drawCreate(HtmlPage &$Page)
    {
        return($Page->addBody(
            '<h3>Create new</h3>'
            .'<div class="InputForm">'
              .'<form id="create_account" name="create_account" action="'.$_SERVER['PHP_SELF'].'" method="POST">'
                .'<input type="hidden" name="cmd" value="cmd_create" />'
                .'<input type="hidden" name="iddomain" value="'.strval($this->App->iIdDomSel).'">'
                .'<table class="InputForm">'
                  .'<tr>'
                    .'<td class="label">Email:</td>'
                    .'<td class="value"><input type="text" name="saccount" placeholder="account" autocomplete="off">@'.$this->App->sDomSel.'</td>'
                  .'</tr>'
                  .'<tr>'
                    .'<td class="label">Pass:</td>'
                    .'<td class="value"><input type="password" name="pwd_spassword" autocomplete="off"></td>'
                  .'</tr>'
                  .(!IMA_CFG_USE_QUOTAS ? '' :
                    '<tr>'
                      .'<td class="label">Quota:</td>'
                      .'<td class="value"><input type="number" name="quota" autocomplete="off" min="0" step="'.strval(intval(IMA_CFG_QUOTA_STEP)).'" value="'.strval(IMA_CFG_DEFAULT_QUOTA).'"></td>'
                    .'</tr>'
                  )
                  .'<tr>'
                    .'<td class="label"></td>'
                    .'<td class="submit">'
                      .'<a class="button" onClick="verifyCreateAccount(document.create_account, \''.$this->App->sDomSel.'\');">Create</a>'
                    .'</td>'
                  .'</tr>'
                .'</table>'
              .'</form>'
            .'</div>'
        ));
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    public function drawList(HtmlPage &$Page)
    {
        $iErr = 0;
        $sHtml = '';
        $nEntries=0;
        $aRow = null;
        $rRslt = null;
        $nRows = 0;

        if(0!=($iErr = $this->App->DB->queryOneRow($aRow,
            "SELECT COUNT(id) AS nCnt FROM `virtual_users`"
            ." WHERE domain_id=".strval(intval($this->App->iIdDomSel))
        )));
        else if(null===$aRow);
        else if(0!=($iErr = lib\checkListPages($this->aStat, ($nEntries = $aRow['nCnt']))));
        
        if(0!=($iErr = $this->App->DB->query($rRslt,
            "SELECT"
            ." user.id AS iId"
            .",user.email AS sEmail"
            .(IMA_CFG_USE_QUOTAS ? ",user.quota AS iQuota" : "")
            .",COUNT(alias.id) AS nAliases"
            ." FROM virtual_users AS user"
            ." LEFT JOIN virtual_aliases AS alias ON(alias.destination=user.email)"
            ." WHERE user.domain_id=".strval(intval($this->App->iIdDomSel))
            ." GROUP BY user.id"
            ." ORDER BY email ASC"
            .lib\makeListPagesSqlLimit($this->aStat)
        ))); 
        else if(0!=($iErr = $this->App->DB->getNumRows($nRows, $rRslt)));
        else if(0==$nRows) $sHtml .= '<tr class=""><td class="" colspan="6">No (email)accounts created yet for this domain</td></tr>';
        else while(0==($iErr = $this->App->DB->fetchArray($aRow, $rRslt, MYSQLI_ASSOC)) && NULL!==$aRow){
            $sHtml .= 
                '<tr>'
                .'<td class="icon">'
                  .'<form name="delete_account_'.strval($aRow['iId']).'" action="'.$_SERVER['PHP_SELF'].'" method="POST">'
                    .'<input type="hidden" name="cmd" value="cmd_delete" />'
                    .'<input type="hidden" name="idaccount" value="'.strval($aRow['iId']).'" />'
                    .'<img class="icon" src="./img/trash.png" onClick="confirmDeleteAccount(document.delete_account_'.strval($aRow['iId']).', \''.$aRow['sEmail'].'\');" alt="icon delete"/>'
                  .'</form>'
                .'</td>'
                /// PASSWORD
                .'<td class="icon">'
                  .'<a onClick="toggleNewPassword(document.account_chg_pass_'.$aRow['iId'].')">'
                    .'<img class="icon" src="./img/key.png" alt="icon change password"/>'
                  .'</a>'
                .'</td>'
                .'<td class="">'
                  .'<form name="account_chg_pass_'.$aRow['iId'].'" style="display:none;" action="'.$_SERVER['PHP_SELF'].'" method="POST">'
                    .'<input type="hidden" name="cmd" value="cmd_resetPassword" />'
                    .'<input type="hidden" name="idaccount" value="'.strval($aRow['iId']).'" />'
                    .'<input name="pwd_spassword" type="password" placeholder="New password">'
                    .'<a class="button button_small_right" onClick="confirmChangePassword(document.account_chg_pass_'.$aRow['iId'].')">Set</a>'
                  .'</form>'
                .'</td>'
                .'<td class="">'.$aRow['sEmail'].'</td>'
                /// QUOTAS
                .(!IMA_CFG_USE_QUOTAS ? '' :
                    '<td class="num">'.self::cnvQuotaToHuman($aRow['iQuota']).'</td>'
                    .'<td class="icon">'
                      .'<a onClick="toggleEditQuota(document.account_chg_quota_'.$aRow['iId'].')">'
                          .'<img class="icon" src="./img/edit_pen.svg" alt="icon change quota"/>'
                      .'</a>'
                    .'</td>'
                    .'<td>'
                      .'<form name="account_chg_quota_'.$aRow['iId'].'" style="display:none;" action="'.$_SERVER['PHP_SELF'].'" method="POST">'
                          .'<input type="hidden" name="cmd" value="cmd_changeQuota" />'
                          .'<input type="hidden" name="idaccount" value="'.strval($aRow['iId']).'" />'
                          .'<input name="quota" type="number" autocomplete="off" min="0" step="'.strval(intval(IMA_CFG_QUOTA_STEP)).'" value="'.$aRow['iQuota'].'">'
                          .'<a class="button button_small_right" onClick="confirmChangeQuota(document.account_chg_quota_'.$aRow['iId'].')">Set</a>'
                      .'</form>'
                    .'</td>'
                )
                .'<td class="num">'.(0==$aRow['nAliases']?'&nbsp;&ndash;&nbsp;':$aRow['nAliases']).'</td>'
                .'<td class="icon">'
                  .'<form name="account_aliases_'.strval($aRow['iId']).'" action="'.$_SERVER['PHP_SELF'].'" method="POST">'
                    .'<input type="hidden" name="cmd" value="cmd_openPage" />'
                    .'<input type="hidden" name="spage" value="page_aliases" />'
                    .'<input type="hidden" name="idaccount" value="'.strval($aRow['iId']).'" />'
                    .'<img class="icon" src="./img/edit.png" onClick="document.account_aliases_'.strval($aRow['iId']).'.submit();" alt="icon edit"/>'
                  .'</form>'
                .'</td>'
                .'</tr>'
            ;
        }

        if(0!=$iErr);
        else if(0!=($iErr = $Page->addBody(
            '<h3>Existing Accounts @'.$this->App->sDomSel.'</h3>'
            .'<div class="DatabaseList">'
              .lib\makeListPages($this->aStat, $nEntries, 'Accounts_ListPage')
              .'<table class="DatabaseList">'
                .'<tr>'
                  .'<th></th>'
                  .'<th></th>'
                  .'<th></th>'
                  .'<th>Account</th>'
                  .(!IMA_CFG_USE_QUOTAS ? '' : '<th class="num">Quota</th><th></th><th></th>')
                  .'<th class="num">Aliases</th>'
                  .'<th></th>'
                .'</tr>'
                .$sHtml
              .'</table>'
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
    public function drawSelect(HtmlPage &$Page)
    {
        $iErr  = 0;
        $sOpts = '';
        $aRow = null;
        $rRslt = null;
        $nRows = 0;

    // user is a normal account: restrict to own account
        if(0 < $this->App->iIdUser) $this->App->iIdAccSel = $this->App->iIdUser;
    // else verify currently selected account, if any
        else if(0!=$this->App->iIdAccSel && !$this->isValidIdAccount($this->App->iIdAccSel)) $this->App->iIdAccSel = 0;
        
        if(0!=($iErr = $this->App->DB->query($rRslt,
            "SELECT"
            ." user.email AS sAccount"
            .",user.id AS iId"
            ." FROM virtual_users AS user"
            .(0 > $this->App->iIdUser ? "" : " WHERE user.id=".strval($this->App->iIdUser))
            ." ORDER BY user.email ASC"
        )));
        else if(0!=($iErr = $this->App->DB->getNumRows($nRows, $rRslt)));
        else if(0==$nRows) $sHtml .= '<option value="0">No (email)accounts available</option>';
        else while(0==($iErr = $this->App->DB->fetchArray($aRow, $rRslt, MYSQLI_ASSOC)) && NULL!==$aRow){
            if(0==$this->App->iIdAccSel) $this->App->iIdAccSel = $aRow['iId'];
            if($this->App->iIdAccSel == $aRow['iId']) $this->App->sAccSel = $aRow['sAccount'];
            
            $sOpts .=
                '<option value="'.strval($aRow['iId']).'"'
                  .($aRow['iId']!=$this->App->iIdAccSel ? '' : ' selected="selected"')
                .'>'
                .$aRow['sAccount']
                .'</option>'
            ;
        }
        
        if(0!=$iErr);
        else if(0!=($iErr = $Page->addBody(
            '<div class="InputForm">'
              .'<form id="accountid_selector" name="accountid_selector" action="'.$_SERVER['PHP_SELF'].'" method="POST">'
                .'<input type="hidden" name="cmd" value="cmd_setIdAccount" />'
                .'<table class="InputForm">'
                  .'<tr>'
                    .'<td class="label">Selected:</td>'
                    .'<td class="value">'
                      .'<select name="idaccount" onChange="document.accountid_selector.submit();">'
                        .$sOpts
                      .'</select>'
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
    public static function loginAccount(&$App, &$iId, $sAccount, $sPwdPlain)
    {
        $iErr = 0;
        $iId  = 0;
        $aRow = null;
        if(0!=($iErr = $App->DB->queryOneRow($aRow,
            "SELECT id, password FROM virtual_users WHERE"
            ." email='".$App->DB->realEscapeString($sAccount)."'"
        )));
        else if(NULL==$aRow);
        else if(!$App->verifyPwd_DbHash($sPwdPlain, $aRow['password']));
        else $iId = $aRow['id'];
        
        return($iErr);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    public function getAccount(&$sEmail, &$iIdDomain, $iId)
    {
        $iErr = 0;
        $aRow = null;
        
        $sEmail    = '';
        $iIdDomain = 0;
        
        if(0!=($iErr = $this->App->DB->queryOneRow($aRow, "SELECT email, domain_id FROM virtual_users WHERE id='".strval(intval($iId))."'")));
        else if(NULL==$aRow);
        else{
            $sEmail    = $aRow['email'];
            $iIdDomain = $aRow['domain_id'];
        }
        return($iErr);
    }
    /**
    **
    **
    ** @retval string
    ** @returns $iQuota as "X.XX GB"
    **/
    public static function cnvQuotaToHuman($iQuota)
    {
        if(0 >= $iQuota)
        {
            return "unlimited";
        }
        return strval(bcdiv($iQuota, 1073741824, 2)).'GB';
    }
// ########## METHOD PROTECTED
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    protected function createAccount(&$sMsg, &$bSuccess, $sAccount, $sPwdHash, $iIdDomain, $iQuota=0)
    {
        $iErr = 0;
        $bSuccess = false;
        
        if(0!=($iErr = $this->EDom->getDomainName($sDomain, $iIdDomain)));
        else if(0==strlen($sDomain)){
            $sMsg .= 'Invalid Domain['.$iIdDomain.']';
        }
        else if($this->doesAccountExist($sAccount.'@'.$sDomain)){
            $sMsg .= 'Account "'.$sAccount.'@'.$sDomain.'" already exists!';
        }
        else if(0==strlen($sPwdHash)){
            $sMsg = 'Password is empty!';
        }
        else if(0!=($iErr = $this->App->DB->state(
            // reminder: this has to work with SQLite (IMA-Demo), too
            // - SQLite3 doesn't know the "INSERT ... SET" Syntax
            "INSERT INTO virtual_users (domain_id, password, email".(IMA_CFG_USE_QUOTAS ? ", quota" : "").") VALUES ("
              .strval($iIdDomain)
              .",'".$this->App->DB->realEscapeString($sPwdHash)."'"
              .",'".$this->App->DB->realEscapeString($sAccount.'@'.$sDomain)."'"
              .(IMA_CFG_USE_QUOTAS ? ",'".intval($iQuota)."'" : "")
            .")"
        ))) lib\ErrLog::getInstance()->push('Could not create account "'.$sAccount.'@'.$sDomain.'", something['.$iErr.'] went wrong!');
        else{
            $bSuccess = true;
            $sMsg = 'Account "'.$sAccount.'@'.$sDomain.'" has been created and should show in the list below.';
        }
        
        return($iErr);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    protected function setPassword(&$sMsg, &$bSuccess, $iIdAccount, $sPwdHash)
    {
        $iErr = 0;
        $bSuccess = false;
        $aRow = null;

        if(0!=($iErr = $this->App->DB->queryOneRow($aRow, "SELECT email FROM virtual_users WHERE id=".strval($iIdAccount))));
        else if(NULL===$aRow){
            $sMsg = 'No such Account!';
        }
        else if(0==strlen($sPwdHash)){
            $sMsg = 'Password is empty!';
        }
        else if(0!=($iErr = $this->App->DB->state(
            // reminder: this has to work with SQLite (IMA-Demo), too
            "UPDATE virtual_users SET password='".$this->App->DB->realEscapeString($sPwdHash)."' WHERE id=".strval($iIdAccount)
        ))){
            lib\ErrLog::getInstance()->push('Could not change password for "'.$aRow['email'].'", something['.$iErr.'] went wrong!');
        }
        else{
            $bSuccess = true;
            $sMsg .= 'Password for "'.$aRow['email'].'" changed.';
        }
        
        return($iErr);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    protected function setQuota(&$sMsg, &$bSuccess, $iIdAccount, $iQuota)
    {
        $iErr = 0;
        $bSuccess = false;
        $aRow = null;

        $iQuota = intval($iQuota);

        if(0!=($iErr = $this->App->DB->queryOneRow($aRow, "SELECT email FROM virtual_users WHERE id=".strval($iIdAccount))));
        else if(NULL===$aRow){
            $sMsg = 'No such Account!';
        }
        else if(0!=($iErr = $this->App->DB->state(
            // reminder: this has to work with SQLite (IMA-Demo), too
            "UPDATE virtual_users SET quota='".strval($iQuota)."' WHERE id=".strval($iIdAccount)
        ))){
            lib\ErrLog::getInstance()->push('Could not change quota for "'.$aRow['email'].'", something['.$iErr.'] went wrong!');
        }
        else{
            $bSuccess = true;
            $sMsg .= 'Quota for "'.$aRow['email'].'" changed to '.self::cnvQuotaToHuman($iQuota);
        }
        
        return($iErr);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    protected function delete(&$sMsg, &$bSuccess, $iId)
    {
        $iErr = 0;
        $bSuccess = false;
        $aRow = null;

        if(0!=($iErr = $this->App->DB->queryOneRow($aRow, "SELECT email FROM virtual_users WHERE id=".strval($iId))));
        else if(NULL===$aRow){
            $sMsg .= 'No such Account!<br />';
        }
        else if(0==($iErr = $this->App->DB->startTransaction())){
            if(0!=($iErr = $this->App->DB->state(
                "DELETE FROM virtual_aliases WHERE destination='".$this->App->DB->realEscapeString($aRow['email'])."'"
            )));
            else if(0!=($iErr = $this->App->DB->state(
                "DELETE FROM virtual_users WHERE id=".strval($iId)
            )));

            if(0!=$iErr) $this->App->DB->cancelTransaction();
            else if(0!=($iErr = $this->App->DB->commitTransaction()));
        }
        
        if(0!=$iErr) lib\ErrLog::getInstance()->push('Could not delete account "'.$aRow['email'].'", something['.$iErr.'] went wrong!');
        else{
            $bSuccess = true;
            $sMsg = 'Account "'.$aRow['email'].'" and all aliases associated with it have been deleted.';
        }
        
        return($iErr);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    protected function doesAccountExist($sEmail)
    {
        $bRetVal = false;
        $aRow = null;
        if(0!=($iErr = $this->App->DB->queryOneRow($aRow, "SELECT id FROM virtual_users WHERE email='".$this->App->DB->realEscapeString($sEmail)."'")));
        else if(NULL===$aRow);
        else $bRetVal = true;
        return($bRetVal);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    protected function isValidIdAccount($iId)
    {
        $bRetVal = false;
        $aRow = null;
        if(0!=($iErr = $this->App->DB->queryOneRow($aRow, "SELECT id FROM virtual_users WHERE id='".strval(intval($iId))."'")));
        else if(NULL===$aRow);
        else $bRetVal = true;
        return($bRetVal);
    }
// ########## METHOD PRIVATE
};
?>