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
        	$aTemp = array();
        	 
        	foreach ($this->oView->aCheckedHandins as $oRow) {
        		if ($oRow['sLearn'] !== $_sHashId) {
        			$aTemp[] = $oRow;
        		}
        	}
        	 
        	$this->oView->aCheckedHandins = $aTemp;
        	
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
            $this->oView->bHandin = FALSE;
            $this->oView->oHandin = NULL;
			$this->oView->icurrentuserprogress = $this->oModule->getCurrentChapterProgressOrder($GLOBALS['oUserLogin']->nId);
            $this->loadFile('iiigel');
        } else {
            throw new \Exception(sprintf(_('error.objectload'), $_sHashId));
        }
    }
    
    public function lookAtHandin($_sHashId) {
    	$aTemp = array();
    	
    	foreach ($this->oView->aReviewHandins as $oRow) {
    		if ($oRow['sHashId'] !== $_sHashId) {
    			$aTemp[] = $oRow;
    		}
    	}
    	
    	$this->oView->aReviewHandins = $aTemp;
    	
    	$oHandin = new \Iiigel\Model\Handin($_sHashId);
    	$oGroup = new \Iiigel\Model\Group(intval($oHandin->nIdGroup));
    	
    	if ($this->hasGroupEditPermission($oGroup->sHashId)) {
    		$this->oCloud = new \Iiigel\Model\Cloud(intval($oHandin->nIdCreator));
    		$this->oChapter = new \Iiigel\Model\Chapter(intval($oHandin->nIdChapter));
    		$this->oModule = new \Iiigel\Model\Module(intval($this->oChapter->nIdModule));
    		 
    		if($oHandin->sInterpreter !== NULL) {
    			$sInterpreter = '\\Iiigel\\View\\Interpreter\\'.$oHandin->sInterpreter;
    			 
    			if(class_exists($sInterpreter)) {
    				$this->oInterpreter = new $sInterpreter($this->oCloud);
    			} else {
    				$this->oInterpreter = new \Iiigel\View\Interpreter\File($this->oCloud);
    			}
    		
    			$this->oView->oModule = $this->oModule;
    			$this->oChapter->sText =  $this->oChapter->replaceTags( $this->oChapter->sText);
    			$this->oView->oChapter = $this->oChapter;
    			$this->oView->nEditorWaitTime = $GLOBALS['aConfig']['nEditorWaitTime'];
    			$this->oView->bHandin = TRUE;
    			$this->oView->oHandin = $oHandin;
    			$this->loadFile('iiigel');
    		} else {
    			throw new \Exception(sprintf(_('error.objectload'), $_sHashId));
    		}
    	} else {
    		throw new \Exception(_('error.permission'));
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
     * @param string $_sHashIdHandin  hash ID of handin to set interpreter and finding file-content
     */
    public function interpret($_sHashIdFile, $_sHashIdChapter, $_sHashIdHandin = '') {
    	if (strlen($_sHashIdChapter) > 0) {
    		if($this->loadEnvironment($_sHashIdChapter)) {
    			if(($oFile = $this->oCloud->loadFile($_sHashIdFile))) {
    				$this->sRawOutput = $this->oInterpreter->interpret($oFile);
    				return;
    			}
    		} else {
    			throw new \Exception($_sHashIdFile." - ".$_sHashIdChapter);
    		}
    	} else
    	if (strlen($_sHashIdHandin) > 0) {
    		$oHandin = new \Iiigel\Model\Handin($_sHashIdHandin);
    		
    		$this->oCloud = new \Iiigel\Model\Cloud(intval($oHandin->nIdCreator));
    		$this->oChapter = new \Iiigel\Model\Chapter(intval($oHandin->nIdChapter));
    		$this->oModule = new \Iiigel\Model\Module(intval($this->oChapter->nIdModule));
    		 
    		if($oHandin->sInterpreter !== NULL) {
    			$sInterpreter = '\\Iiigel\\View\\Interpreter\\'.$oHandin->sInterpreter;
    			 
    			if(class_exists($sInterpreter)) {
    				$this->oInterpreter = new $sInterpreter($this->oCloud);
    			} else {
    				$this->oInterpreter = new \Iiigel\View\Interpreter\File($this->oCloud);
    			}
    			
    			if(($oFile = $this->oCloud->loadFile($_sHashIdFile))) {
    				$this->sRawOutput = $this->oInterpreter->interpret($oFile);
    				return;
    			}
    		} else {
    			throw new \Exception($_sHashIdFile." - ".$_sHashIdHandin);
    		}
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
        
    	$oFile = $this->oCloud->loadFile($_sHashId);
    	
    	if ($oFile !== NULL) {
    		$aEntry = $oFile->getCompleteEntry(TRUE);
    		
    		$this->oCloud->closeFile($_sHashId);
    		
    		if ($aEntry['bFilesystem']) {
    			if ($aEntry['sType'] === 'folder') {
    				// ... ZIP
    			} else
    			if (strpos($aEntry['sType'], 'text') !== 0) {
    				$this->redirect($aEntry['sFile'].'?a=download');
    			}
    		}
    		
    		header('Cache-Control: no-cache, must-revalidate');
    		header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    		header('Content-Type: '.$aEntry['sType']);
    		header('Content-Length: '.strlen($aEntry['sFile']));
    		header('Content-Disposition: attachment; filename="'.$aEntry['sName'].'"');
    		
    		die($aEntry['sFile']);
    	}
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
    
    /**
     * Submit a handin for the current chapter.
     */
    public function submit($_sHashIdChapter) {
    	if ($this->loadEnvironment($_sHashIdChapter)) {
    		$sState = $this->oCloud->getCurrentState();
    		
    		$nIdUser = $GLOBALS['oDb']->escape($GLOBALS['oUserLogin']->nId);
    		$nIdModule = $GLOBALS['oDb']->escape($this->oModule->nId);
    		$nIdChapter = $GLOBALS['oDb']->escape($this->oChapter->nId);
    		
    		$oResult = $GLOBALS['oDb']->query('SELECT user2group.nIdGroup AS nIdGroup FROM user2group, chapter WHERE NOT user2group.bDeleted AND user2group.nIdUser = '.$nIdUser.' AND user2group.nIdModule = '.$nIdModule.' AND user2group.nIdChapter = chapter.nId AND chapter.nId = '.$nIdChapter.' ORDER BY chapter.nOrder DESC LIMIT 1;');
    		
    		if ($GLOBALS['oDb']->count($oResult) > 0) {
    			if ($aRow = $GLOBALS['oDb']->get($oResult)) {
    				$oResult = $GLOBALS['oDb']->query('SELECT * FROM handin WHERE 
    					nIdCreator = '.$nIdUser.' AND
    					nIdGroup = '.$GLOBALS['oDb']->escape($aRow['nIdGroup']).' AND
    					nIdChapter = '.$nIdChapter.'
    				LIMIT 1;');
    				
    				if ($GLOBALS['oDb']->count($oResult) > 0) {
    					$oHandin = new \Iiigel\Model\Handin($GLOBALS['oDb']->get($oResult));
    					
    					$oHandin->bCurrentlyUnderReview = !$oHandin->bCurrentlyUnderReview;
    					
    					if ($oHandin->update()) {
    						$this->sRawOutput = $sState;
    					}
    				} else {
    					$oHandin = new \Iiigel\Model\Handin(array(
    						"nIdGroup" => $aRow['nIdGroup'],
    						"nIdChapter" => $this->oChapter->nId,
    						"sInterpreter" => $this->oChapter->sInterpreter,
    						"sCloud" => $sState
    					));
    					
    					if ($oHandin->create() !== NULL) {
    						$this->sRawOutput = $sState;
    					}
    				}
    			}
    		}
    	}
    }

    /**
     * Accepts a handin for the current chapter.
     */
    public function accept($_sHashId) {
    	$oHandin = new \Iiigel\Model\Handin($_sHashId);
    	$oGroup = new \Iiigel\Model\Group(intval($oHandin->nIdGroup));
    	
    	if ($this->hasGroupEditPermission($oGroup->sHashId)) {
    		$oChapter = new \Iiigel\Model\Chapter(intval($oHandin->nIdChapter));
    		
    		$nOrder = $GLOBALS['oDb']->escape($oChapter->nOrder);
    		$nIdModule = $GLOBALS['oDb']->escape($oChapter->nIdModule);
    		$nIdUser = $GLOBALS['oDb']->escape($oHandin->nIdCreator);
    		$nIdGroup = $GLOBALS['oDb']->escape($oGroup->nId);
    		
    		$GLOBALS['oDb']->query('UPDATE `user2group` SET `nIdChapter` = (SELECT `nId` FROM `chapter` WHERE NOT `bDeleted` AND `bLive` AND `nOrder` > '.$nOrder.' AND `nIdModule` = '.$nIdModule.' ORDER BY `nOrder` ASC LIMIT 1) WHERE NOT `bDeleted` AND `nIdUser` = '.$nIdUser.' AND `nIdGroup` = '.$nIdGroup.';');
    		
    		$this->sRawOutput = ($oHandin->delete()? 'y' : 'n');
    	} else {
    		throw new \Exception(_('error.permission'));
    	}
    }

    /**
     * Denies a handin for the current chapter.
     */
    public function deny($_sHashId) {
    	$oHandin = new \Iiigel\Model\Handin($_sHashId);
    	$oGroup = new \Iiigel\Model\Group(intval($oHandin->nIdGroup));
    	
    	if ($this->hasGroupEditPermission($oGroup->sHashId)) {
    		$oHandin->bCurrentlyUnderReview = !$oHandin->bCurrentlyUnderReview;
    		$oHandin->nRound += 1;
    		
    		$this->sRawOutput = ($oHandin->update()? 'y' : 'n');
    	} else {
    		throw new \Exception(_('error.permission'));
    	}
    }
    
}

?>
