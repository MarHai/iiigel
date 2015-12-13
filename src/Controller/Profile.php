<?php namespace Iiigel\Controller;

class Profile extends \Iiigel\Controller\StaticPage {
	const DEFAULT_ACTION = 'show';
    
    /**
     * Display the profile of a user.
     * 
     * @param string $_sHashId hashed string represents the user
     */
    public function show($_sHashId = NULL) {
    	if ($_sHashId == NULL) {
	    	if(!isset($GLOBALS['oUserLogin'])) {
	            throw new \Exception(_('error.permission'));
	        } else {
	        	$oUser = $GLOBALS['oUserLogin'];
	        	$this->oView->bProfileEdit = TRUE;
	        }
    	} else {
    		$oUser = new \Iiigel\Model\User($_sHashId);
    		$this->oView->bProfileEdit = isset($GLOBALS['oUserLogin'])? ($GLOBALS['oUserLogin']->nId == $oUser->nId) : FALSE;
    	}
    	
    	$this->oView->sProfileHash = md5(strtolower(trim($oUser->sMail)));
    	$this->oView->aProfileUser = array(
    		'sMail' => $oUser->sMail,
    		'sName' => $oUser->sName,
    		'sHashId' => $oUser->sHashId,
    		'sPassword' => '********'
    	);
    	
    	$aIdModules = $oUser->getModules(FALSE, TRUE);
    	$aModules = array();
    	
    	for ($i = 0; $i < count($aIdModules); $i++) {
    		$oTemp = new \Iiigel\Model\Module(intval($aIdModules[$i]));
    		
    		$aModules[] = array(
    			"sHashId" => $oTemp->sHashId,
    			"sName" => $oTemp->sName,
    			"sImage" => $oTemp->sImage,
    			"nProgress" => $oTemp->getProgress($oUser->nId)
    		);
    	}
    	
    	$this->oView->aProfileModule = $aModules;
    	
    	$aGroup = array();
    	
    	foreach ($oUser->getGroups() as $oGroup) {
    		$oGroup->load();
    		
    		$aGroup[] = $oGroup->getCompleteEntry();
    	}
    	
    	$this->oView->aProfileGroup = $aGroup;
    	
    	$this->loadFile('profile');
    }

    /**
     * Edit changes at profile of a user.
     * 
     * @param string $_sHashId hashed string represents the user
     */
    public function edit($_sHashId = NULL) {
    	if(!isset($GLOBALS['oUserLogin'])) {
			throw new \Exception(_('error.permission'));
	  	} else {
			$oEditor = $GLOBALS['oUserLogin'];
	  	}
	  	
    	if ($_sHashId === NULL) {
	    	$oUser = $GLOBALS['oUserLogin'];
    	} else {
    		$oUser = new \Iiigel\Model\User($_sHashId);
    	}
	  	
		if (($oEditor->nId == $oUser->nId) && ($oEditor->bAdmin)) {
			if (isset($GLOBALS['aRequest']['sName'])) {
				$oUser->sName = $GLOBALS['aRequest']['sName'];
			}
			
			if (isset($GLOBALS['aRequest']['sMail'])) {
				$oUser->sMail = $GLOBALS['aRequest']['sMail'];
			}
			
			if (isset($GLOBALS['aRequest']['sPassword'])) {
				$oUser->sPassword = $GLOBALS['aRequest']['sPassword'];
			}
			
			$oUser->update();
		} else {
			throw new \Exception(_('error.permission'));
		}
		
		$this->redirect(URL.'Profile/'.($_sHashId !== NULL? $_sHashId : ''));
    }

}

?>
