<?php namespace Iiigel\Controller\Admin;

class Module extends \Iiigel\Controller\Admin\DefaultController {
    const DEFAULT_ACTION = 'showList';
    const TABLE = 'module';
    
    /**
     * Creates entry with submitted GET/POST data.
     * On success, shows list again. On error, shows message.
     */
    public function create() {
        $oUpload = new \Iiigel\Generic\Upload();
        $aFile = $oUpload->getFiles();
        if(isset($aFile['sImage'])) {
            if(\Iiigel\Generic\Image::isImage($GLOBALS['aConfig']['sUploadDir'].$aFile['sImage']['name'])) {
                \Iiigel\Generic\Image::resize($GLOBALS['aConfig']['sUploadDir'].$aFile['sImage']['name']);
            }
            $GLOBALS['aRequest']['sImage'] = $aFile['sImage']['name'];
        }
        parent::create();
    }

	/**
     * Show details for a single entry, based on its hashed ID.
     * 
     * @param string $_sHashId hashed representation of ID
     */
	public function showDetail($_sHashId) {
		parent::showDetail($_sHashId);
		
		/*
		$oTemp = new $this->sClass($_sHashId);
		
		// $this->oView->addRow();
		
		$oChapterController = new \Iiigel\Controller\Admin\Chapter();
		$oChapterController->showList($oTemp->nId);
		
		$this->oView->aContent = $oChapterController->oView->aContent;
		*/
	}
	
}

?>
