<?php namespace Iiigel\Model;

class Institution extends \Iiigel\Model\GenericModel {
    const TABLE = 'institution';
    const DEFAULT_ORDER = 'sName ASC';
    
    /**
     * Load list of all entries, no matter of the current one.
     * 
     * @return object oDb result object
     */
    public function getList() {
        return $GLOBALS['oDb']->query('SELECT * FROM institution WHERE NOT bDeleted AND nId IN ('.implode(', ', $GLOBALS['oUserLogin']->getInstitutions(TRUE, TRUE)).') ORDER BY '.$this::DEFAULT_ORDER);
    }
    
    /**
     * Return list of entries which should be shown in the dropdown.
     * 
     * @param string $_sHashId hashed ID
     * @return array array with up to four keys (chapter, group, institution, module) which itself hold arrays with nId and sName keys set
     */
    public static function getRightsTypeIdSelection($_sHashId) {
        return array(
            'institution' => array(
                0 => $GLOBALS['oDb']->getOneRow('SELECT sHashId AS nId, sName FROM `institution` WHERE NOT bDeleted AND sHashId = '.$GLOBALS['oDb']->escape($_sHashId))
            )
        );
    }
}

?>