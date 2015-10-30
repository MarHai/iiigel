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
	        }
    	} else {
    		$oUser = new \Iiigel\Model\User($_sHashId);
    	}
    	
    	$this->loadFile('profile');
    }
}

?>