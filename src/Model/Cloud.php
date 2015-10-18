<?php namespace Iiigel\Model;

class Cloud {
    public $oUser = NULL;
    public $oRootFolder = NULL;
    
    /**
     * Setup new cloud based on user given or currently logged in user if not explicitely given.
     * 
     * @param mixed $_mIdUser user ID or NULL (in the latter case, take currently logged in user)
     */
    public function __construct($_mIdUser = NULL) {
        if($_mIdUser === NULL && isset($GLOBALS['oUserLogin'])) {
            $this->oUser = $GLOBALS['oUserLogin'];
        } else {
        //################ ACHTUNG: hier muss noch geprüft werden, ob der aktuell eingeloggte user das denn sehen darf
            $this->oUser = new \Iiigel\Model\User($_mIdUser);
        }
        
        $this->oRootFolder = new \Iiigel\Model\Folder(array(
        	'sName' => '',
        	'sType' => 'root',
        	'nTreeLeft' => 1,
    		'nTreeRight' => 2,
    		'bFileSystem' => 1
        ), $this);
    }
    
    /**
     * Creates a new file (without content at this point).
     * 
     * @param  string  $_sName         new file's name
     * @param  mixed   $_mFolderParent if folder object, then file is created in this folder; if null, file is created in user's root dir.
     * @return boolean TRUE if successfully created
     */
    public function createFile($_sName, $_mFolderParent = NULL) {
        $oFolderParent = ($_mFolderParent === NULL? $this->oRootFolder : $_mFolderParent);
    	
    	$oFile = new \Iiigel\Model\File(array(
    		'sName' => $_sName,
    		'sType' => "text/plain",
    		'nTreeLeft' => $oFolderParent->nTreeRight,
    		'nTreeRight' => $oFolderParent->nTreeRight + 1,
    		'nIdCreator' => $this->oUser->nId
    	), $this);
    	
    	return ($oFile->create() !== NULL);
    }
    
    /**
     * Checks whether a file is allowed to be opened by current user and returns \Iiigel\Model\File if appropriate.
     * Also sets file to be opened within database.
     * 
     * @param  string $_sHashId file's hashed ID (within cloud table)
     * @return object Iiigel/Model/File object
     */
    public function loadFile($_sHashId) {
        $oFile = new \Iiigel\Model\File($_sHashId, $this);
        $oFile->bOpen = TRUE;
        $oFile->update();
        return $oFile;
    }
    
    /**
     * Checks whether a file is allowed to be opened by current user and closes it if appropriate. Returns TRUE if successful (= allowed and database update was successful).
     * 
     * @param  string  $_sHashId file's hashed ID (within cloud table)
     * @return boolean  TRUE if successful
     */
    public function closeFile($_sHashId) {
        $oFile = new \Iiigel\Model\File($_sHashId, $this);
        $oFile->bOpen = FALSE;
        return $oFile->update();
    }
    
    /**
     * Creates a new folder.
     * 
     * @param  string  $_sName         new folder's name
     * @param  mixed   $_mFolderParent if folder object, then file is created in this folder; if null, file is created in user's root dir.
     * @return boolean TRUE if successfully created
     */
    public function createFolder($_sName, $_mFolderParent = NULL) {
    	$oFolderParent = ($_mFolderParent === NULL? $this->oRootFolder : $_mFolderParent);
    	
    	$oFolder = new \Iiigel\Model\Folder(array(
    		'sName' => $_sName,
    		'sType' => "folder",
    		'nTreeLeft' => $oFolderParent->nTreeRight,
    		'nTreeRight' => $oFolderParent->nTreeRight + 1,
    		'nIdCreator' => $this->oUser->nId
    	), $this);
    	
    	return ($oFolder->create() !== NULL);
    }
    
