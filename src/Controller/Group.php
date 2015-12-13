<?php namespace Iiigel\Controller;

class Group extends \Iiigel\Controller\StaticPage {
	const DEFAULT_ACTION = 'show';
    
    /**
     * Display a group.
     * 
     * @param string $_sHashId hashed string represents the group
     */
    public function show($_sHashId = NULL) {
    	if (($_sHashId == NULL) || (!isset($GLOBALS['oUserLogin']))) {
	    	throw new \Exception(_('error.permission'));
    	} else {
    		$oGroup = new \Iiigel\Model\Group($_sHashId);
			$oGroup->load();
    	}
    	
    	$this->oView->aGroup = $oGroup->getCompleteEntry();
    	
		$oSingle = new \Iiigel\Model\GroupAffiliation();
		
		$aNot = array();
		
		$aLeaders = array();
		$aMembers = array();
		$aModules = array();
		
		$oResult = $oSingle->getList($_sHashId, $oSingle::MODE_POSSIBLE);
		
		while(($aRow = $GLOBALS['oDb']->get($oResult))) {
            $oTemp = new \Iiigel\Model\User($aRow);
            
           	$aNot[] = $oTemp->getCompleteEntry();
        }
		
		$oResult = $oSingle->getList($_sHashId, $oSingle::MODE_MEMBER);
		
		while(($aRow = $GLOBALS['oDb']->get($oResult))) {
			$oTempModule = new \Iiigel\Model\Module(intval($aRow['nIdModule']));
			
			unset($aRow['nIdModule']);
			
            $oTemp = new \Iiigel\Model\User($aRow);
            
            for ($i = count($aNot) - 1; $i >= 0; $i--) {
            	if ($aNot[$i]['sHashId'] === $oTemp->sHashId) {
            		array_splice($aNot, $i, 1);
            	}
            }
            
            $aEntry = $oTemp->getCompleteEntry();
            
            $aEntry['sModuleHashId'] = $oTempModule->sHashId;
            $aEntry['sModuleImage'] = $oTempModule->sImage;
            $aEntry['nModuleProgress'] = $oTempModule->getProgress($oTemp->nId);
            $aEntry['nCurrentChapterId'] = $oTempModule->getCurrentChapter($oTemp->nId);
            
           	$aMembers[] = $aEntry;
        }
		
		$oResult = $oSingle->getList($_sHashId, $oSingle::MODE_LEADER);
		
		while(($aRow = $GLOBALS['oDb']->get($oResult))) {
            $oTemp = new \Iiigel\Model\User($aRow);
            
            for ($i = count($aNot) - 1; $i >= 0; $i--) {
            	if ($aNot[$i]['sHashId'] === $oTemp->sHashId) {
            		array_splice($aNot, $i, 1);
            	}
            }
            
            $aEntry = $oTemp->getCompleteEntry();
            
            $aEntry['sHashIdU2G'] = $aRow['sHashIdU2G'];
            
           	$aLeaders[] = $aEntry;
        }
		
		$oResult = $oSingle->getList($_sHashId, $oSingle::MODE_MODULE);
		$oChapterHandle = new \Iiigel\Model\Chapter();
		
		while(($aRow = $GLOBALS['oDb']->get($oResult))) {
            $oTemp = new \Iiigel\Model\Module($aRow);
            
            $aEntry = $oTemp->getCompleteEntry();
            $oResult0 = $oChapterHandle->getList($oTemp->nId);
            
            $aEntry['sHashIdU2G'] = $aRow['sHashIdU2G'];
            $aEntry['aChapters'] = array();
            
           	while (($aRow0 = $GLOBALS['oDb']->get($oResult0))) {
           		$aEntry['aChapters'][] = $aRow0;
           	}
            
			$aModules[] = $aEntry;
        }
		
		$this->oView->aGroupLeaders = $aLeaders;
    	$this->oView->aGroupMembers = $aMembers;
		$this->oView->aGroupModules = $aModules;
		
		$this->oView->aNotInGroup = $aNot;
    	
    	$this->oView->bGroupEdit = $this->hasGroupEditPermission($_sHashId);
    	
    	$this->loadFile('group');
    }
    
    /**
     * Returns the permission of the current user to edit relations in a group.
     *
     * @param string $_sHashId hashed string represents the group
     * @return boolean TRUE if the user is allowed to edit the group, FALSE otherwise
     */
    private function hasGroupEditPermission($_sHashId = NULL) {
    	if (($_sHashId == NULL) || (!isset($GLOBALS['oUserLogin']))) {
    		return false;
    	}
    	
    	if (!$GLOBALS['oUserLogin']->bAdmin) {
    		$bGroupEdit = false;
    	
    		$oSingle = new \Iiigel\Model\GroupAffiliation();
    		$oResult = $oSingle->getList($_sHashId, $oSingle::MODE_LEADER);
    	
    		while(($aRow = $GLOBALS['oDb']->get($oResult))) {
    			$oTemp = new \Iiigel\Model\User($aRow);
    				
    			if ($oTemp->nId == $GLOBALS['oUserLogin']->nId) {
    				$bGroupEdit = true;
    			}
    		}
    	
    		return $bGroupEdit;
    	} else {
    		return true;
    	}
    }
    
