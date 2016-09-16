<?php namespace Iiigel\Controller;

class StaticPage extends \Iiigel\Controller\DefaultController {
    const DEFAULT_ACTION = 'loadFile';
    
    protected $oView = NULL;
    
    /**
     * Create a new static-page controller in order to show, well, a static HTML page.
     */
    public function __construct() {
        $this->oView = new \Iiigel\View\Page();
        
        if (isset($GLOBALS['oUserLogin'])) {
        	$oTmpHandin = new \Iiigel\Model\Handin();
        	
        	$aReviewHandins = array();
        	$aCheckedHandins = array();
        	
        	foreach ($GLOBALS['oUserLogin']->getGroups() as $oGroup) {
        		if ($this->hasGroupEditPermission($oGroup->sHashId)) {
        			$oResult = $oTmpHandin->getList($oGroup->sHashId);
        	
        			while(($GLOBALS['oDb']->count($oResult) > 0) && ($aRow = $GLOBALS['oDb']->get($oResult))) {
        				$oTemp = new \Iiigel\Model\Handin($aRow);
        	
        				$aReviewHandins[] = $oTemp->getCompleteEntry();
        			}
        		}
        	}
        	
        	$oResult = $oTmpHandin->getList($GLOBALS['oUserLogin']->sHashId);
        	
        	while(($GLOBALS['oDb']->count($oResult) > 0) && ($aRow = $GLOBALS['oDb']->get($oResult))) {
        		$oTemp = new \Iiigel\Model\Handin($aRow);
        	
        		$aCheckedHandins[] = $oTemp->getCompleteEntry();
        	}
        	
        	$this->oView->bHandinMessages = (count($aReviewHandins) > 0) || (count($aCheckedHandins) > 0);
        	$this->oView->aReviewHandins = $aReviewHandins;
        	$this->oView->aCheckedHandins = $aCheckedHandins;
        } else {
        	$this->oView->bHandinMessages = FALSE;
        }
    }
    
    /**
     * Default action. Loads a static HTML file from res/tmpl/ in order to be shown.
     * 
     * @param  string  [$_sFile                       = 'home'] file name, excl. "static/" path and excl. ".html" extension
     * @return boolean true on success, routed from View\Page
     */
    public function loadFile($_sFile = 'home') {
        reset($GLOBALS['aRequest']);
        foreach($GLOBALS['aRequest'] as $sKey => $mValue) {
            if(!in_array($sKey, array('c', 'a', 'path'))) {
                $this->oView->$sKey = $mValue;
            }
        }
        $this->oView->sPage = $_sFile;
        return $this->oView->loadTemplate('static/'.$_sFile.'.html');
    }
    
    /**
     * Output rendered static HTML page.
     * 
     * @return string HTML code
     */
    public function output() {
        return $this->oView->render();
    }
    
    /**
     * Takes _GET/_POST sUsername and sPassword in order to log in.
     * If successful, redirects to home page.
     * If not, redirect to error/password-forgotten page.
     */
    public function login() {
        if(isset($GLOBALS['aRequest']['sMail']) && isset($GLOBALS['aRequest']['sPassword'])) {
            $oUser = new \Iiigel\Model\User();
            $bLogin = $oUser->login($GLOBALS['aRequest']['sMail'], $GLOBALS['aRequest']['sPassword'], isset($GLOBALS['aRequest']['sHashId']) ? $GLOBALS['aRequest']['sHashId'] : NULL);
            
            if($bLogin === TRUE) {
                $this->redirect(isset($GLOBALS['aRequest']['sCurrentPageUrl']) ? $GLOBALS['aRequest']['sCurrentPageUrl'] : '');
            } elseif($bLogin === FALSE) {
                throw new \Exception(_('error.loginunsuccessful'));
            } else {
                $this->register();
            }
        } else {
            throw new \Exception(_('error.nodatagiven'));
        }
    }
    
    /**
     * Logs currently logged-in user out and redirects to home page. Returns false if nobody logged in.
     * 
     * @return mixed FALSE if nobody logged in
     */
    public function logout() {
        if(isset($GLOBALS['oUserLogin'])) {
            $GLOBALS['oUserLogin']->logout();
        }
        $this->redirect('');
    }
    
    /**
     * Registers a user based on entered email address and password. Other details entered are also (optionally) taken into account.
     * On success, activation page is shown (waiting for the activation code). On error, the register-error page is shown.
     */
    public function register() {
        if(isset($GLOBALS['aRequest']['sMail']) && isset($GLOBALS['aRequest']['sPassword']) && isset($GLOBALS['aRequest']['sHashId'])) {
            $oUser = new \Iiigel\Model\User();
            $oUser->setData($GLOBALS['aRequest']);
            try {
                if($oUser->register($GLOBALS['aRequest']['sHashId'])) {
                    $this->loadFile('activation');
                } else {
                    throw new \Exception(_('error.registrationunsuccessful'));
                }
            } catch(\Exception $oError) {
                throw new \Exception($oError->getMessage());
            }
        } else {
            throw new \Exception(_('error.nodatagiven'));
        }
    }
    
    /**
     * Remember dashbar navigation status (whether it's open or closed) for next reload of page.
     * 
     * @param boolean $_bHide if true, remember to keep it closed; if false, remember to keep it open (default)
     */
    public function saveDashboardNavStatus($_bHide) {
        if(isset($GLOBALS['oUserLogin'])) {
            $GLOBALS['oUserLogin']->bDashboardNavShown = $_bHide ? 0 : 1;
            $GLOBALS['oUserLogin']->update();
            die();
        }
    }
    
    /**
     * Activates a user that was registered before. Might either be called directly from mail link or "indirectly" through form submission.
     * If successful, user is also logged in directly; if unsuccessful, activation-wrong page is shown.
     * 
     * @param string $_sActivationCode activation code (hashed ID) of user to be activated
     */
    public function activate($_sActivationCode) {
        $nUser = \Iiigel\Model\User::activate($_sActivationCode);
        if($nUser === FALSE) {
            throw new \Exception(_('error.activationcodewrong'));
        } else {
            $oUser = new \Iiigel\Model\User($nUser);
            if($oUser->login()) {
                $this->redirect('');
            } else {
                throw new \Exception(_('error.loginunsuccessful'));
            }
        }
    }
    
    /**
     * Returns the permission of the current user to edit relations in a group.
     *
     * @param string $_sHashId hashed string represents the group
     * @return boolean TRUE if the user is allowed to edit the group, FALSE otherwise
     */
    protected function hasGroupEditPermission($_sHashId = NULL) {
    	if (($_sHashId == NULL) || (!isset($GLOBALS['oUserLogin']))) {
    		return false;
    	}
    	 
    	if (!$GLOBALS['oUserLogin']->bAdmin) {
    		$bGroupEdit = false;
    		 
    		$oSingle = new \Iiigel\Model\GroupAffiliation();
    		$oResult = $oSingle->getList($_sHashId, $oSingle::MODE_LEADER);
    		 
    		while(($GLOBALS['oDb']->count($oResult) > 0) && ($aRow = $GLOBALS['oDb']->get($oResult))) {
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
}

?>
