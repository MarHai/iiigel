<?php namespace Iiigel\Controller\Admin;

class Institution extends \Iiigel\Controller\Admin\DefaultController {
    const DEFAULT_ACTION = 'showList';
    const TABLE = 'institution';
    
     /**
     * Show details for a single entry, based on its hashed ID.
     * 
     * @param string $_sHashId hashed representation of ID
     */
    public function showDetail($_sHashId) {
        parent::showDetail($_sHashId);
        
        $oSingle = new \Iiigel\Model\InstitutionAffiliation();
        $oUserSingle = new \Iiigel\Model\User();
        
        $aNot = array();
        
        $oResult = $oUserSingle->getList();
		
		while(($GLOBALS['oDb']->count($oResult) > 0) && ($aRow = $GLOBALS['oDb']->get($oResult))) {
           	$aNot[] = new \Iiigel\Model\User($aRow);
        }
        
        $aData = array();
        $oResult = $oSingle->getList($_sHashId);
        while(($aRow = $GLOBALS['oDb']->get($oResult))) {
            $oTemp = new \Iiigel\Model\InstitutionAffiliation($aRow);
            
            for ($i = count($aNot) - 1; $i >= 0; $i--) {
            	if ($aNot[$i]->nId == $oTemp->nIdUser) {
            		array_splice($aNot, $i, 1);
            	}
            }
            
            $aData[] = $oTemp->getCompleteEntry();
        }
        
        if(count($aData) > 0) {
            $oTable = new \Iiigel\View\Table($aData);
            $oTable->addHeadline(ngettext('user2institution.member', 'user2institution.member.plural', count($aData)));

            $this->oView->addRow();
            $this->oView->aContent = $oTable->render();
        }
        
        $oForm = new \Iiigel\View\Page();
        $oForm->loadTemplate('admin/add-user.html');
        $oForm->aUser = $aNot;
        $oForm->sHashId = $_sHashId;
        $oForm->sType = ucfirst($this::TABLE);
        
        $this->oView->aContent = $oForm->render();
    }
    
    public function addUser($_sHashId) {
    	if (isset($GLOBALS['aRequest']['nIdUser'])) {
			$oSingle = new \Iiigel\Model\InstitutionAffiliation();
			$oResult = $oSingle->getList($_sHashId);
			
			$bFirst = $GLOBALS['oDb']->count($oResult) == 0;
			
			while(($aRow = $GLOBALS['oDb']->get($oResult)));
			
			$oInstitution = new \Iiigel\Model\Institution($_sHashId);
			$oUser = new \Iiigel\Model\User(intval($GLOBALS['aRequest']['nIdUser']));
			
			$oSingle = new \Iiigel\Model\InstitutionAffiliation(array(
				"nIdInstitution" => $oInstitution->nId,
				"nIdUser" => $oUser->nId
			));
			
			$oSingle->create();
		}
		
		$this->redirect(URL.'Admin/'.ucfirst($this::TABLE).'/showDetail/'.$_sHashId);
    }
}

?>
