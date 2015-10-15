<?php namespace Iiigel\Controller\Frame;

abstract class Interpreter extends \Iiigel\Controller\DefaultController {
    const DEFAULT_ACTION = 'loadInterpreter';
    const INTERPRETER_NAME = 'Html';
    
    private $oContent = "";
    private $oCloud = NULL;
    
	protected $oView = NULL;
    
    /**
     * Create a new interpreter controller
     */
    public function __construct() {
    	$this->sContent = "";
    	$this->oCloud = new \Iiigel\Model\Cloud();
        $this->loadInterpreter(Interpreter::INTERPRETER_NAME);
    }
    
    /**
	 * Loads an interpreter view by name
     * 
     * @param string $_sName interpreter name
     */
    private function loadInterpreter($_sName) {
    	$sClassName = "\\Iiigel\\View\\Interpreter\\".$_sName;
    	
    	if (class_exists($sClassName)) {
    		$this->oView = new $sClassName($this->oCloud);
    	} else {
    		$this->oView = new \Iiigel\View\Interpreter\Html($this->oCloud);
    	}
    }
    
    /**
     * Output interpreted HTML page.
     * 
     * @return string HTML code
     */
    public function output() {
    	$GLOBALS['aConfig']['bDebug'] = FALSE;
    	return $this->sContent;
    }
    
    /**
     * Display the as HTML code interpreted file by hashed id for admins and non-admins.
     * 
     * @param string $_sHashId hashed ID
     */
    public function show($_sHashId) {
    	$oFile = $this->oCloud->loadFile($_sHashId);
    	
    	$this->sContent = $this->oView->interpret($oFile);
    }
}

?>