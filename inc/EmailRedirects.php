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
class EmailRedirects {
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
// ########## PROPS PRIVATE
    private $sLastSrc = '';
    private $sLastTar = '';
    private $iIdLastDom = 0;
// ########## CONST/DEST
    function __construct(IspMailAdminApp &$App, EmailDomains &$EDom)
    {
        $this->App  = &$App;
        $this->aStat = &$App->aRedStat;
        $this->EDom = &$EDom;
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
        $this->App->Page->setTitle('Redirects');
        $this->App->Page->setHelp(
            '<div class="Heading">Manage redirects from a locally accepted email-address to any other email-address (forward emails for <i>somebody@example.com</i> to <i>somebody.else@over.there.com</i></div>'
            .'<ul>'
            .'<li>Create a redirect: Enter the source email-address and click "Create"</li>'
            .'<li>Delete a redirect: Click on <img class="icon" src="./img/trash.png" alt="delete icon" /></li>'
            .'<li><b>Note</b>: You can redirect an existing account.</li>'
            .'<li><b>Note</b>: E&ndash;mails addressed to a deleted redirects will be rejected by the mailserver &ndash; unless you\'ve a "catchall"&ndash;account.</li>'
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
                
                if(!isset($this->App->aReqParam['ssrc']));
                else if(0==strlen($this->sLastSrc = trim($this->App->aReqParam['ssrc'])));
                else if(!isset($this->App->aReqParam['iiddomain']));
                else if(0>=($this->iIdLastDom = intval($this->App->aReqParam['iiddomain'])));
                else if(!isset($this->App->aReqParam['star']));
                else if(0==strlen($this->sLastTar = trim($this->App->aReqParam['star'])));
                else if(0!=($iErr = $this->create($sMsg, $bSuccess, $this->sLastSrc, $this->sLastTar, $this->iIdLastDom)));
                else $this->App->Page->drawMsg(!$bSuccess, $sMsg);

                // clear fields on success
                if($bSuccess){
                    $this->sLastSrc = '';
                    $this->sLastTar = '';
                }
                break;
                
            case 'cmd_delete':
                $bSuccess = false;
                
                if(!isset($this->App->aReqParam['idredirect']));
                else if(0>=($iIdRedirect = intval($this->App->aReqParam['idredirect'])));
                else if(0!=($iErr = $this->delete($sMsg, $bSuccess, $iIdRedirect)));
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
        $sDomOpts  = '';
        $sDomSel   = '';
        
        if(0!=($iErr = $this->EDom->getSelectOpts($sDomOpts, $this->iIdLastDom, $sDomSel)));
        else return($Page->addBody(
            '<h3>Create new</h3>'
            .'<div class="InputForm">'
              .'<form id="create_redirect" name="create_redirect" action="'.$_SERVER['PHP_SELF'].'" method="POST">'
                .'<input type="hidden" name="cmd" value="cmd_create" />'
                .'<table class="InputForm">'
                  .'<tr>'
                    .'<td class="label">Source:</td>'
                    .'<td class="value">'
                      .'<input type="text" name="ssrc" id="redirect_src" placeholder="name" value="'.$this->sLastSrc.'">'
                      .'@<select name="iiddomain">'
                        .$sDomOpts
                      .'</select>'
                    .'</td>'
                  .'</tr>'
                  .'<tr>'
                    .'<td class="label">Destination:</td>'
                    .'<td class="value">'
                      .'<input type="text" name="star" placeholder="account@somewhere.else.com" value="'.$this->sLastTar.'">'
                    .'</td>'
                  .'</tr>'
                  .'<tr>'
                    .'<td class="label">&nbsp;</td>'
                    .'<td class="submit">'
                      .'<a class="button" onClick=" verifyCreateRedirect(document.create_redirect);">Create</a>'
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
        $iErr     = 0;
        $sHtml    = '';
        $aSources = array();
        $nEntries=0;
        
        if(0!=($iErr = $this->App->DB->queryOneRow($aRow,
            "SELECT"
            ." COUNT(redir.id) AS nCnt"
            ." FROM virtual_aliases AS redir"
            ." LEFT JOIN virtual_users AS user ON(redir.destination=user.email)"
            ." WHERE ".$this->App->DB->sqlISNULL('user.id')
        )));
        else if(null===$aRow);
        else if(0!=($iErr = lib\checkListPages($this->aStat, ($nEntries = $aRow['nCnt']))));
        
        if(0!=($iErr = $this->App->DB->query($rRslt,
            "SELECT"
            ." redir.id AS iId"
            .",redir.source AS sSrc"
            .",redir.destination AS sTar"
            .",(SELECT"
                ." COUNT(alias.id)"
                ." FROM virtual_aliases as alias"
                ." LEFT JOIN virtual_users AS user ON(user.email=alias.destination)"
                ." WHERE"
                ." alias.source=redir.source"
                ." AND NOT ".$this->App->DB->sqlISNULL('user.id')
            .") AS nAliases"
            ." FROM virtual_aliases AS redir"
            ." LEFT JOIN virtual_users AS user ON(redir.destination=user.email)"
            ." WHERE ".$this->App->DB->sqlISNULL('user.id')
            ." ORDER BY sSrc, sTar ASC"
            .lib\makeListPagesSqlLimit($this->aStat)
        ))); 
        else if(0!=($iErr = $this->App->DB->getNumRows($nRows, $rRslt)));
        else if(0==$nRows) $sHtml .= '<tr class=""><td class="" colspan="6">No redirects created yet</td></tr>';
        else while(0==($iErr = $this->App->DB->fetchArray($aRow, $rRslt, MYSQLI_ASSOC)) && NULL!==$aRow){
            $aSources[$aRow['sSrc']][$aRow['sTar']] = $aRow['iId'];
            $aAliases[$aRow['sSrc']] = $aRow['nAliases'];
        }
        
        foreach($aSources as $sSrc => $aTar){
            $aAliasTar = array();
            $sHtmlTars  = '';
            
            if(0!=$aAliases[$sSrc] && 0!=($iErr = $this->getAliasTargets($aAliasTar, $sSrc)));
            else foreach($aTar as $sTar => $iId) $sHtmlTars .= 
                    '<tr>'
                      .'<td class="icon">'
                        .'<form name="delete_redirect_'.strval($iId).'" action="'.$_SERVER['PHP_SELF'].'" method="POST">'
                          .'<input type="hidden" name="cmd" value="cmd_delete" />'
                          .'<input type="hidden" name="idredirect" value="'.strval($iId).'" />'
                          .'<img class="icon" src="./img/trash.png" onClick="confirmDeleteRedirect(document.delete_redirect_'.strval($iId).', \''.$sSrc.'\', \''.$sTar.'\');" alt="icon delete"/>'
                        .'</form>'
                      .'</td>'
                      .'<td class="">'.$sTar.'</td>'
                    .'</tr>'
            ;
            
            $sHtml .= 
                '<tr>'
                  .'<td class="">'.$sSrc.'</td>'
                  .'<td class="">'
                    .'<table class="DatabaseListSub1">'
                      .$sHtmlTars
                    .'</table>'
                   .'</td>'
                   .'<td class="list">'.implode('<br />',$aAliasTar).'</td>'
                .'</tr>'
            ;
        }

        if(0!=$iErr);
        else if(0!=($iErr = $Page->addBody(
            '<h3>Existing Redirects</h3>'
            .'<div class="DatabaseList">'
              .lib\makeListPages($this->aStat, $nEntries, 'Alias_ListPage')
              .'<table class="DatabaseList">'
                .'<colgroup><col width="*"><col width="40%"><col width="10%"></colgroup>'
                .'<tr>'
                  .'<th>Source</th>'
                  .'<th>Destination</th>'
                  .'<th>Also&nbsp;alias&nbsp;of</th>'
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
    function getAliasTargets(&$aAliasTar, $sAlias)
    {
        $iErr      = 0;
        $aAliasTar = array();
        
        if(0!=($iErr = $this->App->DB->query($rRslt,
            "SELECT"
            ." alias.destination AS sTar"
            ." FROM virtual_aliases AS alias"
            ." LEFT JOIN virtual_users AS user ON(user.email=alias.destination)"
            ." WHERE"
            ." alias.source='".$this->App->DB->realEscapeString($sAlias)."'"
            ." AND NOT ".$this->App->DB->sqlISNULL('user.id')
        ))); 
        else if(0!=($iErr = $this->App->DB->getNumRows($nRows, $rRslt)));
        else if(0==$nRows);
        else while(0==($iErr = $this->App->DB->fetchArray($aRow, $rRslt, MYSQLI_ASSOC)) && NULL!==$aRow){
            $aAliasTar[] = $aRow['sTar'];
        }
        return($iErr);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    protected function create(&$sMsg, &$bSuccess, $sSrc, $sTar, $iIdDomain)
    {
        $iErr = 0;
        $bSuccess = false;
        
        
        if(0!=($iErr = $this->EDom->getDomainName($sDomain, $iIdDomain)));
        else if(0==strlen($sDomain)){
            $sMsg .= 'Invalid Domain['.$iIdDomain.']';
        }
        else if(false===($sSrcFull = $sSrc.'@'.$sDomain));
        /*
        else if($this->doesRedirectExist($sChkTar, $sSrcFull)){
            $sMsg .= 'Source "'.$sSrcFull.'" already exists as redirect/alias of "'.$sChkTar.'"';
        }
        */
        else if(0!=($iErr = $this->getExistingTargets($aTar, $sSrcFull)));
        else if($this->isLocalAccount($sTar)){
            $sMsg .= 'Destination "'.$sTar.'" is a local account.<br />Please use the "Alias"-Page to create aliases.';
        }
        else if($this->doesRedirectExist($sChkTar, $sTar)){
            $sMsg .= 'Destination "'.$sTar.'" is in itself already a redirect/alias of "'.$sChkTar.'"';
        }
        else if(in_array($sTar, $aTar)){
            $sMsg .= '"'.$sSrcFull.'" already exists as alias/redirect of "'.$sTar.'"';
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
            lib\ErrLog::getInstance()->push('Could not create redirect "'.$sSrcFull.'" to "'.$sTar.'", something['.$iErr.'] went wrong!');
        }
        else{
            $sMsg = 'Redirect "'.$sSrcFull.'" to "'.$sTar.'" has been created.';
            if(0!=count($aTar)) $sMsg .= '<br /><br /><b>Note</b>: This redirect is also an alias/redirect of <ul class="Msg"><li>'.implode('</li><li>', $aTar).'</li></ul>';
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
        else if(NULL==$aRow) $sMsg = 'No such redirect!';
        else if(0!=($iErr = $this->App->DB->state("DELETE FROM virtual_aliases WHERE id=".strval($iId)))){
            lib\ErrLog::getInstance()->push('Could not delete redirect "'.$aRow['source'].'", something['.$iErr.'] went wrong!');
        }
        else{
            $sMsg = 'The redirect "'.$aRow['source'].'" of "'.$aRow['destination'].'" has been deleted.';
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
    protected function getExistingTargets(&$aTar, $sSrc)
    {
        $iErr = 0;
        $aTar = array();
        if(0!=($iErr = $this->App->DB->query($rRslt, "SELECT destination as sTar FROM virtual_aliases WHERE source='".$this->App->DB->realEscapeString($sSrc)."'")));
        else if(0!=($iErr = $this->App->DB->getNumRows($nRows, $rRslt)));
        else if(0==$nRows);
        else while(0==($iErr = $this->App->DB->fetchArray($aRow, $rRslt, MYSQLI_ASSOC)) && NULL!==$aRow){
            $aTar[] = $aRow['sTar'];
        }
        return($iErr);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    protected function doesRedirectExist(&$sTar, $sSrc)
    {
        $bRetVal = false;
        if(0!=($iErr = $this->App->DB->queryOneRow($aRow, "SELECT destination as sTar FROM virtual_aliases WHERE source='".$this->App->DB->realEscapeString($sSrc)."'")));
        else if(NULL===$aRow);
        else{
            $sTar = $aRow['sTar'];
            $bRetVal = true;
        }
        return($bRetVal);
    }
    /**
    **
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    protected function isLocalAccount($sEmail)
    {
        $bRetVal = false;
        if(0!=($iErr = $this->App->DB->queryOneRow($aRow, "SELECT id FROM virtual_users WHERE email='".$this->App->DB->realEscapeString($sEmail)."'")));
        else if(NULL===$aRow);
        else $bRetVal = true;
        return($bRetVal);
    }
// ########## METHOD PRIVATE
};
?>