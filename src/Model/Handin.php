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
    		
    		$oResult = $GLOBALS['oDb']->query('SELECT nId FROM user2group WHERE NOT bDeleted AND nIdUser = '.$nIdUser.' AND nIdGroup = '.$nIdGroup.' AND nIdChapter = '.$nIdChapter.' LIMIT 1;');
    		
    		return ($GLOBALS['oDb']->count($oResult) > 0);
    	} else {
    		return false;
    	}
    }
}

?>