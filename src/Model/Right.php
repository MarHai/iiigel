<?php namespace Iiigel\Model;

class Right extends \Iiigel\Model\GenericModel {
    const TABLE = 'right';
    const DEFAULT_ORDER = 'eType ASC';
    const CONFIG_COLUMN = array('nCreate', 'nIdCreator');
    
    /**
     * Convert hashed ID into real ID or returns real ID directly.
     * 
     * @param  mixed   $_mId numeric ID or string hash ID
     * @return integer real (numeric) ID
     */
    protected function findRealIdForPermissionCheck($_mId) {
        if(strlen($_mId) > 11) {
            $sTable = '';
            switch(strtolower($_mId{0})) {
                case 'i':
                    $sTable = 'institution';
                    break;
                case 'g':
                    $sTable = 'group';
                    break;
                case 'm':
                    $sTable = 'module';
                    break;
                case 'c':
                    $sTable = 'chapter';
                    break;
            }
            if($sTable != '') {
            	$aRow = $GLOBALS['oDb']->getOneRow('SELECT nId FROM `'.$sTable.'` WHERE sHashId = '.$GLOBALS['oDb']->escape($_mId));
                return intval($aRow['nId']);
            } else {
                return 0;
            }
        } else {
            return intval($_mId);
        }
    }
    
