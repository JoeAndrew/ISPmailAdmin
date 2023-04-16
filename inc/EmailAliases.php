<?php
/**
**
**
** @package    ISPmail_Admin
** @author     Ole Jungclaussen
** @version    0.9.5
**/
/**
** @public
**/
class EmailAliases {
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
    /**
    **
    ** @type EmailDomains
    **/
    protected $EDom = false;
    /**
    **
    ** @type EmailAccounts
    **/
    protected $EAcc = false;
// ########## PROPS PRIVATE
    private $sLastSrc = '';
    private $iIdLastDom = 0;
// ########## CONST/DEST
    function __construct(IspMailAdminApp &$App, EmailDomains &$EDom, EmailAccounts &$EAcc)
    {
        $this->App   = &$App;
        $this->aStat = &$App->aAlsStat;
        $this->EDom  = &$EDom;
        $this->EAcc  = &$EAcc;
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
        $this->App->Page->setTitle('Aliases');
        $this->App->Page->setHelp(
            '<div class="Heading">Manage aliases to existing email-accounts (info@example.com as an alias of user1@example.com). All emails send to the alias will end up in the account.</div>'
            .'<ul>'
            .'<li>Choose the account you want to modify/view aliases from the dropdown list</li>'
            .'<li>Create an alias: Enter the source email-address and click "Create"</li>'
            .'<li>Delete an alias: Click on <img class="icon" src="./img/trash.png" alt="delete icon" /></li>'
            .'<li><b>Note</b>: E&ndash;mails addressed to a deleted alias will be rejected by the mailserver &ndash; unless you\'ve a "catchall"&ndash;account.</li>'
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

                if(!isset($this->App->aReqParam['ssource']));
                else if(0==strlen($this->sLastSrc = trim($this->App->aReqParam['ssource'])));
                else if(!isset($this->App->aReqParam['iiddomain']));
                else if(0>=($this->iIdLastDom = intval($this->App->aReqParam['iiddomain'])));
                else if(0!=($iErr = $this->create($sMsg, $bSuccess, $this->sLastSrc, $this->iIdLastDom, $this->App->iIdAccSel)));
                else $this->App->Page->drawMsg(!$bSuccess, $sMsg);
                
                // clear fields on success
                if($bSuccess) $this->sLastSrc = '';
                break;

            case 'cmd_delete':
                $bSuccess = false;

                if(!isset($this->App->aReqParam['idalias']));
                else if(0>=($iIdAlias = intval($this->App->aReqParam['idalias'])));
                else if(0!=($iErr = $this->delete($sMsg, $bSuccess, $iIdAlias)));
                else $this->App->Page->drawMsg(!$bSuccess, $sMsg);
                break;

            case 'cmd_listpage':
                $this->aStat['iIdxPage'] = $this->App->aReqParam['idxpage'];
                break;

            default:
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
        if(0!=($iErr = $this->EDom->getSelectOpts($sDomOpts, $this->iIdLastDom, $sDomSel)));
        
        return($Page->addBody(
            '<h3>Create new</h3>'
            .'<div class="InputForm">'
              .'<form id="create_alias" name="create_alias" action="'.$_SERVER['PHP_SELF'].'" method="POST">'
                .'<input type="hidden" name="cmd" value="cmd_create" />'
                .'<table class="InputForm">'
                  .'<tr>'
                    .'<td class="label">Source:</td>'
                    .'<td class="value">'
                      .'<input type="text" name="ssource" id="alias_src" placeholder="name" value="'.$this->sLastSrc.'">'
                      .'@<select name="iiddomain">'
                        .$sDomOpts
                      .'</select>'
                    .'</td>'
                  .'</tr>'
                  .'<tr>'
                    .'<td class="label">Destination:</td>'
                    .'<td class="value">'
                      .'<input class="readonly" type="text" readonly="readonly" value="'.$this->App->sAccSel.'">'
                    .'</td>'
                  .'</tr>'
                  .'<tr>'
                    .'<td class="label">&nbsp;</td>'
                    .'<td class="submit">'
                      .'<a class="button" onClick=" verifyCreateAlias(document.create_alias);">Create</a>'
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
        
        if(0!=($iErr = $this->App->DB->queryOneRow($aRow,
            "SELECT"
            ." COUNT(alias.id) AS nCnt"
            ." FROM `virtual_aliases` AS alias"
            ." LEFT JOIN virtual_users AS user ON(user.email=alias.destination AND user.id=".strval($this->App->iIdAccSel).")"
            ." LEFT JOIN virtual_aliases AS alias2 ON(alias2.source=alias.source AND alias2.destination!=alias.destination)"
            ." WHERE NOT ".$this->App->DB->sqlISNULL('user.id')
        )));
        else if(null===$aRow);
        else if(0!=($iErr = lib\checkListPages($this->aStat, ($nEntries = $aRow['nCnt']))));
        
        if(0!=($iErr = $this->App->DB->query($rRslt,
            "SELECT"
            ." alias.id AS iId"
            .",alias.source AS sSrc"
            .",alias.destination AS sTar"
            .",COUNT(alias2.id) AS nAddTars"
            ." FROM virtual_aliases AS alias"
            ." LEFT JOIN virtual_users AS user ON(user.email=alias.destination AND user.id=".strval($this->App->iIdAccSel).")"
            ." LEFT JOIN virtual_aliases AS alias2 ON(alias2.source=alias.source AND alias2.destination!=alias.destination)"
            ." WHERE NOT ".$this->App->DB->sqlISNULL('user.id')
            ." GROUP BY iId, sSrc, sTar"
            ." ORDER BY sSrc ASC"
            .lib\makeListPagesSqlLimit($this->aStat)
        ))); 
        else if(0!=($iErr = $this->App->DB->getNumRows($nRows, $rRslt)));
        else if(0==$nRows) $sHtml .= '<tr class=""><td class="" colspan="6">No aliases created yet for this account</td></tr>';
        else while(0==($iErr = $this->App->DB->fetchArray($aRow, $rRslt, MYSQLI_ASSOC)) && NULL!==$aRow){
            $aAddTars = array();
            
            if(0!=$aRow['nAddTars'] && 0!=($iErr = $this->getExistingTargets($aAddTars, $aRow['sSrc'], $aRow['sTar'])));
            else $sHtml .= 
                '<tr>'
                .'<td class="icon">'
                  .'<form name="delete_alias_'.strval($aRow['iId']).'" action="'.$_SERVER['PHP_SELF'].'" method="POST">'
                    .'<input type="hidden" name="cmd" value="cmd_delete" />'
                    .'<input type="hidden" name="idalias" value="'.strval($aRow['iId']).'" />'
                    .'<img class="icon" src="./img/trash.png" onClick="confirmDeleteAlias(document.delete_alias_'.strval($aRow['iId']).', \''.$aRow['sSrc'].'\');" alt="icon delete"/>'
                  .'</form>'
                .'</td>'
                .'<td class="">'.$aRow['sSrc'].'</td>'
                .'<td class="list">'.implode('<br />', $aAddTars).'</td>'
                .'</tr>'
            ;
        }

        if(0!=$iErr);
        else if(0!=($iErr = $Page->addBody(
            '<h3>Existing Aliases for '.$this->App->sAccSel.'</h3>'
            .'<div class="DatabaseList">'
              .lib\makeListPages($this->aStat, $nEntries, 'Alias_ListPage')
              .'<table class="DatabaseList">'
              .'<colgroup><col width="16"><col width="*"><col width="30%"></colgroup>'
                .'<tr>'
                  .'<th></th>'
                  .'<th>Alias</th>'
                  .'<th>Also&nbsp;alias&nbsp;of&nbsp;/&nbsp;redirects&nbsp;to</th>'
                .'</tr>'
                .$sHtml
              .'</table>'
            .'</div>'
        )));
        
        return($iErr);
    }
// ########## METHOD PROTECTED
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    protected function create(&$sMsg, &$bSuccess, $sSrc, $iIdDomain, $iIdUsr)
    {
        $iErr = 0;
        $bSuccess = false;
        
        if(0!=($iErr = $this->EDom->getDomainName($sDomain, $iIdDomain)));
        else if(0==strlen($sDomain)){
            $sMsg .= 'Invalid Domain['.$iIdDomain.']';
        }
        else if(false===($sSrcFull = $sSrc.'@'.$sDomain));
        else if(0!=($iErr = $this->getExistingTargets($aTar, $sSrcFull)));
        else if(0!=($iErr = $this->EAcc->getAccount($sTar, $iIdDomain, $iIdUsr)));
        else if(0==strlen($sTar)){ $sMsg .= 'No such user'; }
        else if(in_array($sTar, $aTar)){
            $sMsg .= '"'.$sSrcFull.'" already exists as alias of "'.$sTar.'"';
        }
        else if(0!=($iErr = $this->App->DB->state(
            // reminder: this has to work with SQLite (IMA-Demo), too
            // - SQLite3 doesn't know the "INSERT ... SET" Syntax
            "INSERT INTO virtual_aliases (domain_id, source, destination) VALUES ("
              .strval($iIdDomain)
              .",'".$this->App->DB->realEscapeString($sSrcFull)."'"
              .",'".$this->App->DB->realEscapeString($sTar)."'"
            .")"
        ))){
            lib\ErrLog::getInstance()->push('Could not create alias "'.$sSrcFull.'" of "'.$sTar.'", something['.$iErr.'] went wrong!');
        }
        else{
            $sMsg = 'Alias "'.$sSrcFull.'" of "'.$sTar.'" has been created.';
            if(0!=count($aTar)) $sMsg .= '<br /><br /><b>Note</b>: This alias is also an alias of <ul class="Msg"><li>'.implode('</li><li>', $aTar).'</li></ul>';
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
    protected function delete(&$sMsg, &$bSuccess, $iId)
    {
        $iErr = 0;
        $bSuccess = false;
        
        if(0!=($iErr = $this->App->DB->queryOneRow($aRow, "SELECT source, destination FROM virtual_aliases WHERE id=".strval($iId))));
        else if(NULL==$aRow) $sMsg = 'No such alias!';
        else if(0!=($iErr = $this->App->DB->state("DELETE FROM virtual_aliases WHERE id=".strval($iId)))){
            lib\ErrLog::getInstance()->push('Could not delete alias "'.$aRow['source'].'", something['.$iErr.'] went wrong!');
        }
        else{
            $sMsg = 'The alias "'.$aRow['source'].'" of "'.$aRow['destination'].'" has been deleted.';
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
    protected function getExistingTargets(&$aTar, $sSrc, $sExcludeTar=false)
    {
        $iErr = 0;
        $aTar = array();
        if(0!=($iErr = $this->App->DB->query($rRslt, 
            "SELECT destination as sTar"
            ." FROM virtual_aliases"
            ." WHERE source='".$this->App->DB->realEscapeString($sSrc)."'"
            .(false===$sExcludeTar ? "" : " AND destination!='".$this->App->DB->realEscapeString($sExcludeTar)."'")
        )));
        else if(0!=($iErr = $this->App->DB->getNumRows($nRows, $rRslt)));
        else if(0==$nRows);
        else while(0==($iErr = $this->App->DB->fetchArray($aRow, $rRslt, MYSQLI_ASSOC)) && NULL!==$aRow){
            $aTar[] = $aRow['sTar'];
        }
        return($iErr);
    }
// ########## METHOD PRIVATE
};
?>