<?php namespace Iiigel\Controller;

class Cloud extends \Iiigel\Controller\DefaultController {
    const DEFAULT_ACTION = 'show';
    
    protected $oCloud = NULL;
    
	public function __construct() {
        if(!isset($GLOBALS['oUserLogin'])) {
            throw new \Exception(_('error.permission'));
        }
        
        $this->oCloud = new \Iiigel\Model\Cloud();
    }
    
    /**
     * Show a file (with appropriate mime type and no-cache) from the cloud.
     * Output "dies" afterwards.
     */
    public function show() {
        $sPath = str_replace(array('@cloud/', 'Cloud/'), '', $GLOBALS['aRequest']['path']);
        $oFile = $this->oCloud->getFile($this->oCloud->oRootFolder->sName.'/'.$sPath);
        
        if ($oFile !== NULL) {
        	if ($oFile->sType === 'folder') {
        		die("THIS COULD BE A DOWNLOAD-LINK FOR A ZIP-FILE!");
        	} else {
	        	if ($oFile->bFilesystem) {
	        		$aFile = explode(';', $oFile->sFile);
	        		$sFileUrl = $aFile[1];
	        		
	        		header('Location: '.$sFileUrl);
	        	} else {
	        		header('Cache-Control: no-cache, must-revalidate');
	        		header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
	        		header('Content-Type: '.$oFile->sType);
	        		header('HTTP/1.1 200 Ok');
	        		
	        		header('Content-Length: '.strlen($oFile->sFile));
	        		die($oFile->sFile);
	        	}
        	}
        } else {
        	header('Location: '.URL);
        }
    }
}

?>