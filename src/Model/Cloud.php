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
    		'nTreeRight' => 2
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
     * Creates a new file (with content which was uploaded).
     * 
     * @param  string  $_sName         new file's name
     * @param  mixed   $_mFolderParent if folder object, then file is created in this folder; if null, file is created in user's root dir.
     * @return boolean TRUE if successfully created
     */
    public function uploadFile($aFile, $_mFolderParent = NULL) {
    	$oFolderParent = ($_mFolderParent === NULL? $this->oRootFolder : $_mFolderParent);
    	
    	$oFile = new \Iiigel\Model\File(array(
    		'sName' => $aFile['originalName'],
    		'sType' => $aFile['type'],
    		'nTreeLeft' => $oFolderParent->nTreeRight,
    		'nTreeRight' => $oFolderParent->nTreeRight + 1,
    		'nIdCreator' => $this->oUser->nId,
    		'sFile' => $aFile['name'].';'.$aFile['url'].';'.$aFile['deleteUrl'],
    		'bFilesystem' => 1
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
        
        if ($oFile->nIdCreator == $this->oUser->nId) {
        	$oFile->bOpen = TRUE;
        	$oFile->update();
        	return $oFile;
        } else {
        	return NULL;
        }
    }
    
    /**
     * Checks whether a file is allowed to be opened by current user and closes it if appropriate. Returns TRUE if successful (= allowed and database update was successful).
     * 
     * @param  string  $_sHashId file's hashed ID (within cloud table)
     * @return boolean  TRUE if successful
     */
    public function closeFile($_sHashId) {
        $oFile = new \Iiigel\Model\File($_sHashId, $this);
        
        if ($oFile->nIdCreator == $this->oUser->nId) {
	        $oFile->bOpen = FALSE;
	        return $oFile->update();
        } else {
        	return false;
        }
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
		if ($_mFolderHashId === NULL) {
			return array($this->oRootFolder);
		}
		
    	$oFolder = new \Iiigel\Model\Folder($_mFolderHashId, $this);
		
		if (($oFolder->nIdCreator != $this->oUser->nId) || ($oFolder->sType !== 'folder')) {
			return array($this->oRootFolder);
		}
		
		$nTreeLeft = $GLOBALS['oDb']->escape($oFolder->nTreeLeft);
		$nTreeRight = $GLOBALS['oDb']->escape($oFolder->nTreeRight);
		$nIdCreator = $GLOBALS['oDb']->escape($this->oUser->nId);
		
		$oResult = $GLOBALS['oDb']->query('SELECT sHashId, nCreate, nUpdate, nTreeLeft, nTreeRight, sType, sName, bFilesystem, sFile FROM `cloud` WHERE nTreeLeft > '.$nTreeLeft.' AND nTreeRight < '.$nTreeRight.' AND nIdCreator = '.$nIdCreator.' AND NOT bDeleted ORDER BY nTreeLeft');
		
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

    private function completeState($oFile) {
    	$aData = $oFile->getCompleteEntry(TRUE);
    	
    	if ($aData['sType'] === 'folder') {
    		$aChildren = array();
    		
    		for ($i = 0; $i < count($aData['aChildren']); $i++) {
    			$aChildren[$i] = $this->completeState($aData['aChildren'][$i]);
    		}
    		
    		$aData['aChildren'] = $aChildren;
    	}
    	
    	return $aData;
    }

    /**
     * Creates a screenshot of this current cloud as json.
     * 
     * @return string Represents a current screenshot of this cloud as json
     */
    public function getCurrentState() {
    	$aFiles = $this->get();
    	$aData = array();
    	
    	for ($i = 0; $i < count($aFiles); $i++) {
    		$aData[$i] = $this->completeState($aFiles[$i]);
    	}
    	
    	return json_encode($aData);
    }
    
    /**
     * Get object which represents the file/folder at path ($_sFilename) if available
     * 
     * @param  string $_sFilename is path of file
     * @return \Iiigel\Model\File object if successful, NULL otherwise
     */
	public function getFile($_sPath) {
    	$nLastIndex = strrpos($_sPath, '/');
    	
    	$nIdCreator = $GLOBALS['oDb']->escape($this->oUser->nId);
    	$sName = $GLOBALS['oDb']->escape(substr($_sPath, $nLastIndex + 1));
    	
    	$oResult = $GLOBALS['oDb']->query('SELECT * FROM `cloud` WHERE nIdCreator = '.$nIdCreator.' AND sName = '.$sName.' AND NOT bDeleted');
    	
		if ($oResult) {
			$nCount = $GLOBALS['oDb']->count($oResult);
			
			for ($i = 0; $i < $nCount; $i++) {
				$oFile = new \Iiigel\Model\File($GLOBALS['oDb']->get($oResult), $this);
				$sPath = $oFile->pathString();
				
				$nC = strcmp($sPath, $_sPath);
				
				if ($nC == 0) {
					return $oFile;
				} else {
					print($sPath.'<br>'.$_sPath.'<br>'.$nC);
				}
			}
		}
		
		return NULL;
    }
    
    /**
     * List all folders in path of file
     * 
     * @param  string $_sHashId file's hashed ID (within cloud table)
     * @return array \Iiigel\Model\Folder objects returned (in adequate order) in an array
     */
    public function listPath($_sHashId) {
    	$oFile = new \Iiigel\Model\File($_sHashId, $this);
    	
    	if ($oFile->nIdCreator != $this->oUser->nId) {
    		return array();
    	}
    	
    	return $oFile->path();
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
    	
    	if ($oFile->nIdCreator == $this->oUser->nId) {
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
    	} else {
    		return false;
    	}
    }
    
    /**
     * Deletes all files/folders in cloud.
     * 
     * @return boolean true if successfully deleted, false otherwise
     */
    public function delete() {
    	return $this->oRootFolder->delete();
    }
    
}

?>
