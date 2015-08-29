<?php namespace Iiigel\View\Interpreter;

class Html extends \Iiigel\View\Interpreter\DefaultInterpreter {
    /**
     * Interpret HTML code.
     * 
     * @param object $_oFile \Iiigel\Model\File object to base the interpretation on
     */
    public function interpret($_oFile) {
        return $_oFile->sFile;
    }
}

?>