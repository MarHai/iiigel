<?php namespace Iiigel\View\Interpreter;

class Html extends \Iiigel\View\Interpreter\DefaultInterpreter {
    /**
     * Interpret HTML code.
     * 
     * @param object $_oFile \Iiigel\Model\File object to base the interpretation on
     */
    public function interpret($_oFile) {
        if ($_oFile->bFilesystem) {
        	$aFile = explode(';', $_oFile->sFile);
        	$sFileUrl = $aFile[1];
        	
        	$sCode = '<iframe style="margin: 0; width: 100%; border: none; overflow: hidden;" scrolling="no" src="'.$sFileUrl.'" onload="adjustIframeHeight(this);"></iframe>';
        	$sCode .= '<script src="'.URL.'res/script/interpreter/interpreter-html.js"></script>';
        	
        	return $sCode;
        } else {
        	return $_oFile->sFile;
        }
    }
}

?>