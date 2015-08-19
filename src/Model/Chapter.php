<?php namespace Iiigel\Model;

class Chapter extends \Iiigel\Model\GenericModel {
    const TABLE = 'chapter';
    const DEFAULT_ORDER = 'nOrder ASC';
    
    
    /**
     * Return list of entries which should be shown in the dropdown.
     * 
     * @param string $_sHashId hashed ID
     * @return array array with up to four keys (chapter, group, institution, module) which itself hold arrays with nId and sName keys set
     */
    public static function getRightsTypeIdSelection($_sHashId) {
        return array(
            'chapter' => array(
                0 => $GLOBALS['oDb']->getOneRow('SELECT sHashId AS nId, sName FROM `chapter` WHERE NOT bDeleted AND sHashId = '.$GLOBALS['oDb']->escape($_sHashId))
            )
        );
    }
    
    /**
     * Load list of all entries, no matter of the current one.
     * If first parameter is set to a module ID, only this module's chapters are loaded.
     * 
     * @param integer $_nIdModule if set, only this module's chapters are loaded
     * @return object  oDb result object
     */
    public function getList($_nIdModule = NULL) {
        return $GLOBALS['oDb']->query('SELECT * FROM `chapter` WHERE bLive AND NOT bDeleted'.($_nIdModule === NULL ? '' : (' AND nIdModule = '.$GLOBALS['oDb']->escape($_nIdModule))).' ORDER BY '.$this::DEFAULT_ORDER);
    }
}

?>