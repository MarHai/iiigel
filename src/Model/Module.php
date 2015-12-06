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
            	$nIdChapter = $this->nCurrentChapter;
            	
            	if ($nIdChapter != 0) {
	            	$oChapter = new \Iiigel\Model\Chapter($nIdChapter);
	            	$nCurrent = intval($oChapter->nOrder);
	            	
	            	$aRow = $GLOBALS['oDb']->getOneRow('SELECT MAX(nOrder) AS nMax, MIN(nOrder) AS nMin FROM chapter WHERE nIdModule = '.$oChapter->nIdModule);
	            	
	            	if ($aRow) {
	            		$nMax = intval($aRow['nMax']);
	            		$nMin = intval($aRow['nMin']);
	            		
	            		return round(100 * ($nCurrent - $nMin) / $nMax);
	            	}
            	}
            	
            	return 0;
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
            	
            	$nIdUser = $GLOBALS['oDb']->escape($oUser->nId);
            	$nId = $GLOBALS['oDb']->escape($this->nId);
            	
            	$aRow = $GLOBALS['oDb']->getOneRow('SELECT user2group.nIdChapter AS nCurrentChapter FROM user2group, chapter WHERE NOT user2group.bDeleted AND user2group.nIdUser = '.$nIdUser.' AND user2group.nIdModule = '.$nId.' AND user2group.nIdChapter = chapter.nId ORDER BY chapter.nOrder DESC LIMIT 1;');
            	
            	if ($aRow) {
            		return intval($aRow['nCurrentChapter']);
            	} else {
            		return 0;
            	}
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
    
    /**
     * Deletes current row.
     * 
     * @return boolean true if successfully deleted, false otherwise
     */
    public function delete() {
    	if (parent::delete()) {
    		foreach ($this->aChapter as $oChapter) {
    			$oChapter->delete();
    		}
    		
    		return TRUE;
    	} else {
    		return FALSE;
    	}
    }
    
}

?>
