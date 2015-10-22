<?php namespace Iiigel\View\Interpreter;

class Javascript extends \Iiigel\View\Interpreter\DefaultInterpreter {
    /**
     * Interpret JS code.
     * 
     * @param object $_oFile \Iiigel\Model\File object to base the interpretation on
     */
    public function interpret($_oFile) {
        return '<script type="text/javascript">'.$_oFile->sFile.'</script>';
    }
}

?>