    /**
     * Used by GenericModel in order to find out whether changes on the current entry are allowed for the currently logged-in user.
     * 
     * @return boolean true if allowed, false otherwise
     */
    protected function changesAllowed() {
        if($GLOBALS['oUserLogin']->bAdmin) {
            return TRUE;
        } elseif(isset($GLOBALS['oUserLogin']) && ($this->eType !== NULL || ($this->nIdType !== NULL && strlen($this->nIdType) > 11))) {
            $oQuery = NULL;
            $sType = $this->eType === NULL ? $this->nIdType{0} : $this->eType{0};
            switch($sType) {
                case 'i':
                    $oQuery = $GLOBALS['oDb']->query('SELECT * FROM `right` WHERE nIdUser = '.$GLOBALS['oUserLogin']->nId.' AND eType = '.$GLOBALS['oDb']->escape('institution').' AND nIdType = '.$GLOBALS['oDb']->escape($this->findRealIdForPermissionCheck($this->nIdType)));
                    break;
                case 'g':
                    $oQuery = $GLOBALS['oDb']->query('SELECT * FROM `right` WHERE nIdUser = '.$GLOBALS['oUserLogin']->nId.' AND (eType = '.$GLOBALS['oDb']->escape('group').' AND nIdType = '.$GLOBALS['oDb']->escape($this->findRealIdForPermissionCheck($this->nIdType)).') OR (eType = '.$GLOBALS['oDb']->escape('institution').' AND nIdType = (SELECT nIdInstitution FROM `group` WHERE nId = '.$GLOBALS['oDb']->escape($this->findRealIdForPermissionCheck($this->nIdType)).'))');
                    break;
                case 'm':
                    $oQuery = $GLOBALS['oDb']->query('SELECT * FROM `right` WHERE nIdUser = '.$GLOBALS['oUserLogin']->nId.' AND eType = '.$GLOBALS['oDb']->escape('module').' AND nIdType = '.$GLOBALS['oDb']->escape($this->findRealIdForPermissionCheck($this->nIdType)));
                    break;
                case 'c':
                    $oQuery = $GLOBALS['oDb']->query('SELECT * FROM `right` WHERE nIdUser = '.$GLOBALS['oUserLogin']->nId.' AND (eType = '.$GLOBALS['oDb']->escape('chapter').' AND nIdType = '.$GLOBALS['oDb']->escape($this->findRealIdForPermissionCheck($this->nIdType)).') OR (eType = '.$GLOBALS['oDb']->escape('module').' AND nIdType = (SELECT nIdModule FROM `chapter` WHERE nId = '.$GLOBALS['oDb']->escape($this->findRealIdForPermissionCheck($this->nIdType)).'))');
                    break;
            }
            if($oQuery !== NULL) {
                return $GLOBALS['oDb']->count($oQuery) > 0;
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
        if($this->nId === NULL && $this->changesAllowed() && $this->eType === NULL && $this->nIdType !== NULL) {
            if(strlen($this->nIdType) > 11) {
                switch($this->nIdType{0}) {
                    case 'i':
                        $this->eType = 'institution';
                        break;
                    case 'm':
                        $this->eType = 'module';
                        break;
                    case 'c':
                        $this->eType = 'chapter';
                        break;
                    case 'g':
                        $this->eType = 'group';
                        break;
                }
                
                $aRow = $GLOBALS['oDb']->getOneRow('SELECT nId FROM `'.$this->eType.'` WHERE sHashId = '.$GLOBALS['oDb']->escape($this->nIdType).' LIMIT 1');
                $this->nIdType = $aRow['nId'];
            }
        }
        return parent::create();
    }
    
    /**
     * Load list of all entries, no matter of the current one.
     * If _sHashId isset, list depending on hash is loaded: permissions from user, to institution, module, chapter, or group
     * 
     * @param string hash ID of target object
     * @return object oDb result object
     */
    public function getList($_sHashId = NULL) {
        $sWhere = '1 = 0';
        switch($_sHashId{0}) {
            case 'i':
                $sWhere = 'a.eType = '.$GLOBALS['oDb']->escape('institution').' AND a.nIdType IN (SELECT nId FROM `institution` WHERE NOT bDeleted AND sHashId = '.$GLOBALS['oDb']->escape($_sHashId).')';
                break;
            case 'g':
                $sWhere = 'a.eType = '.$GLOBALS['oDb']->escape('group').' AND a.nIdType IN (SELECT nId FROM `group` WHERE NOT bDeleted AND sHashId = '.$GLOBALS['oDb']->escape($_sHashId).')';
                break;
            case 'm':
                $sWhere = 'a.eType = '.$GLOBALS['oDb']->escape('module').' AND a.nIdType IN (SELECT nId FROM `module` WHERE NOT bDeleted AND sHashId = '.$GLOBALS['oDb']->escape($_sHashId).')';
                break;
            case 'c':
                $sWhere = 'a.eType = '.$GLOBALS['oDb']->escape('chapter').' AND a.nIdType IN (SELECT nId FROM `chapter` WHERE NOT bDeleted AND sHashId = '.$GLOBALS['oDb']->escape($_sHashId).')';
                break;
            case 'u':
                $sWhere = 'a.nIdUser IN (SELECT nId FROM `user` WHERE NOT bDeleted AND sHashId = '.$GLOBALS['oDb']->escape($_sHashId).')';
                break;
        }
        if(($oResult = $GLOBALS['oDb']->query('SELECT a.*, b.sName AS sIdCreator, u.sName AS sIdUser, i.sName AS sInstitution, g.sName AS sGroup, m.sName AS sModule, c.sName AS sChapter '.
                'FROM `right` a 
                    LEFT JOIN `user` b ON (b.nId = a.nIdCreator)
                    LEFT JOIN `user` u ON (u.nId = a.nIdUser)
                    LEFT JOIN `institution` i ON (i.nId = a.nIdType)
                    LEFT JOIN `group` g ON (g.nId = a.nIdType)
                    LEFT JOIN `module` m ON (m.nId = a.nIdType)
                    LEFT JOIN `chapter` c ON (c.nId = a.nIdType)
                WHERE '.$sWhere.' ORDER BY '.$this::DEFAULT_ORDER))) {

            $aData = array();
            while(($aRow = $GLOBALS['oDb']->get($oResult))) {
                $aRow['sIdType'] = $aRow['s'.ucfirst($aRow['eType'])];
                unset($aRow['sInstitution'], $aRow['sGroup'], $aRow['sModule'], $aRow['sChapter']);
                $aData[] = $aRow;
            }
            return $aData;
        }
        return NULL;
    }
    
    /**
     * Loads data based on this->nId.
     * 
     * @return boolean TRUE if successful, false (and error thrown) otherwise
     */
    public function load() {
        if($this->nId !== NULL) {
            try {
                $aData = $GLOBALS['oDb']->getOneRow('SELECT a.*, b.sName AS sIdCreator, u.sName AS sIdUser, i.sName AS sInstitution, g.sName AS sGroup, m.sName AS sModule, c.sName AS sChapter '.
                'FROM `right` a 
                    LEFT JOIN `user` b ON (b.nId = a.nIdCreator)
                    LEFT JOIN `user` u ON (u.nId = a.nIdUser)
                    LEFT JOIN `institution` i ON (i.nId = a.nIdType)
                    LEFT JOIN `group` g ON (g.nId = a.nIdType)
                    LEFT JOIN `module` m ON (m.nId = a.nIdType)
                    LEFT JOIN `chapter` c ON (c.nId = a.nIdType)
                WHERE a.nId = '.$GLOBALS['oDb']->escape($this->nId).' LIMIT 1');
                $aData['sIdType'] = $aData['s'.ucfirst($aData['eType'])];
                unset($aData['sInstitution'], $aData['sGroup'], $aData['sModule'], $aData['sChapter']);
                $this->setData($aData);
                return TRUE;
            } catch(\Exception $oException) {
                throw new \Exception(ucfirst($this::TABLE).' could not be loaded.', 0, $oException);
            }
        }
        return FALSE;
    }
    
    /**
     * Load possible foreign keys for a specific column. Necessary for frontend editing and the foreign-key select fields.
     * 
     * @param  string $_sColumn name of column for which to get the possible foreign keys
     * @return array  array with every "row" representing one possible foreign key as assoc. array with value and text
     */
    public function getPossibleForeignKeys($_sColumn) {
        return array();
    }
}

?>