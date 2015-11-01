<?php namespace Iiigel\Controller;

class Dashboard extends \Iiigel\Controller\StaticPage {
    const DEFAULT_ACTION = 'show';
    
    /**
     * Display the dashboard with some markers for admins and non-admins.
     */
    public function show() {
        $this->oView->nCountGroup = count($GLOBALS['oUserLogin']->getGroups(TRUE, TRUE));
        $this->oView->nCountInstitution = count($GLOBALS['oUserLogin']->getInstitutions(TRUE, TRUE));
        $this->oView->nCountModule = count($GLOBALS['oUserLogin']->getModules(TRUE, TRUE));
        $this->oView->nCountUser = $GLOBALS['oUserLogin']->bAdmin ? $GLOBALS['oDb']->count($GLOBALS['oUserLogin']->getList()) : 0;
        
        $aCells = array();
        
        $nRows = 0;
        $aColumns = array();
        $nOffsets = array();
        
        foreach ($GLOBALS['oUserLogin']->getModules() as $oModule) {
        	if (($nRows <= 0) || ($aColumns[$nRows - 1] >= 6)) {
        		$aCells[] = array();
        		$aColumns[] = 0;
        		$nRows++;
        	}
        	
        	$aCells[$nRows - 1][] = $oModule;
        	$aColumns[$nRows - 1]++;
        }
        
        $this->oView->aModuleTable = array(
        	'nRows' => $nRows,
        	'aColumns' => $aColumns,
        	'aCells' => $aCells
        );
        
        //show groups the user is part of
        //within these groups, allow to choose a module with which to start
        
        //show institutions the user is part of (without any actions possible)
        
        //show current modules
        
        $this->loadFile('dashboard');
    }
}

?>