    /**
     * Get cloud structure incl. all sub files/folders.
     * Loads either the complete cloud (if no param given or param set to NULL) or starting from a specific folder.
     * Method returns "meta data" only, that is, no files' content is returned but only all other data.
     * 
     * @param  mixed [$_mFolderHashId         = NULL] if set and is valid dir, then returned structure starts from that point. if set to NULL, complete cloud (for current user) is returned.
     * @return array \Iiigel\Model\Folder and \Iiigel\Model\File objects returned (in adequate order) in an array
     */
    public function get($_mFolderHashId = NULL) {
    	if ($_mFolderHashId !== NULL) {
			$oFolder = new \Iiigel\Model\Folder($_mFolderHashId, $this);
			
			if ($oFolder->sType !== 'folder') {
				$oFolder = $this->oRootFolder;
			}
		} else {
			$oFolder = $this->oRootFolder;
		}
		
		$nTreeLeft = $GLOBALS['oDb']->escape($oFolder->nTreeLeft);
		$nTreeRight = $GLOBALS['oDb']->escape($oFolder->nTreeRight);
		$nIdCreator = $GLOBALS['oDb']->escape($this->oUser->nId);
		
		$oResult = $GLOBALS['oDb']->query('SELECT * FROM `cloud` WHERE nTreeLeft > '.$nTreeLeft.' AND nTreeRight < '.$nTreeRight.' AND nIdCreator = '.$nIdCreator.' AND NOT bDeleted ORDER BY nTreeLeft');
		
		$aSub = array();
		
		if ($oResult) {
			$nCount = $GLOBALS['oDb']->count($oResult);
			
			$nLastTreeLeft = $nTreeLeft;
			
			for ($i = 0; $i < $nCount; $i++) {
				$aRow = $GLOBALS['oDb']->get($oResult);
				
				if ($aRow['nTreeLeft'] > $nLastTreeLeft) {
					if ($aRow['sType'] === 'folder') {
						$aSub[] = new \Iiigel\Model\Folder($aRow, $this);
					} else {
						$aSub[] = new \Iiigel\Model\File($aRow, $this);
					}
					
					$nLastTreeLeft = $aRow['nTreeRight'];
				}
			}
		}
		
		return $aSub;
    }
    
    /**
     * List all folders in path of file
     * 
     * @param  string $_sHashId file's hashed ID (within cloud table)
     * @return array \Iiigel\Model\Folder objects returned (in adequate order) in an array
     */
    public function listPath($_mFolderHashId) {
    	$oFile = new \Iiigel\Model\File($_sHashId, $this);
    	
    	$nTreeLeft = $GLOBALS['oDb']->escape($oFile->nTreeLeft);
		$nTreeRight = $GLOBALS['oDb']->escape($oFile->nTreeRight);
		$nIdCreator = $GLOBALS['oDb']->escape($this->oUser->nId);
		
		$oResult = $GLOBALS['oDb']->query('SELECT * FROM `cloud` WHERE nTreeLeft < '.$nTreeLeft.' AND nTreeRight > '.$nTreeRight.' AND nIdCreator = '.$nIdCreator.' AND NOT bDeleted ORDER BY nTreeLeft');
		
		$aPath = array();
		
		if ($oResult) {
			$nCount = $GLOBALS['oDb']->count($oResult);
			
			for ($i = 0; $i < $nCount; $i++) {
				$aPath[] = new \Iiigel\Model\Folder($GLOBALS['oDb']->get($oResult), $this);
			}
		}
		
		return $aPath;
    }
    
    /**
     * Checks whether allowed and, if so, changes a file's/folder's name.
     * 
     * @param  string  $_sHashId  file or folder hash ID (within cloud table)
     * @param  string  $_sNewName new name to be given
     * @return boolean TRUE if successful, FALSE otherwise
     */
    public function rename($_sHashId, $_sNewName) {
    	$oFile = new \Iiigel\Model\File($_sHashId, $this);
    	$oParent = $oFile->oParent;
    	
    	if ($oParent !== NULL) {
    		$aSub = $this->get($oParent->sHashId);
    		
    		for ($i = 0; $i < count($aSub); $i++) {
    			if ($aSub[$i]->sName === $_sNewName) {
    				return false;
    			}
    		}
    	}
    	
        $oFile->sName = $_sNewName;
        return $oFile->update();
    }
}

?>
