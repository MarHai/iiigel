<?php namespace Iiigel\Model;

abstract class GenericModel {
    private $nId = NULL;
    private $aData = array();
    private $aMapping = array();
    private $nIterator = FALSE;
    const TABLE = '';
    const CONFIG_COLUMN = array('bDeleted', 'nCreate', 'nUpdate', 'nIdCreator', 'nIdUpdater');
    const DEFAULT_ORDER = 'nId ASC';
    
    /**
     * Initiates new object which could be one of the following cases:
     * (1) set up object based on ID (integer)
     * (2) set up object based on row data including an nId
     * (3) set up new object (row) data without an ID (for login or registration)
     * (4) set up object based on hash ID (string)
     * (5) for the moment, do nothing
     * 
     * @param mixed [$_mInit         = NULL] integer if case 1, array with nId field if case 2, array without nId if case 3, NULL otherwise (4)
     */
    public function __construct($_mInit = NULL) {
        if(is_array($_mInit)) {
            if(isset($_mInit['nId'])) {
                $this->setId($_mInit['nId']);
                $this->setData($_mInit);
            } else {
                $this->setData($_mInit);
            }
        } elseif(is_int($_mInit)) {
            $this->setId($_mInit);
            $this->load();
        } elseif(is_string($_mInit)) {
            $this->loadFromHash($_mInit);
        }
    }
    
    /**
     * Access id (->nId) or any other data (->NAME).
     * 
     * @param  string $_sName data param name
     * @return mixed  depending on parameter; NULL if not set
     */
    public function __get($_sName) {
        if($_sName == 'nId') {
            return $this->nId;
        } elseif(isset($this->aData[$_sName])) {
            return $this->aData[$_sName];
        } else {
            return NULL;
        }
    }
    
    /**
     * Check whether a parameter isset and available.
     * 
     * @param  string $_sName data param name
     * @return boolean  TRUE if set, FALSE otherwise
     */
    public function __isset($_sName) {
        if($_sName == 'nId' && $this->nId > 0) {
            return TRUE;
        } else {
            return isset($this->aData[$_sName]);
        }
    }
    
    /**
     * Iterate over all parameters from this object. Returns FALSE on end and key/value pairs for all other cases.
     * Usage: while((list($sKey, $mValue) = $o->get())) { ... }
     * 
     * @return mixed key/value array or FALSE on end
     */
    public function get() {
        if($this->nIterator === FALSE) {
            $this->nIterator = 0;
        }
        if(count($this->aData) > $this->nIterator) {
            $aReturn = array_slice($this->aData, $this->nIterator, 1, TRUE);
            $this->nIterator++;
            if(isset($this->aMapping[array_keys($aReturn)[0]])) {
                $aReturn[array_keys($aReturn)[0]] = $this->aMapping[array_keys($aReturn)[0]];
            }
            return $aReturn;
        } else {
            $this->nIterator = FALSE;
            return FALSE;
        }
    }
    
    /**
     * Runs through all columns (via ->get()) and returns the loaded entry (so far).
     * 
     * @param boolean $_bIncludeConfigColumns if true, really all columns are included
     * @return array   key/value pairing of all columns except CONFIG ones
     */
    public function getCompleteEntry($_bIncludeConfigColumns = FALSE) {
        $aData = array();
        while(($aRow = $this->get())) {
            $sKey = array_keys($aRow)[0];
            if((!in_array($sKey, $this::CONFIG_COLUMN) && $sKey != 'nId') || $_bIncludeConfigColumns) {
                $aData[$sKey] = $aRow[$sKey];
            }
        }
        return $aData;
    }
    
    /**
     * Load possible foreign keys for a specific column. Necessary for frontend editing and the foreign-key select fields.
     * 
     * @param  string $_sColumn name of column for which to get the possible foreign keys
     * @return array  array with every "row" representing one possible foreign key as assoc. array with value and text
     */
    public function getPossibleForeignKeys($_sColumn) {
        $sTable = '';
        if($_sColumn == 'nIdCreator' || $_sColumn == 'nIdUpdater') {
            if(($oResult = $GLOBALS['oDb']->query('SELECT nId AS value, sName AS text FROM `user` WHERE NOT bDeleted AND bActive ORDER BY sName ASC'))) {
                $aReturn = array();
                while(($aRow = $GLOBALS['oDb']->get($oResult))) {
                    $aReturn[] = $aRow;
                }
                return $aReturn;
            }
        }
        return array();
    }
    
