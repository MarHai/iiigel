<?php namespace Iiigel\Controller\Admin;

class DefaultController extends \Iiigel\Controller\DefaultController {
    const DEFAULT_ACTION = 'showList';
    const TABLE = '';
    const SHOW_RIGHTS = TRUE;
    
    protected $aList = array();
    protected $sClass = NULL;
    protected $sRawOutput = NULL;
    protected $sPreviousUrl = NULL;
    
    /**
     * Create a new admin controller in order to show and maintain admin-like pages (institution, group, user, etc.).
     */
    public function __construct() {
        $this->oView = new \Iiigel\View\Admin();
        $this->oView->sPage = $this::TABLE;
        $this->aList = array();
        $this->sClass = '\\Iiigel\\Model\\'.ucfirst($this::TABLE);
        if(isset($GLOBALS['aRequest']['sPreviousUrl'])) {
            $this->sPreviousUrl = $GLOBALS['aRequest']['sPreviousUrl'];
            unset($GLOBALS['aRequest']['sPreviousUrl']);
        }
    }
    
    /**
     * Set viewer (page) to be a two-column design with a list of entries on left-hand and a new form on right-hand side.
     */
    public function showList() {
        $oSingle = new $this->sClass();
        
        //left col
        $aData = array();
        if(($oResult = $oSingle->getList())) {
            while(($aRow = $GLOBALS['oDb']->get($oResult))) {
                $oTemp = new $this->sClass($aRow);
                $this->aList[] = $oTemp;
                $aTemp = $oTemp->getCompleteEntry();
                unset($aTemp['bDashboardNavShown'], $aTemp['sLanguage'], $aTemp['bMailIfOffline']);
                $aData[] = $aTemp;
            }
        }
        $oTable = new \Iiigel\View\Table($aData);
        $oTable->makeInteractive($this->oView);
        $oTable->onRowClick(URL.'Admin/'.ucfirst($this::TABLE).'/showDetail/');
        $oTable->addHeadline(sprintf(_('table.headline'), ngettext($this::TABLE, $this::TABLE.'s', 2)));
        $this->oView->aContent = $oTable->render();
        
        //right col
        $oForm = new \Iiigel\View\Page();
        $oForm->loadTemplate('admin/'.$this::TABLE.'.html');
        $oForm->aGroup = $GLOBALS['oUserLogin']->getGroups(TRUE);
        $oForm->aInstitution = $GLOBALS['oUserLogin']->getInstitutions(TRUE);
        $this->oView->aContent = $oForm->render();
    }
    
    /**
     * Creates entry with submitted GET/POST data.
     * On success, shows list again. On error, shows message.
     */
    public function create() {
        $aParam = $GLOBALS['aRequest'];
        unset($aParam['nId'], $aParam['c'], $aParam['a'], $aParam['path']);
        $oTemp = new $this->sClass($aParam);
        $nTempId = $oTemp->create();
        if($nTempId > 0) {
            $this->aList[] = $oTemp;
            if(isset($GLOBALS['oUserLogin']) && !$GLOBALS['oUserLogin']->bAdmin && in_array($this::TABLE, array('institution', 'module', 'chapter', 'group'))) {
                $oTemp = new \Iiigel\Model\Right(array(
                    'nIdUser' => $GLOBALS['oUserLogin']->nId,
                    'eType' => $this::TABLE,
                    'nIdType' => $nTempId
                ));
            }
            $this->redirect('Admin/'.ucfirst($this::TABLE).'/showList');
        } else {
            throw new \Exception(sprintf(_('error.newclass'), $this->sClass));
        }
    }
    
    /**
     * Updates single parameter for object given as _sHashId.
     * Needs three GET/POST parameters: pk (has to be equal to _sHashId), name (column name), value (new value)
     * Sets this->sRawOutput for later AJAX output
     * 
     * @param string $_sHashId hashed ID
     */
    public function update($_sHashId) {
        if(isset($GLOBALS['aRequest']['pk']) && $GLOBALS['aRequest']['pk'] == $_sHashId && isset($GLOBALS['aRequest']['name']) && isset($GLOBALS['aRequest']['value'])) {
            $oTemp = new $this->sClass($_sHashId);
            $oTemp->$GLOBALS['aRequest']['name'] = $GLOBALS['aRequest']['value'];
            if($oTemp->nId > 0) {
                if($oTemp->update()) {
                    $this->sRawOutput = '';
                } else {
                    $this->sRawOutput = _('error.update');
                }
            } else {
                $this->sRawOutput = sprintf(_('error.objectload'), $this->sClass);
            }
        } else {
            $this->sRawOutput = _('error.invalidrequest');
        }
    }
    
    /**
     * Delete one specific entry based on its hashed ID.
     * 
     * @param string $_sHashId hashed ID (as from ->hashId)
     */
    public function delete($_sHashId) {
        $oTemp = new $this->sClass($_sHashId);
        if($oTemp->delete()) {
            $this->redirect('Admin/'.ucfirst($this::TABLE).'/showList');
        } else {
            throw new \Exception(sprintf(_('error.delete'), $_sHashId, $this->sClass));
        }
    }
    
