<?php namespace Iiigel\Controller;

class Interpreter extends \Iiigel\Controller\StaticPage {
    const DEFAULT_ACTION = 'showFile';
    
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
}

?>
