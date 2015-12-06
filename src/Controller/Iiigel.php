<?php namespace Iiigel\Controller;

class Iiigel extends \Iiigel\Controller\StaticPage {
    const DEFAULT_ACTION = 'show';
    
    protected $sRawOutput = NULL;
    protected $oCloud = NULL;
    protected $oInterpreter = NULL;
    protected $oChapter = NULL;
    protected $oModule = NULL;
    
    public function __construct() {
        if(!isset($GLOBALS['oUserLogin'])) {
            throw new \Exception(_('error.permission'));
        }
        parent::__construct();
        $this->oCloud = new \Iiigel\Model\Cloud();
    }
    
    /**
     * Loads an environment (= module, chapter and interpreter) based on a given hash (either module hash or chapter hash).
     * 
     * @param  string  [$_sHashId       = ''] module or chapter hashed ID
     * @return boolean true if successfully loaded
     */
    protected function loadEnvironment($_sHashId = '') {
        $this->oChapter = $this->oModule = NULL;
        if($_sHashId{0} == 'm') {
            $this->oModule = new \Iiigel\Model\Module($_sHashId);
			$nCurrentChapter = $this->oModule->nCurrentChapter;
			
			if ($nCurrentChapter == 0) {
		        $this->oChapter = new \Iiigel\Model\Chapter();
			
		        $oResult = $this->oChapter->getList($this->oModule->nId);
		        if(($aRow = $GLOBALS['oDb']->get($oResult))) {
		            $this->oChapter = new \Iiigel\Model\Chapter($aRow['sHashId']);
		        } else {
		            $this->oChapter = NULL;
		        }
			} else {
				$this->oChapter = new \Iiigel\Model\Chapter($nCurrentChapter);
			}
        } else {
            $this->oChapter = new \Iiigel\Model\Chapter($_sHashId);
            $this->oModule = new \Iiigel\Model\Module(intval($this->oChapter->nIdModule));
        }
        if($this->oModule->nId > 0 && $this->oChapter !== NULL) {
            if($this->oChapter->sInterpreter !== NULL) {
                $sInterpreter = '\\Iiigel\\View\\Interpreter\\'.$this->oChapter->sInterpreter;
                
                if(class_exists($sInterpreter)) {
                    $this->oInterpreter = new $sInterpreter($this->oCloud);
                } else {
                	$this->oInterpreter = new \Iiigel\View\Interpreter\File($this->oCloud);
                }
            }
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    /**
     * Output rendered static HTML page.
     * 
     * @return string HTML code
     */
    public function output() {
        return ($GLOBALS['bAjax'] && $this->sRawOutput !== NULL) ? $this->sRawOutput : $this->oView->render();
    }
    
    /**
     * Display the main module training view.
     * 
     * @param string $_sHashId if module ID, first chapter of this module is shown; if chapter ID, this chapter is shown
     */
    public function show($_sHashId = '') {
        if($this->loadEnvironment($_sHashId)) {
            //check if current user is in this module
            //load module data (incl. chapter)
            $this->oView->oModule = $this->oModule;
            $this->oChapter->sText =  $this->oChapter->replaceTags( $this->oChapter->sText);
            $this->oView->oChapter = $this->oChapter;
            $this->oView->nEditorWaitTime = $GLOBALS['aConfig']['nEditorWaitTime'];
            $this->loadFile('iiigel');
        } else {
            throw new \Exception(sprintf(_('error.objectload'), $_sHashId));
        }
    }
    
    /**
     * Loads cloud and sets ->sRawOutput accordingly.
     */
    public function cloud() {
        $this->sRawOutput = json_encode($this->oCloud->get());
    }
    
    /**
     * Interpretes a specific file within a specific chapter. Sets any (HTML) output into ->sRawOutput (without doctype, html, head, body tags).
     * 
     * @param string $_sHashIdFile    hash ID of element to interpret
     * @param string $_sHashIdChapter hash ID of chapter to be interpreted in (important for correct interpreter)
     */
    public function interpret($_sHashIdFile, $_sHashIdChapter) {
        if($this->loadEnvironment($_sHashIdChapter)) {
            if(($oFile = $this->oCloud->loadFile($_sHashIdFile))) {
                $this->sRawOutput = $this->oInterpreter->interpret($oFile);
                return;
            }
        } else {
        	throw new \Exception($_sHashIdFile." - ".$_sHashIdChapter);
        }
        
        throw new \Exception(_('error.filenotfound'));
    }
    
    /**
     * Open a specific file. Sets File() object to ->sRawOutput, allowing to manually redirect in case the MIME type is not text/...
     * 
     * @param string $_sHashId hash ID of element to open
     */
    public function open($_sHashId) {
        $this->sRawOutput = json_encode($this->oCloud->loadFile($_sHashId)->getCompleteEntry(TRUE));
    }
    
    /**
     * Closes file.
     * 
     * @param string $_sHashId hash ID of element to close
     */
    public function close($_sHashId) {
        $this->sRawOutput = $this->oCloud->closeFile($_sHashId);
    }
    
    /**
     * Save a file's new contents. Does output TRUE on success.
     * 
     * @param string $_sHashId  hash ID of element to be updated
     * @param string $_sContent new contents
     */
    public function update($_sHashId, $_sContent) {
        $oFile = $this->oCloud->loadFile($_sHashId);
        $oFile->sFile = $_sContent;
        $this->sRawOutput = $oFile->update();
    }
    
    /**
     * Create a new file (empty) and returns the new cloud structure.
     * 
     * @param string $_sHashIdParent hash ID of parent element, if invalid/NULL/empty, element is put to root element
     * @param string $_sName         new file's name
     */
    public function createFile($_sHashIdParent, $_sName) {
        if($this->oCloud->createFile($_sName, new \Iiigel\Model\Folder($_sHashIdParent, $this->oCloud))) {
            $this->sRawOutput = json_encode($this->oCloud->get());
        } else {
            $this->sRawOutput = _('error.create');
        }
    }
    
    /**
     * Create a new directory (empty) and returns the new cloud structure.
     * 
     * @param string $_sHashIdParent hash ID of parent element, if invalid/NULL/empty, element is put to root element
     * @param string $_sName         new folder's name
     */
    public function createDir($_sHashIdParent, $_sName) {
        if($this->oCloud->createFolder($_sName, new \Iiigel\Model\Folder($_sHashIdParent, $this->oCloud))) {
            $this->sRawOutput = json_encode($this->oCloud->get());
        } else {
            $this->sRawOutput = _('error.create');
        }
    }
    
    /**
     * Renames any element (either file or folder). Returns new cloud structure.
     * 
     * @param string $_sHashId  hash ID of element to be renamed
     * @param string $_sNewName new name
     */
    public function rename($_sHashId, $_sNewName) {
        if($this->oCloud->rename($_sHashId, $_sNewName)) {
            $this->sRawOutput = json_encode($this->oCloud->get());
        } else {
            $this->sRawOutput = _('error.update');
        }
    }
    
    /**
     * Deletes any element (either file or folder). If folder, all child elements are deleted as well. Returns new cloud structure.
     * 
     * @param string $_sHashId hash ID of element to be deleted
     */
    public function delete($_sHashId) {
        if($this->oCloud->delete($_sHashId)) {
            $this->sRawOutput = json_encode($this->oCloud->get());
        } else {
            $this->sRawOutput = sprintf(_('error.delete'), 'Cloud', $_sHashId);
        }
    }
    
    /**
     * Presents a file/folder for download. Uses HTTP headers and dies afterwards.
     * If element to be downloaded is a folder, this folder gets zip'ed and presented for download.
     * 
     * @param string $_sHashId hash ID of element to download
     */
    public function download($_sHashId) {
        //DOWNLOAD a file (presented as download) or ZIP&DOWNLOAD a folder (presented as download)
        //die() afterwards
    }
    
    /**
     * Upload a file from the local machine.
     * 
     * @param string $_sHashId hash ID of folder into which files should be loaded
     */
    public function uploadFromHost($_sHashId) {
        $oUpload = new \Iiigel\Generic\Upload();
        
        foreach ($oUpload->getFiles() as $sKey => $aFile) {
        	$this->oCloud->uploadFile($aFile, new \Iiigel\Model\Folder($_sHashId, $this->oCloud));
        }
        
        $this->sRawOutput = json_encode($this->oCloud->get());
    }
    
    /**
     * Upload a file from the web.
     * 
     * @param string $_sHashId hash ID of folder into which files should be loaded
     * @param string $_sUrl    URL to be included
     */
    public function uploadFromUrl($_sHashId, $_sUrl) {
        //PUSH file_get_contents or curl into cloud from current folder
        $this->sRawOutput = json_encode($this->oCloud->get());
    }
}

?>
