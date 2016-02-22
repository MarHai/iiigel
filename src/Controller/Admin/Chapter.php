<?php namespace Iiigel\Controller\Admin;

class Chapter extends \Iiigel\Controller\Admin\DefaultController {
    const DEFAULT_ACTION = 'showDetail';
    const TABLE = 'chapter';
    
    /**
     * Set viewer (page) to be a two-column design with a list of entries on left-hand and a new form on right-hand side.
     * 
     * @param object $_oView View object to render columns. In case $_oView is NULL, the own view of the controller is used
     * @param integer $_nIdModule if set, only this module's chapters are loaded
     */
    public function showList($_oView = NULL, $_nIdModule = NULL) {
        $oSingle = new $this->sClass();
        $nOrder = 0;
        
        //left col
        $aData = array();
        if(($oResult = $oSingle->getList($_nIdModule))) {
            while(($aRow = $GLOBALS['oDb']->get($oResult))) {
                $oTemp = new $this->sClass($aRow);
                $this->aList[] = $oTemp;
                $aTemp = $oTemp->getCompleteEntry();
                unset($aTemp['bDashboardNavShown'], $aTemp['sLanguage'], $aTemp['bMailIfOffline']);
                
                if ((isset($aTemp['nOrder'])) && ($aTemp['nOrder'] > $nOrder)) {
                	$nOrder = $aTemp['nOrder'];
                }
                
                $aData[] = $aTemp;
            }
        }
        
        if (count($aData) >= 0) {
        	$oTable = new \Iiigel\View\Table($aData);
        	$oTable->makeInteractive($this->oView);
        	$oTable->onRowClick(URL.'Admin/'.ucfirst($this::TABLE).'/showDetail/');
        	$oTable->addHeadline(sprintf(_('table.headline'), ngettext($this::TABLE, $this::TABLE.'s', 2)));
        	
        	if ($_oView == NULL) {
        		$this->oView->aContent = $oTable->render();
        	} else {
        		$_oView->aContent = $oTable->render();
        	}
        }
        
        if ($_nIdModule !== NULL) {
		    //right col
		    $oForm = new \Iiigel\View\Page();
		    $oForm->loadTemplate('admin/'.$this::TABLE.'.html');
		    $oForm->aGroup = $GLOBALS['oUserLogin']->getGroups(TRUE);
		    $oForm->aInstitution = $GLOBALS['oUserLogin']->getInstitutions(TRUE);
		    $oForm->nIdModule = $_nIdModule;
		    $oForm->nOrder = $nOrder + 1;
		    
		    if ($_oView === NULL) {
		    	$this->oView->aContent = $oForm->render();
		    } else {
		    	$_oView->aContent = $oForm->render();
		    }
		}
    }
    
    /**
     * Creates entry with submitted GET/POST data.
     * On success, shows list again. On error, shows message.
     */
    public function create() {
        $aParam = $GLOBALS['aRequest'];
        if (isset($aParam['nIdModule'])) {
        	$oModule = new \Iiigel\Model\Module(intval($aParam['nIdModule']));
        	$this->sPreviousUrl = 'Admin/Module/showDetail/'.$oModule->sHashId;
        }
        
        parent::create();
    }
    
    /**
     * Redirect to the page given. Use relative URL from URL constant onwards (without initial slash).
     * 
     * @param string $_sUrl relative URL to redirect to
     */
    public function redirect($_sUrl) {
        parent::redirect($this->sPreviousUrl === NULL ? $_sUrl : $this->sPreviousUrl);
    }
    
    /**
     * Delete one specific entry based on its hashed ID.
     * 
     * @param string $_sHashId hashed ID (as from ->hashId)
     */
    public function delete($_sHashId) {
    	$oTemp = new $this->sClass($_sHashId);
    	$oModule = new \Iiigel\Model\Module(intval($oTemp->nIdModule));
    	$this->sPreviousUrl = 'Admin/Module/showDetail/'.$oModule->sHashId;
        parent::delete($_sHashId);
    }
    
     /**
     * Show details for a single entry, based on its hashed ID.
     * 
     * @param string $_sHashId hashed representation of ID
     */
    public function showDetail($_sHashId) {
    	$oTemp = new $this->sClass($_sHashId);
    	$oModule = new \Iiigel\Model\Module(intval($oTemp->nIdModule));
    	$this->sPreviousUrl = 'Admin/Module/showDetail/'.$oModule->sHashId;
    	
    	$oForm = new \Iiigel\View\Page();
		$oForm->loadTemplate('admin/chapter-editor.html');
		$oForm->sChapterId = $oTemp->sHashId;
		$oForm->aChapterNames = $oModule->aChapter;
    
		$this->oView->aContent = $oForm->render();
		$this->oView->aContent = '<div class="iiigel-scroll" id="chapter-interpreter">          
</div>';
    	
    	$this->oView->addRow();
    	
    	parent::showDetail($_sHashId);
    }
    
    public function getChapter($_sHashId) {
		$oTemp = new $this->sClass($_sHashId);
        $this->sRawOutput = json_encode($oTemp->getCompleteEntry(TRUE));
    }
    
	public function updateChapter($_sHashId, $_sContent) {
        $oChapter = new \Iiigel\Model\Chapter($_sHashId);
        $oChapter->sText = $_sContent;
        $oChapter->update();
        
        $this->sRawOutput = $oChapter->replaceTags($_sContent);
    }
}

?>