    /**
     * Sets the current object's ID.
     * 
     * @param  integer $_nId the ID to set
     * @return boolean TRUE if successful (i.e., ID is >0 and integer)
     */
    public function setId($_nId) {
        $nId = intval($_nId);
        if($nId > 0) {
            $this->nId = $_nId;
            return TRUE;
        }
        return FALSE;
    }
    
    /**
     * Set one specific parameter.
     * 
     * @param  string  $_sName  parameter name (be careful as it needs to match a DB column but not one of CONFIG_COLUMN or nId
     * @param  mixed   $_mValue value to insert
     * @return boolean true if value was set, false otherwise
     */
    public function __set($_sName, $_mValue) {
        return $this->setParameter($_sName, $_mValue);
    }
    
    /**
     * Used by ->setData and __set magic method in order to bring stuff together.
     * Allows only columns not inside CONFIG_COLUMN (except if _bSetWithoutNameChecking is true.
     * Also, if a columns has the same name as an already existent parameter but the first char is s instead of n, this is set into ->aMapping as it normally represents the foreign key show value.
     * 
     * @param  string  $_sName                             parameter name to set
     * @param  mixed   $_mValue                            value of newly set parameter
     * @param  boolean [$_bSetWithoutNameChecking          = FALSE] if TRUE, no CONFIG_COLUMN checking takes place
     * @return boolean TRUE if parameter was set correctly
     */
    protected function setParameter($_sName, $_mValue, $_bSetWithoutNameChecking = FALSE) {
        if(!in_array($_sName, array_merge($this::CONFIG_COLUMN, array('nId'))) || $_bSetWithoutNameChecking) {
            if($_sName{0} == 's' && isset($this->aData['n'.substr($_sName, 1)])) {
                $this->aMapping['n'.substr($_sName, 1)] = $_mValue;
            } else {
                $this->aData[$_sName] = $_mValue;
            }
            return TRUE;
        }
        return FALSE;
    }
    
    /**
     * Sets the current object's data. Ignores some reserved fields (nId, nCreate, etc.) but uses all other fields submitted.
     * 
     * @param array   $_aData user data
     * @return integer number of data values set (in this process only)
     */
    public function setData($_aData) {
        $nSet = 0;
        reset($_aData);
        foreach($_aData as $sKey => $mValue) {
            if($this->setParameter($sKey, $mValue, TRUE)) {
                $nSet++;
            }
        }
        return $nSet;
    }
    
    /**
     * Loads user data based on hashed ID.
     * 
     * @return boolean TRUE if successful, false (and error thrown) otherwise
     */
    public function loadFromHash($_sHashId) {
        try {
            $aData = $GLOBALS['oDb']->getOneRow('SELECT nId FROM `'.$this::TABLE.'` WHERE sHashId = '.$GLOBALS['oDb']->escape($_sHashId).' LIMIT 1');
            if($this->setId(intval($aData['nId']))) {
                $this->load();
                return TRUE;
            }
        } catch(\Exception $oException) {
            throw new \Exception(sprintf(_('error.objectload'), ucfirst($this::TABLE)), 0, $oException);
        }
        return FALSE;
    }
    
    /**
     * Loads user data based on this->nId.
     * 
     * @return boolean TRUE if successful, false (and error thrown) otherwise
     */
    public function load() {
        if($this->nId !== NULL) {
            try {
                $bCreator = in_array('nIdCreator', $this::CONFIG_COLUMN);
                $bUpdater = in_array('nIdUpdater', $this::CONFIG_COLUMN);
                $this->setData($GLOBALS['oDb']->getOneRow('SELECT a.* '.
                    ($bCreator ? ', b.sName AS sIdCreator ' : '').
                    ($bUpdater ? ', c.sName AS sIdUpdater ' : '').
                'FROM `'.$this::TABLE.'` a '.
                    ($bCreator ? 'LEFT JOIN user b ON (b.nId = a.nIdCreator) ' : '').
                    ($bUpdater ? 'LEFT JOIN user c ON (c.nId = a.nIdUpdater) ' : '').
                'WHERE a.nId = '.$GLOBALS['oDb']->escape($this->nId).' LIMIT 1'));
                return TRUE;
            } catch(\Exception $oException) {
                throw new \Exception(sprintf(_('error.objectload'), ucfirst($this::TABLE)), 0, $oException);
            }
        }
        return FALSE;
    }
    
