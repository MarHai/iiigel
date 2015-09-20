<?php namespace Iiigel\Controller\Admin;

class Group extends \Iiigel\Controller\Admin\DefaultController {
    const DEFAULT_ACTION = 'showList';
    const TABLE = 'group';
    
    /**
     * Show details for a single entry, based on its hashed ID.
     * 
     * @param string $_sHashId hashed representation of ID
     */
    public function showDetail($_sHashId) {
        parent::showDetail($_sHashId);
        
        $oSingle = new \Iiigel\Model\GroupAffiliation();
        $aData = array();
        $oResult = $oSingle->getList($_sHashId);
        while(($aRow = $GLOBALS['oDb']->get($oResult))) {
            $oTemp = new \Iiigel\Model\GroupAffiliation($aRow);
            $aData[] = $oTemp->getCompleteEntry();
        }
        
        if(count($aData) > 0) {
            $oTable = new \Iiigel\View\Table($aData);
            $oTable->addHeadline(ngettext('user2group.member', 'user2group.member.plural', count($aData)));

            $this->oView->addRow();
            $this->oView->aContent = $oTable->render();

            //von showRights() kopiert - VORLAGE
            //$oTable->makeInteractive($this->oView);
            //$oTable->onRowClick(URL.'Admin/Right/showDetail/%s?sPreviousUrl=Admin/'.ucfirst($this::TABLE).'/showDetail/'.$_sHashId);

            //$oForm = new \Iiigel\View\Page();
            //$oForm->loadTemplate('admin/right.html');
            //$oForm->aUser = $this->getRightsUserSelection($_sHashId);
            //$oForm->aTypeId = $this->getRightsTypeIdSelection($_sHashId);

            //$this->oView->aContent = $oTable->render().$oForm->render();
        }
    }
}

?>