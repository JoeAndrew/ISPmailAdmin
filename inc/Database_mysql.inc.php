<?php
class Database {
    protected $_sHost = "";
    protected $_sPort = "";
    protected $_sSocket = "";
    protected $_sDataBase = "";
    protected $_sUser = "";
    protected $_sPass = "";
    protected $_rLink = false;
    protected $_iTransactionOpenCount = 0;
    protected $_bTransactionRollback = false;
    
    function __construct($sHost, $sPort, $sSocket, $sDataBase, $sUser, $sPass)
    {
        $this->_sHost       = $sHost    ; 
        $this->_sPort       = $sPort    ;
        $this->_sSocket     = $sSocket  ;
        $this->_sDataBase   = $sDataBase; 
        $this->_sUser       = $sUser    ; 
        $this->_sPass       = $sPass    ; 
    }
    function __destruct()
    {
        $this->verifyTransaction();
    }
    public function close()
    {
        $iErr = 0;
        if(false===$this->_rLink); 
        else{
            mysqli_close($this->_rLink);
            $this->_rLink = false;
        }
        return($iErr);
    }

    public function connect()
    {
        return($this->_connect());
    }

    public function fetchArray(&$aArray, &$rRslt, $iType=MYSQLI_ASSOC)
    {
        $aArray = mysqli_fetch_array($rRslt, $iType);
        return(0);
    }

    public function freeResult(&$rRslt)
    {
        mysqli_free_result($rRslt);
        return(0);
    }

    public function getNumRows(&$iCount, &$rRslt)
    {
        $iCount = mysqli_num_rows($rRslt);
        return(0);
    }

    public function query(&$rRslt, $sQuery)
    {
        $iErr = 0;
        if(false === ($rRslt = mysqli_query($this->_rLink, $sQuery))){
            $iErr = 1;
            lib\ErrLog::getInstance()->push("{".get_class($this)."} _query[".mysqli_errno($this->_rLink).", ".mysqli_error($this->_rLink)."] Query[".$sQuery."]");
        }
        return($iErr);
    }

    public function queryOneRow(&$aRow, $sQuery, $iType=MYSQLI_ASSOC)
    {
        $iErr  = 0;
        $rRslt = false;
        $aRow  = NULL;
        
        if(0!=($iErr = $this->query($rRslt, $sQuery)));
        else if(0!=($iErr = $this->getNumRows($nRows, $rRslt)));
        else if(0==$nRows);
        else if(0!=($iErr = $this->fetchArray($aRow, $rRslt, MYSQLI_ASSOC)) || NULL===$aRow);
        
        if($rRslt) $this->freeResult($rRslt);
        return($iErr);
    }

    public function realEscapeString($s)
    {
        return(mysqli_real_escape_string($this->_rLink, $s));
    }

    public function state($sState)
    {
        return($this->query($rIgnore, $sState));
    }
    public function startTransaction()
    {
        $iErr = 0;
        if(0==$this->_iTransactionOpenCount){
            if(false===$this->_rLink && 0!=($iErr = $this->connect()));
            else if(0!=($iErr = $this->state("BEGIN")));
            else $this->_iTransactionOpenCount=1;
            $this->_bTransactionRollback = false;
        }
        else $this->_iTransactionOpenCount++;
        return($iErr);
    }   

    public function commitTransaction()
    {
        $iErr = 0;
        if(0==$this->_iTransactionOpenCount){
            $iErr = 1;
            lib\ErrLog::getInstance()->push("{".get_class($this)."} commitTransaction: transaction nesting incomplete");
        }
        else{
            $this->_iTransactionOpenCount--;
            if(0==$this->_iTransactionOpenCount){
                if($this->_bTransactionRollback){
                    if(0!=($iErr = $this->state("ROLLBACK")));
                }
                if(false===$this->_rLink && 0!=($iErr = $this->connect()));
                else if(0!=($iErr = $this->state("COMMIT")));
            }
        }
        return($iErr);
    }   

    public function cancelTransaction()
    {
        $iErr = 0;
        if(0==$this->_iTransactionOpenCount){
            $iErr = 1; 
            lib\ErrLog::getInstance()->push("{".get_class($this)."} cancelTransaction: transaction nesting incomplete");
        }
        else{
            $this->_iTransactionOpenCount--;
            if(0==$this->_iTransactionOpenCount){
                if(false===$this->_rLink && 0!=($iErr = $this->connect()));
                else if(0!=($iErr = $this->state("ROLLBACK"))); 
            }
            else $this->_bTransactionRollback = true;
        }
        return($iErr);
    }   

    public function verifyTransaction()
    {
        $iErr=0;
        if(0!=$this->_iTransactionOpenCount){
            $iErr = 1; 
            lib\ErrLog::getInstance()->push("{".get_class($this)."} verifyTransaction: transaction nesting incomplete");
            $this->_iTransactionOpenCount=1;
            $this->cancelTransaction();
        }
        return($iErr);
    }
    public function sqlISNULL($sField)
    {
        return("ISNULL(".$sField.")");
    }
    public function getVersion()
    {
        return(mysqli_get_server_version($this->_rLink));
    }


    protected function _connect()
    {
        $iErr = 0;
        if(false===($this->_rLink = @mysqli_connect($this->_sHost, $this->_sUser, $this->_sPass, $this->_sDataBase, $this->_sPort, $this->_sSocket))){
            $iErr = 1;
            lib\ErrLog::getInstance()->push("{".get_class($this)."} _connect: connect to ".$this->_sHost."[".mysqli_connect_errno().", ".mysqli_connect_error()."]");
        }
        return($iErr);
    }

};
?>
