<?php namespace Iiigel\Controller;

class Iiigel extends \Iiigel\Controller\StaticPage {
    const DEFAULT_ACTION = 'show';
    
    /**
     * Display the main module training view.
     * 
     * @param string $_sHashId if module ID, first chapter of this module is shown; if chapter ID, this chapter is shown
     */
    public function show($_sHashId = '') {
        $oChapter = $oModule = NULL;
        if($_sHashId{0} == 'm') {
            $oModule = new \Iiigel\Model\Module($_sHashId);
            $oChapter = new \Iiigel\Model\Chapter();
            $oResult = $oChapter->getList($oModule->nId);
            if(($aRow = $GLOBALS['oDb']->get($oResult))) {
                $oChapter = new \Iiigel\Model\Chapter($aRow);
            } else {
                $oChapter = NULL;
            }
        } else {
            $oChapter = new \Iiigel\Model\Chapter($_sHashId);
            $oModule = new \Iiigel\Model\Module($oChapter->nIdModule);
        }
        if($oModule->nId > 0) {
            //check if current user is in this module
            //load module data (incl. chapter)
            $this->oView->oModule = $oModule;
            $this->oView->oChapter = $oChapter;
            $this->loadFile('iiigel');
        } else {
            throw new \Exception(sprintf(_('error.objectload'), $_sHashId));
        }
    }
}

?>