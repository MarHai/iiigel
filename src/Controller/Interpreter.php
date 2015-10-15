<?php namespace Iiigel\Controller;

class Dashboard extends \Iiigel\Controller\StaticPage {
    const DEFAULT_ACTION = 'show';
    
    /**
     * Display the interpreter-window with some markers for admins and non-admins.
     * 
     * @param string $_sInterpreter interpreter name
     * @param string $_sHashId hashed ID
     */
    public function showFile($_sName, $_sHashId) {
    	$this->oView->sName = $_sName;
        $this->oView->sHashId = $_sHashId;
        $this->loadFile('interpreter');
    }
    
    public function show() {
    	$this->showFile("Html", 2);
    }
}

?>