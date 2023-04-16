<?php
/**
**
**
** @package    ISPMailAdmin
** @author     Ole Jungclaussen
** @version    1.0.1
**/
/**
** @public
**/
class Database {
// ########## PROPS PUBLIC
// ########## PROPS PROTECTED
    /** 
    ** Host name for connection.
    ** @type string
    **/
    protected $_sHost = "";
    /** 
    ** Port for connection.
    ** @type string
    **/
    protected $_sPort = "";
    /** 
    ** Socket for connection.
    ** @type string
    **/
    protected $_sSocket = "";
    /** 
    ** Database name for connection.
    ** @type string
    **/
    protected $_sDataBase = "";
    /** 
    ** User name for connection.
    ** @type string
    **/
    protected $_sUser = "";
    /** 
    ** Password for connection.
    ** @type string
    **/
    protected $_sPass = "";
    /** 
    ** Connection Link.
    ** @type resource
    **/
    protected $_rLink = false;
    /**
    ** Internal transaction open count.
    ** @type int
    **/
    protected $_iTransactionOpenCount = 0;
    /**
    ** Internal transaction rollback flag.
    ** @type boolean
    **/
    protected $_bTransactionRollback = false;
// ########## PROPS PRIVATE
// ########## CONST/DEST
    /**
    ** Constructs a DataBaseConnection.
    ** @param string $sHost      Host to find database at
    ** @param string $sPort      Port to connect on to $sHost
    ** @param string $sDataBase  Name of Database to select
    ** @param string $sUser      User name under which to connect
    ** @param string $sPass      Password to transmit on connection
    **/
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
// ########## METHOD PUBLIC
    /**
    ** Closes this connection.
    ** @returns int
    ** @return 0 on success !0 on error
    **/
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
    /**
    ** Open connection.
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    public function connect()
    {
        return($this->_connect());
    }
    /**
    **
    ** Fetches the next row of an query result and stores fields in array.
    ** <p>
    ** Mit fetchArray() kann man sich anhand einer Ergebnis-Kennung ($rRslt)
    ** einen Datensatz in einem assoziativen Array übergeben lassen.
    ** Dabei werden die Feldnamen innerhalb der Tabelle als Schlüssel des Arrays
    ** genutzt. Im Erfolgsfall liefert diese Funktion den aktuellen Datensatz, sonst wird
    ** ist $aArray false.
    ** Der zweite Parameter ($iType) ist optional. Sie können in diesem Parameter
    ** folgende Konstanten übergeben:<br>
    ** <ul>
    ** <li>MYSQLI_ASSOC: associative indexed array (fieldnames as indexes)</li>
    ** <li>MYSQLI_NUM:   numerical indexed array</li>
    ** <li>MYSQLI_BOTH:  both associative and numerical indexed</li>
    ** </ul>
    ** </p>
    ** @param array    $aArray  Takes fields from result row ($aArray will be false after last row)
    ** @param resource $rRslt   Result from a query
    ** @param int      $iType   Defines the type of the returned array
    **                          - MYSQLI_ASSOC associative indexed array (fieldnames as indexes)
    **                          - MYSQLI_NUM   numerical indexed array
    **                          - MYSQLI_BOTH  both associative and numerical indexed
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    public function fetchArray(&$aArray, &$rRslt, $iType=MYSQLI_ASSOC)
    {
        $aArray = mysqli_fetch_array($rRslt, $iType);
        return(0);
    }
    /**
    ** Free a result from memory.
    ** <p>
    ** Mit freeResult() kann man anhand einer Ergebnis-Kennung ($rRslt)
    ** den belegten Speicher wieder freigeben.<br>
    ** Diese Funktion ist auf Servern mit sehr wenig Arbeitsspeicher sinnvoll, um die
    ** Resourcen wieder freizugeben. Nach Beendigung des Skripts wird der Speicher
    ** automatisch wieder freigegeben.<br>
    ** Beachten Sie, dass nach dieser Funktion $rRslt ungültig ist und nicht mehr 
    ** auf das Ergebnis Ihrer Anfrage zurückgegriffen werden kann, da dieses 
    ** entfernt wurde.
    ** </p>
    ** @param resource $rRslt Result from a query
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    public function freeResult(&$rRslt)
    {
        mysqli_free_result($rRslt);
        return(0);
    }
    /**
    ** Get the number of rows in query result.
    ** <p>
    ** Mit getNumRows() kann man sich anhand einer Ergebnis-Kennung
    ** ($rRslt) die Anzahl der Datensätze eines Ergebnisses zurückgeben
    ** lassen.  
    ** </p>
    ** @param int      $iCount  Takes the number of rows in query result
    ** @param resource $rRslt Result from a query
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    public function getNumRows(&$iCount, &$rRslt)
    {
        $iCount = mysqli_num_rows($rRslt);
        return(0);
    }
    /**
    ** Executes a query on a database.
    **
    ** @param resource $rRslt     Takes the result of the query
    ** @param string   $sQuery    SQL-Query string
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    public function query(&$rRslt, $sQuery)
    {
        $iErr = 0;
        if(false === ($rRslt = mysqli_query($this->_rLink, $sQuery))){
            $iErr = 1; // query failed
            lib\ErrLog::getInstance()->push("{".get_class($this)."} _query[".mysqli_errno($this->_rLink).", ".mysqli_error($this->_rLink)."] Query[".$sQuery."]");
        }
        return($iErr);
    }
    /**
    ** Executes a one row query on a database.
    **
    ** @param resource $aRow        Takes the first result-row of the query
    ** @param string   $sQuery      SQL-Query string
    ** @param boolean  $bShowResult Print table of results directly to screen
    **
    ** @returns int
    ** @return 0 on success !0 on error
    **/
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
    /**
    **
    **
    ** @retval string
    ** @returns sanitized string
    **/
    public function realEscapeString($s)
    {
        return(mysqli_real_escape_string($this->_rLink, $s));
    }
    /**
    ** Executes a statement on the data source.
    **
    ** @param string  $sState  SQL-Statement
    **
    ** @retval integer
    ** @returns !=0 on error
    **/
    public function state($sState)
    {
        return($this->query($rIgnore, $sState));
    }
// ----------------------------------------------------------------------------
// TRANSACTIONS
    /**
    ** Start a transaction.
    **
    ** @returns int
    ** @return 0 on success !0 on error
    **/
    public function startTransaction()
    {
        $iErr = 0;
    // IF THIS IS THE VERY FIRST (OUTER) BEGIN, EXECUTE IT.
        if(0==$this->_iTransactionOpenCount){
            if(false===$this->_rLink && 0!=($iErr = $this->connect()));
            else if(0!=($iErr = $this->state("BEGIN")));
            else $this->_iTransactionOpenCount=1;
            // set cancel flag to false
            $this->_bTransactionRollback = false;
        }
    // INCREASE NESTING COUNT
        else $this->_iTransactionOpenCount++;
        return($iErr);
    }   
    /**
    ** Commit a transaction.
    **
    ** @returns int
    ** @return 0 on success !0 on error
    **/
    public function commitTransaction()
    {
        $iErr = 0;
        // We've a nesting error here (more COMMITs/ROLLBACKs than BEGINs)
        if(0==$this->_iTransactionOpenCount){
            $iErr = 1; // transaction nesting incomplete
            lib\ErrLog::getInstance()->push("{".get_class($this)."} commitTransaction: transaction nesting incomplete");
        }
        else{
            // decrease nesting count
            $this->_iTransactionOpenCount--;
            // if this is the very last (outer) COMMIT/ROLLBACK, execute it
            if(0==$this->_iTransactionOpenCount){
                // one or more of the nested transactions failed: ROLLBACK all!
                if($this->_bTransactionRollback){
                    if(0!=($iErr = $this->state("ROLLBACK"))); // rollback failed
                }
                // all nested transactions were COMMITed
                if(false===$this->_rLink && 0!=($iErr = $this->connect()));
                else if(0!=($iErr = $this->state("COMMIT"))); // commit failed
            }
        }
        return($iErr);
    }   
    /**
    ** Cancel (rollback) a transaction.
    **
    ** @returns int
    ** @return 0 on success !0 on error
    **/
    public function cancelTransaction()
    {
        $iErr = 0;
        // We've a nesting error here (more COMMITs/ROLLBACKs than BEGINs)
        if(0==$this->_iTransactionOpenCount){
            $iErr = 1; // transaction nesting incomplete
            lib\ErrLog::getInstance()->push("{".get_class($this)."} cancelTransaction: transaction nesting incomplete");
        }
        else{
            // decrease nesting count
            $this->_iTransactionOpenCount--;
            // if this is the very last (outer) COMMIT/ROLLBACK, execute it
            if(0==$this->_iTransactionOpenCount){
                if(false===$this->_rLink && 0!=($iErr = $this->connect()));
                else if(0!=($iErr = $this->state("ROLLBACK"))); // rollback failed
            }
            // NOT the outer (very last) COMMIT/ROLLBACK
            // set cancel flag to true (last outer COMMIT/ROLLBACK will be ROLLBACK)
            else $this->_bTransactionRollback = true;
        }
        return($iErr);
    }   
    /**
    ** Cancels any open transactions.
    ** <P>
    ** This method should be invoked at the end of every script (prefferably in a
    ** registered shutdown function) to make sure that all database transactions
    ** have been committed or cancelled.
    ** </P>
    ** @returns int
    ** @return 0 on success !0 on error
    **/
    public function verifyTransaction()
    {
        $iErr=0;
        if(0!=$this->_iTransactionOpenCount){
            $iErr = 1; // transaction nesting not completed
            lib\ErrLog::getInstance()->push("{".get_class($this)."} verifyTransaction: transaction nesting incomplete");
            // Rollback
            $this->_iTransactionOpenCount=1;
            $this->cancelTransaction();
        }
        return($iErr);
    }
// ----------------------------------------------------------------------------
// SYNTAX
    /**
    **
    **
    ** @retval string
    ** @returns correct isnull syntax
    **/
    public function sqlISNULL($sField)
    {
        return("ISNULL(".$sField.")");
    }
// ----------------------------------------------------------------------------
// MISC
    /**
    **
    **
    ** @retval integer
    ** @returns version
    **/
    public function getVersion()
    {
        return(mysqli_get_server_version($this->_rLink));
    }
// ########## METHOD PROTECTED
    /**
    ** (Re-)connects to data source if necessary
    ** @returns int
    ** @return 0 on success !0 on error
    **/
    protected function _connect()
    {
        $iErr = 0;
        if(false===($this->_rLink = @mysqli_connect($this->_sHost, $this->_sUser, $this->_sPass, $this->_sDataBase, $this->_sPort, $this->_sSocket))){
            $iErr = 1; // failed to connect to host
            lib\ErrLog::getInstance()->push("{".get_class($this)."} _connect: connect to ".$this->_sHost."[".mysqli_connect_errno().", ".mysqli_connect_error()."]");
        }
        return($iErr);
    }
// ########## METHOD PRIVATE
};
?>