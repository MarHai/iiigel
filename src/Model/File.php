<?php namespace Iiigel\Model;

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
		    	$bNeedCreation = true;
		    	
		    	if ($GLOBALS['oDb']->count($oResult) > 0) {
		    		$aRow = $GLOBALS['oDb']->get($oResult);
		    		
		    		if (isset($aRow['nId'])) {
		    			parent::__construct($aRow);
		    			return;
		    		}
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
    	if ($_sName === 'sFile') {
    		$sFile = parent::__get('sFile');
    		
	    	if ($this->bFilesystem) {
	    		$sFilename = Cloud::getCloudFilename($sFile);
	    		
	    		if (file_exists($sFilename)) {
	    			return file_get_contents($sFilename);
	    		} else {
	    			return '';
	    		}
	    	} else {
	    		return $sFile;
	    	}
    	} else
    	if ($_sName === 'oParent') {
    		if($this->oParentFolder === NULL) {
    			$aPath = $this->oCloud->listPath($this->sHashId);
    			$nCount = count($aPath);
    			
    			if ($nCount > 0) {
    				$this->oParentFolder = $aPath[$nCount - 1];
    			}
    		}
    		
    		return $this->oParentFolder;
    	} else
        if($_sName === 'sSize') {
        	if ($this->bFilesystem) {
        		$sFilename = Cloud::getCloudFilename(parent::__get('sFile'));
        		$nSize = filesize($sFilename);
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
     * Runs through all columns (via ->get()) and returns the loaded entry (so far).
     * 
     * @param boolean $_bIncludeConfigColumns if true, really all columns are included
     * @return array   key/value pairing of all columns except CONFIG ones
     */
    public function getCompleteEntry($_bIncludeConfigColumns = FALSE) {
        $aData = parent::getCompleteEntry($_bIncludeConfigColumns);
        $aData['sSize'] = $this->sSize;
        return $aData;
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
}

?>
