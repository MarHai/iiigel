<?php namespace Iiigel\Generic;

class Database {
    private $oDb = NULL;
    private $sLastQuery = '';
    private $oLastResult = NULL;
    private $sLastError = NULL;
    
    /**
     * Initiate a new database connection, based on aConfig/aDb settings.
     * Sets aConfig/aDb/sCharset and runs aConfig/aDb/sInitialQuery on success.
     */
    public function __construct() {
        $this->oDb = new \mysqli(
            $GLOBALS['aConfig']['aDb']['sHost'],
            $GLOBALS['aConfig']['aDb']['sUsername'],
            $GLOBALS['aConfig']['aDb']['sPassword'],
            $GLOBALS['aConfig']['aDb']['sDatabase']
        );
        if($this->oDb->connect_error) {
            throw new \Exception(_('error.dbconnection'));
        } else {
            $this->oDb->set_charset($GLOBALS['aConfig']['aDb']['sCharset']);
            if(isset($GLOBALS['aConfig']['aDb']['sInitialQuery']) && $GLOBALS['aConfig']['aDb']['sInitialQuery'] != '') {
                $this->query($GLOBALS['aConfig']['aDb']['sInitialQuery']);
            }
        }
    }
    
    /**
     * Getter method (yet, there is no setter for this class).
     * 
     * @param  string $_sProperty property to fetch
     * @return mixed  property value
     */
    public function __get($_sProperty) {
        return $this->$_sProperty;
    }
    
    /**
     * Query any statement to the database.
     * 
     * @param string $_sQuery the statement to be queried
     */
    public function query($_sQuery) {
        $this->sLastQuery = $_sQuery;
        $this->oLastResult = $this->oDb->query($_sQuery);
        if($this->oLastResult === FALSE) {
            $this->sLastError = $this->oDb->error;
        } else {
            $this->sLastError = NULL;
        }
        return $this->oLastResult;
    }
    
    /**
     * Fetch auto_increment ID of last statement.
     * 
     * @return integer ID of last statement; if last statement was not INSERT, 0 is returned
     */
    public function getLastId() {
        return $this->oDb->insert_id;
    }
    
    /**
     * Abstraction method for getting one single entry as an associative array.
     * 
     * @param  object &$_oResult resultset
     * @return array  associative array of single entry
     */
    public function get(&$_oResult) {
    	if (method_exists($_oResult, 'fetch_assoc')) {
    		return $_oResult->fetch_assoc();
    	} else {
    		return array();
    	}
    }
    
    /**
     * Abstraction method for getting the number of entries retrieved/affected.
     * 
     * @param  object  $_oResult resultset
     * @return integer number of rows retrieved/affected
     */
    public function count($_oResult) {
        return $_oResult->num_rows;
    }
    
    /**
     * Helper method for a combination of ->query and ->get for only one (the first) entry.
     * 
     * @param  string $_sQuery query
     * @return array  associative array of single (first) entry
     */
    public function getOneRow($_sQuery) {
        if(($oResult = $this->query($_sQuery))) {
            if($this->count($oResult) > 0) {
                return $this->get($oResult);
            } else {
                throw new \Exception(_('error.querynoresult'));
            }
        } else {
            throw new \Exception(_('error.querymissspelled'));
        }
    }
    
    /**
     * Escapes any input to the database according to the current charset and the variable's type.
     * 
     * @param  mixed  $_mValue variable to be escaped
     * @return string ready-for-input string (incl. quotation marks)
     */
    public function escape($_mValue) {
        if(is_numeric($_mValue)) {
            return $this->oDb->real_escape_string(1*$_mValue);
        } elseif(is_bool($_mValue)) {
            return $this->oDb->real_escape_string($_mValue ? 1 : 0);
        } elseif(is_array($_mValue)) {
            return $this->escape(json_encode($_mValue));
        } elseif(is_object($_mValue) || is_null($_mValue)) {
            return 'NULL';
        } else {
            return '\''.$this->oDb->real_escape_string($_mValue).'\'';
        }
    }
}

?>
