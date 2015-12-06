<?php namespace Iiigel\Controller;

class Group extends \Iiigel\Controller\StaticPage {
	const DEFAULT_ACTION = 'show';
    
    /**
     * Display the profile of a user.
     * 
     * @param string $_sHashId hashed string represents the user
     */
    public function show($_sHashId = NULL) {
    	if ($_sHashId == NULL) {
	    	throw new \Exception(_('error.permission'));
    	} else {
    		$oGroup = new \Iiigel\Model\Group($_sHashId);
			$oGroup->load();
    	}
    	
    	$this->oView->aGroup = $oGroup->getCompleteEntry();
    	
		$oSingle = new \Iiigel\Model\GroupAffiliation();
		
		$aLeaders = array();
		$aMembers = array();
		$aModules = array();
		
		$oResult = $oSingle->getList($_sHashId, $oSingle::MODE_MEMBER);
		
		while(($aRow = $GLOBALS['oDb']->get($oResult))) {
            $oTemp = new \Iiigel\Model\User($aRow);
			$aEntry = $oTemp->getCompleteEntry();
			$aEntry["sHash"] = md5(strtolower(trim($oTemp->sMail)));
           	$aMembers[] = $aEntry;
        }
		
		$oResult = $oSingle->getList($_sHashId, $oSingle::MODE_LEADER);
		
		while(($aRow = $GLOBALS['oDb']->get($oResult))) {
            $oTemp = new \Iiigel\Model\User($aRow);
           	$aEntry = $oTemp->getCompleteEntry();
			$aEntry["sHash"] = md5(strtolower(trim($oTemp->sMail)));
           	$aLeaders[] = $aEntry;
        }
		
		$oResult = $oSingle->getList($_sHashId, $oSingle::MODE_MODULE);
		
		while(($aRow = $GLOBALS['oDb']->get($oResult))) {
            $oTemp = new \Iiigel\Model\Module($aRow);
			$aModules[] = $oTemp->getCompleteEntry();
        }
		
		$this->oView->aGroupLeaders = $aLeaders;
    	$this->oView->aGroupMembers = $aMembers;
		$this->oView->aGroupModules = $aModules;
		
		$this->oView->bGroupEdit = false;
    	
    	$this->loadFile('group');
    }

}

?>
