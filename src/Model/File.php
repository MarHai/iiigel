<?php namespace Iiigel\Model;

use Iiigel\Generic\Upload;

class File extends \Iiigel\Model\GenericModel {
    const TABLE = 'cloud';
    const DEFAULT_ORDER = 'sName ASC';
    const CONFIG_COLUMN = array('bDeleted', 'nCreate', 'nUpdate', 'nIdCreator');
    
    protected $oCloud = NULL;
    protected $oParentFolder = NULL;
    
    /**
     * Initiates new object which could be one of the following cases:
     * (1) set up object based on ID (integer)
     * (2) set up object based on row data including an nId
     * (3) set up new object (row) data without an ID (for login or registration)
     * (4) set up object based on hash ID (string)
     * (5) for the moment, do nothing
     * In addition, the surround cloud (via ->setSurroundingCloud(...) can be set directly.
     * 
     * @param mixed [$_mInit         = NULL] integer if case 1, array with nId field if case 2, array without nId if case 3, NULL otherwise (4)
     * @param mixed [$_oCloud        = NULL] if set, surrounding cloud is set directly
     */
    public function __construct($_mInit = NULL, $_oCloud = NULL) {
        $bNeedCreation = false;
        
        if($_oCloud !== NULL) {
            $this->setSurroundingCloud($_oCloud);
            
            if ((is_array($_mInit)) && ($_mInit['sType'] === 'root')) {
		        $nIdCreator = $GLOBALS['oDb']->escape($this->oCloud->oUser->nId);
		    	
		    	$oResult = $GLOBALS['oDb']->query('SELECT * FROM `'.$this::TABLE.'` WHERE nIdCreator='.$nIdCreator.' AND nTreeLeft=1 AND NOT bDeleted LIMIT 1');
		    	
		    	if ($GLOBALS['oDb']->count($oResult) > 0) {
		    		$aRow = $GLOBALS['oDb']->get($oResult);
		    		
		    		if (isset($aRow['nId'])) {
		    			parent::__construct($aRow);
		    			return;
		    		}
		    	} else {
		    		$bNeedCreation = true;
		    	}
		    }
        }
        
        parent::__construct($_mInit);
        
        if ($bNeedCreation) {
        	$this->create();
        }
    }
    
    /**
     * Sets the surrounding cloud.
     * 
     * @param object $_oCloud \Iiigel\Model\Cloud object
     */
    public function setSurroundingCloud($_oCloud) {
        $this->oCloud = $_oCloud;
    }
    
    /**
     * No loading of list available here --> refers to Iiigel\Model\Cloud
     */
    public function getList() {
        throw new \Exception(_('error.usecloudforfilelist'));
    }
    
    /**
     * Overwrite in order to not return file content.
     * 
     * @return array same as ->getCompleteEntry(TRUE)
     */
    public function jsonSerialize() {
        $aFile = $this->getCompleteEntry(TRUE);
        unset($aFile['sFile']);
        return $aFile;
    }
    
    /**
     * Overwrite magic get in order to serve with size.
     * 
     * @param  string $_sName data param name
     * @return mixed  depending on parameter; NULL if not set
     */
    public function __get($_sName) {
    	if ($_sName === 'oParent') {
    		if($this->oParentFolder === NULL) {
    			$aPath = $this->path();
    			$nCount = count($aPath);
    			
    			if ($nCount > 0) {
    				$this->oParentFolder = $aPath[$nCount - 1];
    			}
    		}
    		
    		return $this->oParentFolder;
    	} else
        if($_sName === 'sSize') {
        	if ($this->bFilesystem) {
        		$aFile = explode(';', $this->sFile);
        		$nSize = filesize($GLOBALS['aConfig']['sUploadDir'].$aFile[0]);
        	} else {
        		$nSize = mb_strlen($this->sFile, 'utf8');
        	}
            
            $aPrefix = array('', 'K', 'M', 'G', 'T', 'P');
            $i = 0;
            while($nSize/1024 > 1 && isset($aPrefix[$i+1])) {
                $nSize /= 1024;
                $i++;
            }
            return $nSize.' '.$aPrefix[$i].'Byte';
        } else {
            return parent::__get($_sName);
        }
    }
    
	/**
     * Set one specific parameter.
     * 
     * @param  string  $_sName  parameter name (be careful as it needs to match a DB column but not one of CONFIG_COLUMN or nId
     * @param  mixed   $_mValue value to insert
     * @return boolean true if value was set, false otherwise
     */
    public function __set($_sName, $_mValue) {
    	if (($_sName == 'sFile') && ($this->bFilesystem)) {
    		$aFile = explode(';', $this->sFile);
    		$sFilename = $GLOBALS['aConfig']['sUploadDir'].$aFile[0];
    		
    		if (!file_put_contents($sFilename, $_mValue)) {
    			return false;
    		} else {
    			return true;
    		}
    	} else {
    		return parent::__set($_sName, $_mValue);
    	}
    }
    