    /**
     * If current entry does not have an ID, entry is created and ID is returned. Also, object is updated with INSERT information.
     * 
     * @return integer ID if successful, NULL otherwise
     */
    public function create() {
        if($this->nId === NULL && $this->changesAllowed()) {
            $aValue = array();
            if(in_array('bDeleted', $this::CONFIG_COLUMN)) {
                $aValue['bDeleted'] = 0;
            }
            if(in_array('nCreate', $this::CONFIG_COLUMN)) {
                $aValue['nCreate'] = time();
            }
            if(in_array('nIdCreator', $this::CONFIG_COLUMN)) {
                $aValue['nIdCreator'] = isset($GLOBALS['oUserLogin']) ? $GLOBALS['oUserLogin']->nId : NULL;
            }
            reset($this->aData);
            foreach($this->aData as $sKey => $mValue) {
                if(!in_array($sKey, $this::CONFIG_COLUMN) && $sKey != 'nId') {
                    $aValue[$sKey] = $GLOBALS['oDb']->escape($mValue);
                }
            }
            if($GLOBALS['oDb']->query('INSERT INTO `'.$this::TABLE.'` ('.implode(', ', array_keys($aValue)).') VALUES ('.implode(', ', $aValue).')')) {
                if($this->setId($GLOBALS['oDb']->getLastId())) {
                    $this->load();
                    $this->sHashId = $this->hashId();
                    $this->update();
                    return $this->nId;
                }
            } else {
                throw new \Exception(_('error.create'));
            }
        }
        return NULL;
    }
    
    /**
     * Updates current entry (based on nId) with all data set.
     * 
     * @return boolean true if successful, false otherwise
     */
    public function update() {
        if($this->nId > 0 && count($this->aData) > 1 && $this->changesAllowed()) {
            $aValue = array();
            if(in_array('nUpdate', $this::CONFIG_COLUMN)) {
                $aValue[] = 'nUpdate = '.time();
            }
            if(in_array('nIdUpdater', $this::CONFIG_COLUMN)) {
                $aValue[] = 'nIdUpdater = '.(isset($GLOBALS['oUserLogin']) ? $GLOBALS['oUserLogin']->nId : NULL);
            }
            reset($this->aData);
            foreach($this->aData as $sKey => $mValue) {
                if(!in_array($sKey, $this::CONFIG_COLUMN) && $sKey != 'nId') {
                    $aValue[] = $sKey.' = '.$GLOBALS['oDb']->escape($mValue);
                }
            }
            return $GLOBALS['oDb']->query('UPDATE `'.$this::TABLE.'` SET '.implode(', ', $aValue).' WHERE nId = '.$this->nId.' LIMIT 1');
        }
        return FALSE;
    }
    
    /**
     * Deletes current row.
     * 
     * @return boolean true if successfully deleted, false otherwise
     */
    public function delete() {
        if($this->nId > 0 && $this->changesAllowed()) {
            if(in_array('bDeleted', $this::CONFIG_COLUMN)) {
                $aValue = array();
                $aValue[] = 'bDeleted = 1';
                if(in_array('nUpdate', $this::CONFIG_COLUMN)) {
                    $aValue[] = 'nUpdate = '.time();
                }
                if(in_array('nIdUpdater', $this::CONFIG_COLUMN)) {
                    $aValue[] = 'nIdUpdater = '.(isset($GLOBALS['oUserLogin']) ? $GLOBALS['oUserLogin']->nId : NULL);
                }
                return $GLOBALS['oDb']->query('UPDATE `'.$this::TABLE.'` SET '.implode(', ', $aValue).' WHERE nId = '.$this->nId.' LIMIT 1');
            } else {
                return $GLOBALS['oDb']->query('DELETE FROM `'.$this::TABLE.'` WHERE nId = '.$this->nId.' LIMIT 1');
            }
        }
        return FALSE;
    }
    
