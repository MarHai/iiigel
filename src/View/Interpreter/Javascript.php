<?php namespace Iiigel\View\Interpreter;

class Javascript extends \Iiigel\View\Interpreter\DefaultInterpreter {
    /**
     * Interpret JS code.
     * 
     * @param object $_oFile \Iiigel\Model\File object to base the interpretation on
     */
    public function interpret($_oFile) {
    	$sCode = '<script src="'.URL.'res/script/interpreter/interpreter-javascript.js"></script>';
    	
    	if ($_oFile->bFilesystem) {
    		$sCode .= '<script>'.$_oFile->sFile.'</script>';
    	} else {
    		$aFile = explode(';', $_oFile->sFile);
        	$sFileUrl = $aFile[1];
    		
    		$sCode .= '<script src="'.$sFileUrl.'"></script>';
    	}
    	
    	return $sCode;
    }
}

?>