    /**
     * Add a user to a group.
     *
     * @param object $_oGroup is the group
     * @param object $_oUser is the user
     * @param boolean $_bAdmin sets if the user shall be an admin in the group
     * @param object $_oModule is the module, the user is set in ( needed if he won't be an admin )
     */
    private function add($_oGroup, $_oUser, $_bAdmin, $_oModule = NULL) {
    	if ($this->hasGroupEditPermission($_oGroup->sHashId)) {
    		$nIdChapter = 0;
    		
    		if ($_oModule != NULL) {
    			$oTemp = new \Iiigel\Model\Chapter();
    			$aRow = $GLOBALS['oDb']->get($oTemp->getList($_oModule->nId));
    			$nIdChapter = $aRow['nId'];
    		}
    		
    		$oSingle = new \Iiigel\Model\GroupAffiliation(array(
    			"nIdGroup" => $_oGroup->nId,
    			"nIdUser" => $_oUser->nId,
    			"nIdModule" => ($_oModule != NULL? $_oModule->nId : 0),
    			"nIdChapter" => $nIdChapter,
    			"bAdmin" => $_bAdmin
    		));
    		
    		$oSingle->create();
    		 
    		$this->redirect(URL.'Group/'.$_oGroup->sHashId);
    	} else {
    		throw new \Exception(_('error.permission'));
    	}
    }
    
    /**
     * Removes a user from a group
     *
     * @param string $_sHashId hashed string represents the group
     * @param string $_sHashIdU2G hashed string represents the relation of the user to the group
     */
    public function remove($_sHashId = NULL, $_sHashIdU2G = NULL) {
    	if (($_sHashIdU2G != NULL) && ($this->hasGroupEditPermission($_sHashId))) {
    		$oGroup = new \Iiigel\Model\Group($_sHashId);
    		$oAffiliation = new \Iiigel\Model\GroupAffiliation($_sHashIdU2G);
    		
    		$oAffiliation->delete();
    		
    		$this->redirect(URL.'Group/'.$_sHashId);
    	} else {
    		throw new \Exception(_('error.permission'));
    	}
    }
    
    /**
     * Adds a user as admin in a group
     *
     * @param string $_sHashId hashed string represents the group
     */
    public function addAdmin($_sHashId = NULL) {
    	if (($_sHashId != NULL) && (isset($GLOBALS['aRequest']['sHashIdUser']))) {
    		return $this->add(new \Iiigel\Model\Group($_sHashId), new \Iiigel\Model\User($GLOBALS['aRequest']['sHashIdUser']), True);
    	} else {
    		throw new \Exception(_('error.permission'));
    	}
    }

    /**
     * Adds a user as general member in a group
     *
     * @param string $_sHashId hashed string represents the group
     */
    public function addUser($_sHashId = NULL) {
    if (($_sHashId != NULL) && (isset($GLOBALS['aRequest']['sHashIdUser'])) && (isset($GLOBALS['aRequest']['sHashIdModule']))) {
    		return $this->add(new \Iiigel\Model\Group($_sHashId), new \Iiigel\Model\User($GLOBALS['aRequest']['sHashIdUser']), False, new \Iiigel\Model\Module($GLOBALS['aRequest']['sHashIdModule']));
    	} else {
    		throw new \Exception(_('error.permission'));
    	}
    }

    /**
     * Edits the relation of a user ( member ) in a group
     *
     * @param string $_sHashId hashed string represents the group
     * @param string $_sHashIdU2G hashed string represents the relation
     */
    public function editUser($_sHashId = NULL, $_sHashIdU2G = NULL) {
    	if (($_sHashIdU2G != NULL) && (isset($GLOBALS['aRequest']['sHashIdChapter'])) && (isset($GLOBALS['aRequest']['sHashIdModule'])) && ($this->hasGroupEditPermission($_sHashId))) {
    		$oGroup = new \Iiigel\Model\Group($_sHashId);
    		$oAffiliation = new \Iiigel\Model\GroupAffiliation($_sHashIdU2G);
    		$oChapter = new \Iiigel\Model\Chapter($GLOBALS['aRequest']['sHashIdChapter']);
    		$oModule = new \Iiigel\Model\Module($GLOBALS['aRequest']['sHashIdModule']);
    		
    		$oAffiliation->nIdModule = $oModule->nId;
    		$oAffiliation->nIdChapter = $oChapter->nId;
    		
    		$oAffiliation->update();
    	
    		$this->redirect(URL.'Group/'.$_sHashId);
    	} else {
    		throw new \Exception(_('error.permission'));
    	}
    }

}

?>
