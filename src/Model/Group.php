<?php namespace Iiigel\Model;

class Group extends \Iiigel\Model\GenericModel {
    const TABLE = 'group';
    const DEFAULT_ORDER = 'sName ASC';
    
    /**
     * Used by GenericModel in order to find out whether changes on the current entry are allowed for the currently logged-in user.
     * 
     * @return boolean true if allowed, false otherwise
     */
    protected function changesAllowed() {
        $bReturn = parent::changesAllowed();
        if(!$bReturn) {
            return $GLOBALS['oDb']->count($GLOBALS['oDb']->query('SELECT * FROM `right` WHERE nIdUser = '.$GLOBALS['oUserLogin']->nId.' AND eType = '.$GLOBALS['oDb']->escape('institution').' AND nIdType = '.$GLOBALS['oDb']->escape($this->nIdInstitution))) > 0;
        } else {
            return TRUE;
        }
    }
    
    /**
     * Loads user data based on this->nId.
     * 
     * @return boolean TRUE if successful, false (and error thrown) otherwise
     */
    public function load() {
        if($this->nId !== NULL) {
            try {
                $this->setData($GLOBALS['oDb']->getOneRow('SELECT a.*, b.sName AS sIdInstitution FROM `group` a, `institution` b WHERE a.nIdInstitution = b.nId AND NOT b.bDeleted AND NOT a.bDeleted AND a.nId = '.$GLOBALS['oDb']->escape($this->nId).' LIMIT 1'));
                return TRUE;
            } catch(\Exception $oException) {
                throw new \Exception(sprintf(_('error.objectload'), ucfirst($this::TABLE)), 0, $oException);
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
        return $GLOBALS['oDb']->query('SELECT a.*, b.sName AS sIdInstitution FROM `group` a, institution b WHERE a.nIdInstitution = b.nId AND NOT b.bDeleted AND NOT a.bDeleted AND a.nId IN ('.implode(', ', $GLOBALS['oUserLogin']->getGroups(TRUE, TRUE)).') ORDER BY a.'.$this::DEFAULT_ORDER);
    }
    
    /**
     * Load possible foreign keys for a specific column. Necessary for frontend editing and the foreign-key select fields.
     * 
     * @param  string $_sColumn name of column for which to get the possible foreign keys
     * @return array  array with every "row" representing one possible foreign key as assoc. array with value and text
     */
    public function getPossibleForeignKeys($_sColumn) {
        if($_sColumn == 'nIdInstitution') {
            $sTable = '';
            if(($oResult = $GLOBALS['oDb']->query('SELECT nId AS value, sName AS text FROM institution WHERE NOT bDeleted AND nId IN ('.implode(', ', $GLOBALS['oUserLogin']->getInstitutions(TRUE, TRUE)).') ORDER BY '.$this::DEFAULT_ORDER))) {
                $aReturn = array();
                while(($aRow = $GLOBALS['oDb']->get($oResult))) {
                    $aReturn[] = $aRow;
                }
                return $aReturn;
            }
            return array();
        } else {
            return parent::getPossibleForeignKeys($_sColumn);
        }
    }
    
    /**
     * Return list of entries which should be shown in the dropdown.
     * 
     * @param string $_sHashId hashed ID
     * @return array array with up to four keys (chapter, group, institution, module) which itself hold arrays with nId and sName keys set
     */
    public static function getRightsTypeIdSelection($_sHashId) {
        return array(
            'group' => array(
                0 => $GLOBALS['oDb']->getOneRow('SELECT sHashId AS nId, sName FROM `group` WHERE NOT bDeleted AND sHashId = '.$GLOBALS['oDb']->escape($_sHashId))
            )
        );
    }
}

?>