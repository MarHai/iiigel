<?php namespace Iiigel\View\Interpreter;

abstract class DefaultInterpreter {
    protected $oCloud = NULL;
    
    /**
     * Set up new interpreter with (mostly, the current user's) cloud.
     * 
     * @param object $_oCloud \Iiigel\Model\Cloud object within which interpreter is executed
     */
    public function __construct($_oCloud) {
        $this->oCloud = $_oCloud;
    }
    
    /**
     * Interpret one file within current cloud. Return output.
     * 
     * @param object $_oFile \Iiigel\Model\File object to base the interpretation on
     */
    abstract public function interpret($_oFile);
}

?>