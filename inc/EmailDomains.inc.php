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
class EmailDomains {
// ########## PROPS PUBLIC
    /**
    **
    ** @type IspMailAdminApp
    **/
    public $App = false;
    /**
    **
    ** @type array
    **/
    public $aStat = null;
// ########## PROPS PROTECTED
// ########## PROPS PRIVATE
// ########## CONST/DEST
    public function __construct(IspMailAdminApp &$App)
    {
        $this->App  = &$App;
        $this->aStat = &$App->aDomStat;
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
        $this->App->Page->setTitle('Domains');
        $this->App->Page->setHelp(
            '<div class="Heading">Manage Domains (<i>example.com</i>) handled by this mailserver</div>'
            .'<ul>'
            .'<li>Create a domain: Enter Domain name and click "Create"</li>'
            .'<li>Email accounts of a domain: Click on <img class="icon" src="./img/edit.png" alt="edit icon"/></li>'
            .'<li>Delete a domain: Click on <img class="icon" src="./img/trash.png" alt="delete icon" /></li>'
            .'<li><b>Note</b>: If you delete a domain, all email accounts and aliases associated with it <i>should</i> be deleted, too.'
              .'This depends on wether you\'ve followed Haas\' instructions to the point and created the tables as InnoDB with all the constraints enabled.'
            .'</li>'
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

                if(!isset($this->App->aReqParam['sdomain']));
                else if(0==strlen($sDomain = trim($this->App->aReqParam['sdomain'])));
                else if(0!=($iErr = $this->createDomain($sMsg, $bSuccess, $sDomain)));
                else $this->App->Page->drawMsg(!$bSuccess, $sMsg);
                break;

            case 'cmd_delete':
                $bSuccess = false;

                if(0!=($iErr = lib\verifyParam($bOk, 'iddomain', 'number')) || !$bOk);
                else if(0>=($iIdDomain = intval($this->App->aReqParam['iddomain'])));
                else if(0!=($iErr = $this->deleteDomain($sMsg, $bSuccess, $iIdDomain)));
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
              .'<form id="create_domain" name="create_domain" action="'.$_SERVER['PHP_SELF'].'" method="POST" onSubmit="return verifyCreateDomain(document.create_domain, false);">'
                .'<input type="hidden" name="cmd" value="cmd_create" />'
                .'<table class="InputForm">'
                  .'<tr>'
                    .'<td class="value">'
                      .'<input type="text" name="sdomain" id="domain_name" placeholder="example.com">'
                    .'</td>'
                    .'<td class="submit_right">'
                      .'<a class="button" onClick="verifyCreateDomain(document.create_domain, true);">Create</a>'
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
        $nEntries = 0;
        
        if(0!=($iErr = $this->App->DB->queryOneRow($aRow,
            " SELECT COUNT(id) AS nCnt FROM `virtual_domains`"
        )));
        else if(null===$aRow);
        else if(0!=($iErr = lib\checkListPages($this->aStat, ($nEntries = $aRow['nCnt']))));
        
        
        if(0!=($iErr = $this->App->DB->query($rRslt,
            "SELECT"
            ." domain.name AS sName"
            .",domain.id AS iId"
            .",COUNT(user.id) AS nUsers"
            .",(SELECT COUNT(id) FROM virtual_aliases WHERE domain_id=domain.id) AS nAliases"
            ." FROM `virtual_domains` AS domain"
            ." LEFT JOIN virtual_users AS user ON(user.domain_id = domain.id)"
            ." GROUP BY domain.id"
            ." ORDER BY domain.name ASC"
            .lib\makeListPagesSqlLimit($this->aStat)
        )));
        else if(0!=($iErr = $this->App->DB->getNumRows($nRows, $rRslt)));
        else if(0==$nRows) $sHtml .= '<tr class="" colspan="6"><td class="">No domains created yet.</td></tr>';
        else while(0==($iErr = $this->App->DB->fetchArray($aRow, $rRslt, MYSQLI_ASSOC)) && NULL!==$aRow){
            $sHtml .= 
              '<tr>'
                .'<td class="icon">'
                  .'<form name="delete_domain_'.strval($aRow['iId']).'" action="'.$_SERVER['PHP_SELF'].'" method="POST">'
                    .'<input type="hidden" name="cmd" value="cmd_delete" />'
                    .'<input type="hidden" name="iddomain" value="'.strval($aRow['iId']).'" />'
                    .'<img class="icon" src="./img/trash.png" onClick="confirmDeleteDomain(document.delete_domain_'.strval($aRow['iId']).', \''.$aRow['sName'].'\');" alt="icon delete"/>'
                  .'</form>'
                .'</td>'
                .'<td class="">'.$aRow['sName'].'</td>'
                .'<td class="num">'
                  .(0==$aRow['nUsers']?'&nbsp;&ndash;&nbsp;':$aRow['nUsers'])
                .'</td>'
                .'<td class="icon">'
                  .'<form name="domain_accounts_'.strval($aRow['iId']).'" action="'.$_SERVER['PHP_SELF'].'" method="POST">'
                    .'<input type="hidden" name="cmd" value="cmd_openPage" />'
                    .'<input type="hidden" name="spage" value="page_accounts" />'
                    .'<input type="hidden" name="iddomain" value="'.strval($aRow['iId']).'" />'
                    .'<img class="icon" src="./img/edit.png" onClick="document.domain_accounts_'.strval($aRow['iId']).'.submit();" alt="icon edit"/>'
                  .'</form>'
                .'</td>'
              .'</tr>'
            ;
        }
        
        if(0!=$iErr);
        else if(0!=($iErr = $Page->addBody(
            '<h3>Existing Domains</h3>'
            .'<div class="DatabaseList">'
              .lib\makeListPages($this->aStat, $nEntries, 'Domain_ListPage')
              .'<table class="DatabaseList">'
               .'<tr class="header">'
                 .'<th></th>'
                 .'<th>Domain</th>'
                 .'<th class="num" colspan="2">Accounts</th>'
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
    public function drawSelect(HtmlPage &$Page, &$iIdDomSel, &$sDomSel)
    {
        $iErr  = 0;
        $sOpts = '';
        
        if(0!=($iErr = $this->getSelectOpts($sOpts, $iIdDomSel, $sDomSel)));
        else if(0!=($iErr = $Page->addBody(
            '<div class="InputForm">'
              .'<form id="domainid_selector" name="domainid_selector" action="'.$_SERVER['PHP_SELF'].'" method="POST">'
                .'<input type="hidden" name="cmd" value="cmd_setIdDomain" />'
                .'<table class="InputForm">'
                  .'<tr>'
                    .'<td class="label">Selected:</td>'
                    .'<td class="value">'
                      .'<select name="iddomain" onChange="document.domainid_selector.submit();">'
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
    public function getSelectOpts(&$sOpts, &$iIdDomSel, &$sDomSel)
    {
        $iErr = 0;
        
        if(0!=$iIdDomSel && !$this->isValidIdDomain($iIdDomSel)) $iIdDomSel = 0;

        if(0!=($iErr = $this->App->DB->query($rRslt,
            "SELECT"
            ." domain.name AS sName"
            .",domain.id AS iId"
            ." FROM `virtual_domains` AS domain"
            ." ORDER BY domain.name ASC"
        )));
        else if(0!=($iErr = $this->App->DB->getNumRows($nRows, $rRslt)));
        else if(0==$nRows) $sHtml .= '<option value="0">No domains available</option>';
        else while(0==($iErr = $this->App->DB->fetchArray($aRow, $rRslt, MYSQLI_ASSOC)) && NULL!==$aRow){
            if(0==$iIdDomSel) $iIdDomSel = $aRow['iId'];
            if($iIdDomSel == $aRow['iId']) $sDomSel = $aRow['sName'];
            
            $sOpts .=
                '<option value="'.strval($aRow['iId']).'"'
                  .($aRow['iId']!=$iIdDomSel ? '' : ' selected="selected"')
                .'>'
                .$aRow['sName']
                .'</option>'
            ;
        }
        return($iErr);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    public function getDomainName(&$sName, $iId)
    {
        $iErr  = 0;
        $sName = '';
        
        if(0!=($iErr = $this->App->DB->queryOneRow($aRow, "SELECT name FROM virtual_domains WHERE id='".strval(intval($iId))."'")));
        else if(NULL===$aRow);
        else $sName = $aRow['name'];
        
        return($iErr);
    }
// ########## METHOD PROTECTED
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    protected function createDomain(&$sMsg, &$bSuccess, $sName)
    {
        $iErr = 0;
        $bSuccess = false;
        
        if(0==strlen($sName = trim($sName)));
        else if($this->doesDomainExist($sName)){
            $sMsg .= 'Domain "'.$sName.'" is already in database!';
        }
        else if(0!=($iErr = $this->App->DB->state(
            // reminder: this has to work with SQLite (IMA-Demo), too (SQLite doesn't know the "INSERT ... SET" Syntax)
            "INSERT INTO virtual_domains (name) VALUES ('".$this->App->DB->realEscapeString($sName)."')"
        ))){
            lib\ErrLog::getInstance()->push('Could not create domain "'.$sName.'", something['.$iErr.'] went wrong!');
        }
        else{
            $sMsg = 'The domain "'.$sName.'" has been created and should show in the list below.';
            $bSuccess = true;
        }
        return($iErr);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    protected function deleteDomain(&$sMsg, &$bSuccess, $iId)
    {
        $iErr = 0;
        $bSuccess = false;
        
        if(0!=($iErr = $this->App->DB->queryOneRow($aRow, "SELECT name FROM virtual_domains WHERE id=".strval($iId))));
        else if(NULL==$aRow){
            $sMsg = 'No such Domain!';
        }
        else if(0!=($iErr = $this->App->DB->state("DELETE FROM virtual_domains WHERE id=".strval($iId)))){
            lib\ErrLog::getInstance()->push('Could not delete domain "'.$aRow['name'].'", something['.$iErr.'] went wrong!');
        }
        else{
            $sMsg = 'The domain "'.$aRow['name'].'" and all accounts/aliases associated with it have been deleted.';
            $bSuccess = true;
        }
        return($iErr);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    protected function doesDomainExist($sName)
    {
        $bRetVal = false;
        if(0!=($iErr = $this->App->DB->queryOneRow($aRow, "SELECT id FROM virtual_domains WHERE name='".$this->App->DB->realEscapeString($sName)."'")));
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
    protected function isValidIdDomain($iId)
    {
        $bRetVal = false;
        if(0!=($iErr = $this->App->DB->queryOneRow($aRow, "SELECT id FROM virtual_domains WHERE id='".strval(intval($iId))."'")));
        else if(NULL===$aRow);
        else $bRetVal = true;
        return($bRetVal);
    }
// ########## METHOD PRIVATE
};
?>