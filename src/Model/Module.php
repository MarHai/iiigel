<?php namespace Iiigel\Model;

class Module extends \Iiigel\Model\GenericModel {
    const TABLE = 'module';
    const DEFAULT_ORDER = 'sName ASC';
    
    /**
     * Return list of entries which should be shown in the dropdown.
     * 
     * @param string $_sHashId hashed ID
     * @return array array with up to four keys (chapter, group, institution, module) which itself hold arrays with nId and sName keys set
     */
    public static function getRightsTypeIdSelection($_sHashId) {
        return array(
            'module' => array(
                0 => $GLOBALS['oDb']->getOneRow('SELECT sHashId AS nId, sName FROM `module` WHERE NOT bDeleted AND sHashId = '.$GLOBALS['oDb']->escape($_sHashId))
            )
        );
    }
    
    /**
     * Access id (->nId), nProgress (->nProgress), or any other data (->NAME).
     * 
     * @param  string $_sName data param name
     * @return mixed  depending on parameter; NULL if not set
     */
    public function __get($_sName) {
        switch($_sName) {
            case 'nProgress':
            	if (!isset($GLOBALS['oUserLogin'])) {
            		return 0;
            	}
            	
            	$oUser = $GLOBALS['oUserLogin'];
            	
            	// CALCULATE PROGRESS OF USER
            	
            	return 25;
            case 'aChapter':
                $oChapter = new \Iiigel\Model\Chapter();
                $oChapter = $oChapter->getList($this->nId);
                $aReturn = array();
                while(($aRow = $GLOBALS['oDb']->get($oChapter))) {
                    $aReturn[] = new \Iiigel\Model\Chapter($aRow);
                }
                return $aReturn;
            case 'nCurrentChapter':
            	if (!isset($GLOBALS['oUserLogin'])) {
            		return 0;
            	}
            	
            	$oUser = $GLOBALS['oUserLogin'];
            	
            	// GET ACTIVE CHAPTER OF USER
            	
            	return 1;
            default:
                return parent::__get($_sName);
        }
    }
    
    /**
     * Check whether a parameter isset and available.
     * 
     * @param  string $_sName data param name
     * @return boolean  TRUE if set, FALSE otherwise
     */
    public function __isset($_sName) {
        return in_array($_sName, array('nProgress', 'aChapter', 'nCurrentChapter')) ? TRUE : parent::__isset($_sName);
    }
}

?>