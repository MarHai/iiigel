<?php namespace Iiigel\Model;

class Handin extends \Iiigel\Model\GenericModel {
    const TABLE = 'handin';
    const DEFAULT_ORDER = 'nIdGroup ASC';
    const CONFIG_COLUMN = array('nCreate', 'nIdCreator');

    protected function changesAllowed() {
    	if (parent::changesAllowed()) {
    		return true;
    	} elseif (isset($GLOBALS['oUserLogin'])) {
    		$nIdUser = $GLOBALS['oDb']->escape($GLOBALS['oUserLogin']->nId);
    		$nIdGroup = $GLOBALS['oDb']->escape($this->nIdGroup);
    		$nIdChapter = $GLOBALS['oDb']->escape($this->nIdChapter);
    		
    		$oResult = $GLOBALS['oDb']->query('SELECT nId FROM user2group WHERE NOT bDeleted AND nIdUser = '.$nIdUser.' AND nIdGroup = '.$nIdGroup.' AND (nIdChapter = '.$nIdChapter.' OR bAdmin) LIMIT 1;');
    		
    		return ($GLOBALS['oDb']->count($oResult) > 0);
    	} else {
    		return false;
    	}
    }
    
    /**
     * Load list of all entries, no matter of the current one.
     *
     * @param  string _sHashId hashed ID of group or user (!)
     * @return object oDb result object
     */
    public function getList($_sHashId = NULL) {
    	if ($_sHashId !== NULL) {
    		if ($_sHashId{0} == 'g') {
    			return $GLOBALS['oDb']->query('SELECT * FROM handin WHERE nIdGroup = (SELECT nId FROM `group` WHERE sHashId = '.$GLOBALS['oDb']->escape($_sHashId).') AND bCurrentlyUnderReview ORDER BY nCreate ASC');
    		} else
    		if ($_sHashId{0} == 'u') {
    			return $GLOBALS['oDb']->query('SELECT * FROM handin WHERE nIdCreator = (SELECT nId FROM `user` WHERE sHashId = '.$GLOBALS['oDb']->escape($_sHashId).') AND NOT bCurrentlyUnderReview ORDER BY nCreate ASC');
    		}
    	}
    	
    	return $GLOBALS['oDb']->query('SELECT * FROM handin ORDER BY nCreate ASC');
    }
    
    /**
     * Runs through all columns (via ->get()) and returns the loaded entry (so far).
     *
     * @param boolean $_bIncludeConfigColumns if true, really all columns are included
     * @return array   key/value pairing of all columns except CONFIG ones
     */
    public function getCompleteEntry($_bIncludeConfigColumns = FALSE) {
    	$aData = parent::getCompleteEntry($_bIncludeConfigColumns);
    	
    	$oUser = new \Iiigel\Model\User(intval($this->nIdCreator));
    	
    	$aData['sName'] = $oUser->sName;
    	$aData['sHash'] = md5(strtolower(trim($oUser->sMail)));
    	
    	$oChapter = new \Iiigel\Model\Chapter(intval($this->nIdChapter));
    	$oModule = new \Iiigel\Model\Module(intval($oChapter->nIdModule));
    	
    	$aData['sChapter'] = '[ '.$oModule->sName.' - '.$oChapter->sName.' ]';
    	$aData['sLearn'] = $oChapter->sHashId;
    
    	return $aData;
    }
}

?>