    /**
     * Show details for a single entry, based on its hashed ID.
     * 
     * @param string $_sHashId hashed representation of ID
     */
    public function showDetail($_sHashId) {
        $oView = new \Iiigel\View\Page();
        $oView->loadTemplate('admin/detail.html');
        if($this->sPreviousUrl !== NULL) {
            $oView->sPreviousUrl = $this->sPreviousUrl;
        }
        $aDetail = array();
        $oTemp = new $this->sClass($_sHashId);
        while(($aRow = $oTemp->get())) {
        	$aKeys = array_keys($aRow);
            $sKey = $aKeys[0];
            $aDetail[$sKey] = array(
                'sHeadline' => $oView->makeCamelCaseNicer($sKey),
                'mValue' => $aRow[$sKey],
                'eType' => 'text',
                'bEdit' => (!in_array($sKey, $oTemp::CONFIG_COLUMN) && $sKey != 'nId' && $sKey != 'sHashId')
            );
            if(substr($sKey, 0, 3) == 'nId' && $sKey != 'nId') {
                $aDetail[$sKey]['eType'] = 'select';
                $aDetail[$sKey]['nValue'] = $oTemp->$sKey;
                $aDetail[$sKey]['aSelect'] = $oTemp->getPossibleForeignKeys($sKey);
            } elseif($sKey == 'nStart' || $sKey == 'nEnd') {
                $aDetail[$sKey]['eType'] = 'datetime';
                $aDetail[$sKey]['sFormat'] = $GLOBALS['aConfig']['sDateFormatJs'];
            } elseif($sKey{0} == 'b') {
                $aDetail[$sKey]['eType'] = 'select';
                $aDetail[$sKey]['nValue'] = intval($aRow[$sKey]);
                $aDetail[$sKey]['aSelect'] = array(
                    array('value' => 1, 'text' => _('yes')),
                    array('value' => 0, 'text' => _('no'))
                );
            } elseif($sKey == 'sLanguage') {
                $aDetail[$sKey]['eType'] = 'select';
                $aDetail[$sKey]['nValue'] = $aRow[$sKey];
                $aDetail[$sKey]['aSelect'] = array_map(function($_sCountry) {
                        return array('value' => $_sCountry, 'text' => $_sCountry);
                    }, $GLOBALS['aConfig']['aLanguage']['aCountry']);
            } elseif($sKey == 'sDomain') {
                $aDetail[$sKey]['eType'] = 'select';
                $aDetail[$sKey]['nValue'] = $aRow[$sKey];
                $aDetail[$sKey]['aSelect'] = array_map(function($_sDomain) {
                        return array('value' => $_sDomain, 'text' => $_sDomain);
                    }, $GLOBALS['aConfig']['aLanguage']['aDomain']);
            } elseif($sKey == 'sText' || $sKey == 'sDescription') {
                $aDetail[$sKey]['eType'] = 'textarea';
            }
            if($sKey == 'sName') {
                $oView->sName = $aRow[$sKey];
            } elseif($sKey == 'sHashId') {
                $oView->sHashId = $aRow[$sKey];
            }
        }
        $oView->aDetail = $aDetail;
        $oView->sClass = ucfirst($this::TABLE);
        $this->oView->aContent = $oView->render();
        
        if($this::SHOW_RIGHTS) {
            $this->showRights($_sHashId);
        }
    }
    
    /**
     * As rights are differnet from usual showList lists, they have a separate method. However, all it does, is to list the rights of the current table accordingly, ready for detail-click and including a form below it.
     * 
     * @param string $_sHashId hashed ID of the "mother" entry (in whose detail view we currently are)
     */
    public function showRights($_sHashId) {
        $oSingle = new \Iiigel\Model\Right();
        $aData = array();
        $aResult = $oSingle->getList($_sHashId);
        for($i = 0; $i < count($aResult); $i++) {
            $oTemp = new \Iiigel\Model\Right($aResult[$i]);
            $aData[] = $oTemp->getCompleteEntry();
        }
        $oTable = new \Iiigel\View\Table($aData);
        $oTable->makeInteractive($this->oView);
        $oTable->onRowClick(URL.'Admin/Right/showDetail/%s?sPreviousUrl=Admin/'.ucfirst($this::TABLE).'/showDetail/'.$_sHashId);
        $oTable->addHeadline(ngettext('permission', 'permission.plural', 2));
        
        $oForm = new \Iiigel\View\Page();
        $oForm->loadTemplate('admin/right.html');
        $oForm->aUser = $this->getRightsUserSelection($_sHashId);
        $oForm->aTypeId = $this->getRightsTypeIdSelection($_sHashId);
        
        $this->oView->aContent = $oTable->render().$oForm->render();
    }
    
    /**
     * Return list of users which should be shown in the dropdown.
     * 
     * @param string $_sHashId hashed ID
     * @return array array with arrays with nId and sName keys set
     */
    protected function getRightsUserSelection($_sHashId) {
        $sClass = $this->sClass;
        return $sClass::getRightsUserSelection($_sHashId);
    }
    
    /**
     * Return list of entries which should be shown in the dropdown.
     * 
     * @param string $_sHashId hashed ID
     * @return array array with up to four keys (chapter, group, institution, module) which itself hold arrays with nId and sName keys set
     */
    protected function getRightsTypeIdSelection($_sHashId) {
        $sClass = $this->sClass;
        return $sClass::getRightsTypeIdSelection($_sHashId);
    }
    
    /**
     * Output rendered static HTML page.
     * 
     * @return string HTML code
     */
    public function output() {
        return ($GLOBALS['bAjax'] && $this->sRawOutput !== NULL) ? $this->sRawOutput : $this->oView->render();
    }
}

?>