    /**
     * Runs through all columns (via ->get()) and returns the loaded entry (so far).
     * 
     * @param boolean $_bIncludeConfigColumns if true, really all columns are included
     * @return array   key/value pairing of all columns except CONFIG ones
     */
    public function getCompleteEntry($_bIncludeConfigColumns = FALSE) {
        $aData = parent::getCompleteEntry($_bIncludeConfigColumns);
        $aData['sSize'] = $this->sSize;
        
        if ($this->bFilesystem) {
        	$aFile = explode(';', $aData['sFile']);
        	$sFilename = $GLOBALS['aConfig']['sUploadDir'].$aFile[0];
        	$sFileUrl = $aFile[1];
        	
        	if ((strpos($aData['sType'], 'text') === 0) && (file_exists($sFilename))) {
        		$aData['sFile'] = file_get_contents($sFilename);
        	} else {
        		$aData['sFile'] = $sFileUrl;
        	}
        }
        
        return $aData;
    }
    
    /**
     * Used by GenericModel in order to find out whether changes on the current entry are allowed for the currently logged-in user.
     * 
     * @return boolean true if allowed, false otherwise
     */
    protected function changesAllowed() {
    	if ((isset($GLOBALS['oUserLogin'])) && ((!isset($this->nIdCreator)) || ($this->nIdCreator == $GLOBALS['oUserLogin']->nId))) {
    		return true;
    	} else {
    		return parent::changesAllowed();
    	}
    }

	/**
     * If current entry does not have an ID, entry is created and ID is returned. Also, object is updated with INSERT information.
     * 
     * @return integer ID if successful, NULL otherwise
     */
    public function create() {
    	$mId = parent::create();
    	
    	if (($mId !== NULL) && ($this->oCloud !== NULL)) {
    		$nTreeLeft = $GLOBALS['oDb']->escape($this->nTreeLeft);
    		$nIdCreator = $GLOBALS['oDb']->escape($this->oCloud->oUser->nId);
    		
    		$GLOBALS['oDb']->query('UPDATE `'.$this::TABLE.'` SET nTreeLeft=nTreeLeft+2 WHERE nTreeLeft > '.$nTreeLeft.' AND nIdCreator = '.$nIdCreator.' AND NOT bDeleted');
    		$GLOBALS['oDb']->query('UPDATE `'.$this::TABLE.'` SET nTreeRight=nTreeRight+2 WHERE nTreeLeft <> '.$nTreeLeft.' AND nTreeRight >= '.$nTreeLeft.' AND nIdCreator = '.$nIdCreator.' AND NOT bDeleted');
    	}
    	
    	return $mId;
    }
    
    /**
     * List all folders in path of file
     * 
     * @return array \Iiigel\Model\Folder objects returned (in adequate order) in an array
     */
    public function path() {
    	$nTreeLeft = $GLOBALS['oDb']->escape($this->nTreeLeft);
		$nTreeRight = $GLOBALS['oDb']->escape($this->nTreeRight);
		$nIdCreator = $GLOBALS['oDb']->escape($this->oCloud->oUser->nId);
		
		$oResult = $GLOBALS['oDb']->query('SELECT * FROM `cloud` WHERE nTreeLeft < '.$nTreeLeft.' AND nTreeRight > '.$nTreeRight.' AND nIdCreator = '.$nIdCreator.' AND NOT bDeleted ORDER BY nTreeLeft');
		
		$aPath = array();
		
		if ($oResult) {
			$nCount = $GLOBALS['oDb']->count($oResult);
			
			for ($i = 0; $i < $nCount; $i++) {
				$aPath[] = new \Iiigel\Model\Folder($GLOBALS['oDb']->get($oResult), $this->oCloud);
			}
		}
		
		return $aPath;
    }
    
    /**
     * Get joined list of folders as string
     * 
     * @return string path of file
     */
    public function pathString() {
    	$aPath = $this->path();
    	$sPath = '';
    	
    	foreach ($aPath as $oFolder) {
    		$sPath .= $oFolder->sName.'/';
    	}
    	
    	return $sPath.$this->sName;
    }
    
	/**
     * Deletes current row.
     * 
     * @return boolean true if successfully deleted, false otherwise
     */
    public function delete() {
    	if (parent::delete()) {
    		if ($this->bFilesystem) {
    			$aFile = explode(';', $this->sFile);
    			$sDeleteUrl = $aFile[2];
    			
    			$aDeleteFunction = explode('/', $sDeleteUrl);
    			$sUploadHash = $aDeleteFunction[count($aDeleteFunction) - 1];
    			
    			$oUpload = new \Iiigel\Generic\Upload();
    			$oUpload->delete($sUploadHash);
    		}
    		
    		return TRUE;
    	} else {
    		return FALSE;
    	}
    }
    
}

?>
