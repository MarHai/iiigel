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
		
		while(($GLOBALS['oDb']->count($oResult) > 0) && ($aRow = $GLOBALS['oDb']->get($oResult))) {
            $oTemp = new \Iiigel\Model\User($aRow);
            
           	$aNot[] = $oTemp->getCompleteEntry();
        }
		
		$oResult = $oSingle->getList($_sHashId, $oSingle::MODE_MEMBER);
		
		while(($GLOBALS['oDb']->count($oResult) > 0) && ($aRow = $GLOBALS['oDb']->get($oResult))) {
			$nTempIdModule = intval($aRow['nIdModule']);
			$oTempModule = $nTempIdModule != 0? new \Iiigel\Model\Module($nTempIdModule) : NULL;
			
			unset($aRow['nIdModule']);
			
            $oTemp = new \Iiigel\Model\User($aRow);
            
            for ($i = count($aNot) - 1; $i >= 0; $i--) {
            	if ($aNot[$i]['sHashId'] === $oTemp->sHashId) {
            		array_splice($aNot, $i, 1);
            	}
            }
            
            $aEntry = $oTemp->getCompleteEntry();
            
            $aEntry['sHash'] = md5(strtolower(trim($oTemp->sMail)));
            $aEntry['bOnline'] = FALSE | $oTemp->isOnline();
            $aEntry['sHashIdU2G'] = $aRow['sHashIdU2G'];
            $aEntry['bModule'] = ($nTempIdModule != 0);
			$aEntry['nId']=$oTemp -> nId;
            
            if ($oTempModule !== NULL) {
	            $aEntry['sModuleHashId'] = $oTempModule->sHashId;
	            $aEntry['sModuleImage'] = $oTempModule->sImage;
	            $aEntry['nModuleProgress'] = $oTempModule->getProgress($oTemp->nId);
	            $aEntry['nCurrentChapterId'] = $oTempModule->getCurrentChapter($oTemp->nId);
            }
            
           	$aMembers[] = $aEntry;
        }
		
		$oResult = $oSingle->getList($_sHashId, $oSingle::MODE_LEADER);
		
		while(($GLOBALS['oDb']->count($oResult) > 0) && ($aRow = $GLOBALS['oDb']->get($oResult))) {
            $oTemp = new \Iiigel\Model\User($aRow);
            
            for ($i = count($aNot) - 1; $i >= 0; $i--) {
            	if ($aNot[$i]['sHashId'] === $oTemp->sHashId) {
            		array_splice($aNot, $i, 1);
            	}
            }
            
            $aEntry = $oTemp->getCompleteEntry();
            
            $aEntry['sHash'] = md5(strtolower(trim($oTemp->sMail)));
            $aEntry['bOnline'] = FALSE | $oTemp->isOnline();
            $aEntry['sHashIdU2G'] = $aRow['sHashIdU2G'];
            
           	$aLeaders[] = $aEntry;
        }
		
		$oResult = $oSingle->getList($_sHashId, $oSingle::MODE_MODULE);
		$oChapterHandle = new \Iiigel\Model\Chapter();
		
		while(($GLOBALS['oDb']->count($oResult) > 0) && ($aRow = $GLOBALS['oDb']->get($oResult))) {
            $oTemp = new \Iiigel\Model\Module($aRow);
            
            $aEntry = $oTemp->getCompleteEntry();
            $oResult0 = $oChapterHandle->getList($oTemp->nId);
            
            $aEntry['aChapters'] = array();
            
           	while (($GLOBALS['oDb']->count($oResult0) > 0) && ($aRow0 = $GLOBALS['oDb']->get($oResult0))) {
           		$aEntry['aChapters'][] = $aRow0;
           	}
            
			$aModules[] = $aEntry;
        }
        
        $oSingle = new \Iiigel\Model\Module();
        $oResult = $oSingle->getList();
        
        $aOtherModules = array();
        
        while(($GLOBALS['oDb']->count($oResult) > 0) && ($aRow = $GLOBALS['oDb']->get($oResult))) {
        	$oTemp = new \Iiigel\Model\Module($aRow);
        	$bFound = false;
        	
        	for ($i = count($aModules) - 1; $i >= 0; $i--) {
            	if ($aModules[$i]['sHashId'] === $oTemp->sHashId) {
            		$bFound = true;
            	}
            }
            
            if (!$bFound) {
            	$aEntry = $oTemp->getCompleteEntry();
            	
            	$aOtherModules[] = $aEntry;
            }
        }
		
		
		$oSingle = new \Iiigel\Model\Handin();
        $oResult = $oSingle->getList();
        
        while(($GLOBALS['oDb']->count($oResult) > 0) && ($aRow = $GLOBALS['oDb']->get($oResult))) {
        	$oTemp = new \Iiigel\Model\Handin($aRow);
			if ($oTemp->nIdGroup == $oGroup->nId ){
				$aEntry = $oTemp->getCompleteEntry();
				$aEntry['nIdCreator']=$oTemp->nIdCreator;
			}
            $aHandins[] = $aEntry;
        }
		
		$this->oView->aGroupLeaders = $aLeaders;
    	$this->oView->aGroupMembers = $aMembers;
		$this->oView->aGroupModules = $aModules;
		
		$this->oView->aNotInGroup = $aNot;
		$this->oView->aOtherModules = $aOtherModules;
    	
    	$this->oView->bGroupEdit = $this->hasGroupEditPermission($_sHashId);
		
		
		$this->oView->aHandins =  $aHandins;
    	
    	$this->loadFile('group');

		
		

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
    		
    		if ($_oModule !== NULL) {
    			$oTemp = new \Iiigel\Model\Chapter();
    			$oResult = $oTemp->getList($_oModule->nId);
    			
    			if ($oResult) {
    				$aRow = $GLOBALS['oDb']->get($oResult);
    				
    				if (isset($aRow['nId'])) {
    					$nIdChapter = $aRow['nId'];
    				}
    			}
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
    	if (($_sHashIdU2G != NULL) && (isset($GLOBALS['aRequest']['sHashIdModule'])) && ($this->hasGroupEditPermission($_sHashId))) {
    		$oGroup = new \Iiigel\Model\Group($_sHashId);
    		$oAffiliation = new \Iiigel\Model\GroupAffiliation($_sHashIdU2G);
    		
    		$oModule = new \Iiigel\Model\Module($GLOBALS['aRequest']['sHashIdModule']);
    		$oChapter = NULL;
    		
    		if (isset($GLOBALS['aRequest']['sHashIdChapter'])) {
    			$oChapter = new \Iiigel\Model\Chapter($GLOBALS['aRequest']['sHashIdChapter']);
    		} else {
    			$oTemp = new \Iiigel\Model\Chapter();
    			$mResult = $oTemp->getList($oModule->nId);
    			
    			if ($GLOBALS['oDb']->count($mResult) > 0) {
    				$oChapter =new \Iiigel\Model\Chapter($GLOBALS['oDb']->get($mResult));
    			}
    		}
    		
    		$oAffiliation->nIdModule = $oModule->nId;
    		$oAffiliation->nIdChapter = $oChapter !== NULL? $oChapter->nId : 0;
    		
    		$oAffiliation->update();
    	
    		$this->redirect(URL.'Group/'.$_sHashId);
    	} else {
    		throw new \Exception(_('error.permission'));
    	}
    }
    
    /**
     * Adds a module to a group
     *
     * @param string $_sHashId hashed string represents the group
     */
    public function addModule($_sHashId = NULL) {
    	if (($_sHashId != NULL) && (isset($GLOBALS['aRequest']['sHashIdModule'])) && ($this->hasGroupEditPermission($_sHashId))) {
    		$oGroup = new \Iiigel\Model\Group($_sHashId);
    		$oModule = new \Iiigel\Model\Module($GLOBALS['aRequest']['sHashIdModule']);
    		
    		$nIdGroup = $GLOBALS['oDb']->escape($oGroup->nId);
    		$nIdModule = $GLOBALS['oDb']->escape($oModule->nId);
    		
    		$nUpdate = $GLOBALS['oDb']->escape(standardized_time());
    		$nIdUpdater = $GLOBALS['oDb']->escape($GLOBALS['oUserLogin']->nId);
    		
    		$oResult = $GLOBALS['oDb']->query('SELECT `nId` FROM `module2group` WHERE `nIdGroup` = '.$nIdGroup.' AND `nIdModule` = '.$nIdModule.' LIMIT 1');
    		
    		if ($GLOBALS['oDb']->count($oResult) > 0) {
    			$nIdEntry = $GLOBALS['oDb']->get($oResult)['nId'];
    			
    			$GLOBALS['oDb']->query('UPDATE `module2group` SET `bDeleted` = 0, `nUpdate` = '.$nUpdate.', `nIdUpdater` = '.$nIdUpdater.' WHERE `nId` = '.$nIdEntry);
    		} else {
    			$GLOBALS['oDb']->query('INSERT INTO `module2group` (`bDeleted`, `nCreate`, `nUpdate`, `nIdCreator`, `nIdUpdater`, `nIdModule`, `nIdGroup`) VALUES (0,'.$nUpdate.',0,'.$nIdUpdater.',0,'.$nIdModule.','.$nIdGroup.')');
    		}
    		
    		$this->redirect(URL.'Group/'.$_sHashId);
    	} else {
    		throw new \Exception(_('error.permission'));
    	}
    }

	/**
     * Removes a module from a group
     *
     * @param string $_sHashId hashed string represents the group
     */
	public function removeModule($_sHashId = NULL) {
		if (($_sHashId != NULL) && (isset($GLOBALS['aRequest']['sHashIdModule'])) && ($this->hasGroupEditPermission($_sHashId))) {
    		$oGroup = new \Iiigel\Model\Group($_sHashId);
    		$oModule = new \Iiigel\Model\Module($GLOBALS['aRequest']['sHashIdModule']);
    		
    		$nIdGroup = $GLOBALS['oDb']->escape($oGroup->nId);
    		$nIdModule = $GLOBALS['oDb']->escape($oModule->nId);
    		
    		$nUpdate = $GLOBALS['oDb']->escape(standardized_time());
    		$nIdUpdater = $GLOBALS['oDb']->escape($GLOBALS['oUserLogin']->nId);
    		
    		$GLOBALS['oDb']->query('UPDATE `module2group` SET `bDeleted` = 1, `nUpdate` = '.$nUpdate.', `nIdUpdater` = '.$nIdUpdater.' WHERE `nIdGroup` = '.$nIdGroup.' AND `nIdModule` = '.$nIdModule);
    		
    		$this->redirect(URL.'Group/'.$_sHashId);
    	} else {
    		throw new \Exception(_('error.permission'));
    	}
	}

}

?>