    /**
     * Load list of all entries, no matter of the current one.
     * 
     * @return object oDb result object
     */
    public function getList() {
        return $GLOBALS['oDb']->query('SELECT * FROM `'.$this::TABLE.'`'.(in_array('bDeleted', $this::CONFIG_COLUMN) ? ' WHERE NOT bDeleted' : '').' ORDER BY '.$this::DEFAULT_ORDER);
    }
    
    /**
     * Transform nId into a short hash value in order to show it to the users.
     * 
     * @return string 12-character hash value representation of nId (and some other stuff)
     */
    public function hashId() {
        list($sKey) = explode(' ', $this::DEFAULT_ORDER, 2);
        $sKey = hash('sha256', $GLOBALS['aConfig']['sSalt'].$this->$sKey.$this->nId);
        $sKey = substr($this::TABLE, 0, 1).strtr(base64_encode(substr($sKey, 0, 11)), '+/=', '-_:');
        return $sKey;
    }
    
    /**
     * Used by GenericModel in order to find out whether changes on the current entry are allowed for the currently logged-in user.
     * 
     * @return boolean true if allowed, false otherwise
     */
    protected function changesAllowed() {
        if($GLOBALS['oUserLogin']->bAdmin) {
            return TRUE;
        } elseif(isset($GLOBALS['oUserLogin'])) {
            return $GLOBALS['oDb']->count($GLOBALS['oDb']->query('SELECT * FROM `right` WHERE nIdUser = '.$GLOBALS['oUserLogin']->nId.' AND eType = '.$GLOBALS['oDb']->escape($this::TABLE).' AND nIdType = '.$GLOBALS['oDb']->escape($this->nId))) > 0;
        } else {
            return FALSE;
        }
    }
    
    /**
     * Return list of users which should be shown in the dropdown.
     * 
     * @param string $_sHashId hashed ID
     * @return array array with arrays with nId and sName keys set
     */
    public static function getRightsUserSelection($_sHashId) {
        $aData = array();
        if(($oResult = $GLOBALS['oDb']->query('SELECT nId, sName FROM `user` WHERE NOT bDeleted ORDER BY sName ASC'))) {
            while(($aRow = $GLOBALS['oDb']->get($oResult))) {
                $aData[] = $aRow;
            }
        }
        return $aData;
    }
    
    /**
     * Return list of entries which should be shown in the dropdown.
     * 
     * @param string $_sHashId hashed ID
     * @return array array with up to four keys (chapter, group, institution, module) which itself hold arrays with nId and sName keys set
     */
    public static function getRightsTypeIdSelection($_sHashId) {
        $aReturn = array();
        
        $aData = array();
        if(($oResult = $GLOBALS['oDb']->query('SELECT sHashId AS nId, sName FROM `chapter` WHERE NOT bDeleted ORDER BY sName ASC'))) {
            while(($aRow = $GLOBALS['oDb']->get($oResult))) {
                $aData[] = $aRow;
            }
        }
        $aReturn['chapter'] = $aData;
        
        $aData = array();
        if(($oResult = $GLOBALS['oDb']->query('SELECT sHashId AS nId, sName FROM `group` WHERE NOT bDeleted ORDER BY sName ASC'))) {
            while(($aRow = $GLOBALS['oDb']->get($oResult))) {
                $aData[] = $aRow;
            }
        }
        $aReturn['group'] = $aData;
        
        $aData = array();
        if(($oResult = $GLOBALS['oDb']->query('SELECT sHashId AS nId, sName FROM `institution` WHERE NOT bDeleted ORDER BY sName ASC'))) {
            while(($aRow = $GLOBALS['oDb']->get($oResult))) {
                $aData[] = $aRow;
            }
        }
        $aReturn['institution'] = $aData;
        
        $aData = array();
        if(($oResult = $GLOBALS['oDb']->query('SELECT sHashId AS nId, sName FROM `module` WHERE NOT bDeleted ORDER BY sName ASC'))) {
            while(($aRow = $GLOBALS['oDb']->get($oResult))) {
                $aData[] = $aRow;
            }
        }
        $aReturn['module'] = $aData;
        return $aReturn;
    }
}

?>