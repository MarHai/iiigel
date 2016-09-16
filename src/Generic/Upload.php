<?php namespace Iiigel\Generic;

class Upload {
    protected $aFile = array();
    
    /**
     * Handles uploaded files (renames them, checks them, moves them to upload directory).
     */
    public function __construct() {
        if(isset($_FILES) && count($_FILES) > 0) {
            reset($_FILES);
            
            foreach($_FILES as $sKey => $aConfig) {
                if(is_uploaded_file($aConfig['tmp_name'])) {
                    if($aConfig['error'] == UPLOAD_ERR_OK) {
                        if($aConfig['size'] > $GLOBALS['aConfig']['nUploadMaxSize']) {
                            throw new \Exception(sprintf(_('error.filesizeexceeded'), $GLOBALS['aConfig']['nUploadMaxSize']));
                        } else {
                            $sName = $this::findName($aConfig['name']);
                            if($this->moveUploadedFile($aConfig['tmp_name'], $sName)) {
                                $this->aFile[$sKey] = array(
                                    'name' => $sName,
                                    'size' => $aConfig['size'],
                                    'type' => finfo_file(finfo_open(FILEINFO_MIME_TYPE), $GLOBALS['aConfig']['sUploadDir'].$sName),
                                    'url' => URL.$GLOBALS['aConfig']['sFileUrl'].$sName,
                                    'originalName' => $aConfig['name'],
                                    'deleteUrl' => URL.'File/delete/'.$this->createHash($sName)
                                );
                            } else {
                                throw new \Exception(sprintf(_('error.moveuploadedfile'), $aConfig['name']));
                            }
                        }
                    } else {
                        throw new \Exception(sprintf(_('error.upload'), $aConfig['error']));
                    }
                }
            }
        }
    }
    
    private function moveUploadedFile($_sTmpName, $_sName) {
    	if (!is_dir($GLOBALS['aConfig']['sUploadDir'])) {
    		if (!mkdir($GLOBALS['aConfig']['sUploadDir'], null, true)) {
    			return false;
    		}
    	}
    	
    	if ((!file_exists($_sTmpName)) || (file_exists($GLOBALS['aConfig']['sUploadDir'].$_sName))) {
    		return false;
    	}
    	
    	return move_uploaded_file($_sTmpName, $GLOBALS['aConfig']['sUploadDir'].$_sName);
    }
    
    /**
     * Hash salt and filename and currently logged in user.
     * 
     * @param  string $_sFilename filename (without path)
     * @return string hash
     */
    protected function createHash($_sFilename) {
        return md5($GLOBALS['aConfig']['sSalt'].$_sFilename.$GLOBALS['oUserLogin']->sMail);
    }
    
    /**
     * Verfies filename. Also checks if filename already exists and finds an appropriately similar name.
     * 
     * @param  string $_sName original filname
     * @return string filename to be used
     */
    static public function findName($_sName) {
        list($sExt, $sName) = explode('.', strrev(trim($_sName)), 2);
        $sName = str_replace(array('/', '\\', ':', '*', '?', '"', '<', '>', '|'), '', strrev($sName));
        if($sName == '') {
            $sName = _('upload.defaultfilename');
        }
        $sExt = strtolower(strrev($sExt));
        $sFilename = $sName.'.'.$sExt;
        $i = 0;
        while(file_exists($GLOBALS['aConfig']['sUploadDir'].$sFilename)) {
            $i++;
            $sFilename = $sName.'_'.$i.'.'.$sExt;
        }
        return $sFilename;    
    }
    
    /**
     * Returnes array of uploaded/handled files.
     * 
     * @return array array with upload HTML element as key and array with name/size/mime/url/thumbnail-url/delete-url/delete-type at each position
     */
    public function getFiles() {
        return $this->aFile;
    }
    
    /**
     * Returnes array of uploaded/handled files.
     * 
     * @param string $_sFileHash hash generated in upload function
     * @return boolean  TRUE if deletion was successful (and allowed)
     */
    public function delete($_sFileHash) {
        if(isset($GLOBALS['aRequest']['sFile'])) {
            if($this->createHash($GLOBALS['aRequest']['sFile']) == $_sFileHash) {
                return unlink($GLOBALS['aConfig']['sUploadDir'].$GLOBALS['aRequest']['sFile']);
            }
        }
        return FALSE;
    }
}

?>