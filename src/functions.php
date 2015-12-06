<?php

/**
 * Hardcoded debug function that does not rely on any other functions (i.e., template libraries).
 * May be used anywhere, anytime.
 * 
 * @param  mixed    $_mVar                           the variable to debug
 * @param  string   [$_sTitle                        = '']                  a title to headline the debug
 * @param  boolean  [$_bReturnInsteadOfEcho          = FALSE] TRUE if no echo but a return should be initiated
 * @return string if $_bReturnInsteadOfEcho is TRUE then a HTML output is returned, otherwise nothing
 */
function debug($_mVar, $_sTitle = '', $_bReturnInsteadOfEcho = FALSE) {
    $sOutput = '<div class="debug">';
    if($_sTitle != '') {
        $sOutput .= '<h1>'.$_sTitle.'</h1>';
    }
    
    if(is_object($_mVar)) {
        $sOutput .= '<p><code>object ('.get_class($_mVar).')</code></p>';
    } elseif(is_array($_mVar)) {
        $sOutput .= '<p><code>array</code></p>';
        $sOutput .= '<table class="table table-striped table-condensed table-hover">';
        foreach($_mVar as $sKey => $mValue) {
            $sOutput .= '<tr><th>'.$sKey.'</th><td>'.debug($mValue, '', TRUE).'</td></tr>';
        }
        $sOutput .= '</table>';
    } else {
        $sOutput .= '<code>'.gettype($_mVar).'</code>';
        $sOutput .= '<p>|'.$_mVar.'|</p>';
    }
    
    $sOutput .= '</div>';
    
    if($_bReturnInsteadOfEcho) {
        return $sOutput;
    } else {
        $GLOBALS['sDebugOutput'] .= $sOutput;
    }
}

/**
 * Generic exception catch function for exceptions not explicetly handled.
 * 
 * @param object  $_oException exception to be catched
 * @param boolean if           true, only debug array is returned (necessary for inner exceptions as recursive method
 * @return mixed   none (normally) or debug array
 */
function except($_oException, $_bOnlyReturnDebugArray = FALSE) {
    $sDebug = '';
    if($GLOBALS['aConfig']['bDebug']) {
        if($_bOnlyReturnDebugArray) {
            return method_exists($_oException, 'getMessage') ? array(
                    'oError' => $_oException,
                    'sError' => $_oException->getMessage(),
                    'oPreviousError' => $_oException->getPrevious(),
                    'sTraceback' => except_splitTracebackIntoList($_oException->getTraceAsString())
                ) : NULL;
        } else {
            $sDebug = debug(
                array(
                    'oError' => $_oException,
                    'sError' => $_oException->getMessage(),
                    'oPreviousError' => except($_oException->getPrevious(), TRUE),
                    'sTraceback' => except_splitTracebackIntoList($_oException->getTraceAsString())
                ), 
                'Exception',
                TRUE
            );
        }
    }
    try {
        $oView = new \Iiigel\View\Page();
        $oView->loadTemplate('static/error.html');
        $oView->sErrorMessage = $_oException->getMessage();
        die($oView->render().$sDebug.$GLOBALS['sDebugOutput']);
    } catch(\Exception $oError) {
        echo $GLOBALS['sDebugOutput'];
        echo 'Error: '.$_oException->getMessage();
        die('Exceptional error for the format of this message: '.$oError->getMessage());
    }
}

/**
 * Convert traceback string (from error msg.) into a styled list for better overview.
 * 
 * @param  string $_sTraceback original traceback string
 * @return string HTML code of list
 */
function except_splitTracebackIntoList($_sTraceback) {
    $aTraceback = explode(chr(10), str_replace(chr(13), chr(10), str_replace(chr(13).chr(10), chr(10), $_sTraceback)));
    $sReturn = '<ul class="list-unstyled">';
    foreach($aTraceback as $sStep) {
        $sReturn .= '<li>'.$sStep.'</li>';
    }
    $sReturn .= '</ul>';
    return $sReturn;
}

?>