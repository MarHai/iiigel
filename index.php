<?php
/**
 * i³gel || iiigel
 * (c) 2015 - date('Y')
 * 
 * Mario Haim <mario@haim.it>
 */

error_reporting(E_ALL);
session_start();

$GLOBALS['dInit'] = microtime(TRUE);
$GLOBALS['sDebugOutput'] = '';

define('VERSION', '0.1');
define('PATH_DIR', __DIR__.'/');

require_once(PATH_DIR.'config/main.php');
require_once(PATH_DIR.'vendor/autoload.php');
require_once(PATH_DIR.'src/functions.php');

set_exception_handler('except');

date_default_timezone_set($GLOBALS['aConfig']['sTimeZone']);

define('PATH_URL', ((isset($GLOBALS['aConfig']['sRootPath']) && $GLOBALS['aConfig']['sRootPath'] !== '' && $GLOBALS['aConfig']['sRootPath'] !== '/')? (($GLOBALS['aConfig']['sRootPath']{0} == ':')?($GLOBALS['aConfig']['sRootPath'].'/'):('/'.$GLOBALS['aConfig']['sRootPath'].'/')):'/'));
define('URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] ? 'https://' : 'http://').$_SERVER['SERVER_NAME'].PATH_URL);

$GLOBALS['bAutoPermission'] = false;
$GLOBALS['aRequest'] = array_merge_recursive($_POST, $_GET);
$GLOBALS['bAjax'] = isset($GLOBALS['aRequest']['c']) ? TRUE : FALSE;
require_once(PATH_DIR.'config/language.php');
$GLOBALS['sLanguage'] = $GLOBALS['aConfig']['aLanguage']['sDefaultCountry'];
$GLOBALS['sDomain'] = $GLOBALS['aConfig']['aLanguage']['sDefaultDomain'];
$GLOBALS['oDb'] = new \Iiigel\Generic\Database();

\Iiigel\Model\User::checkLogin();

//find controller, action, and action params
$aParam = array();
require_once(PATH_DIR.'config/controller-dict.php');
if($GLOBALS['bAjax']) {
    $GLOBALS['aRequest']['c'] = '\\Iiigel\\Controller\\'.$GLOBALS['aRequest']['c'];
} else {
    $GLOBALS['aRequest']['c'] = isset($GLOBALS['oUserLogin']) ? '\\Iiigel\\Controller\\Dashboard' : '\\Iiigel\\Controller\\StaticPage';
    if(isset($GLOBALS['aRequest']['path'])) {
        $aTemp = explode('/', $GLOBALS['aRequest']['path']);
        for($i = 0; $i < count($aTemp); $i++) {
            if(isset($GLOBALS['aConfig']['aController'][$aTemp[$i]])) {
                $aTemp[$i] = $GLOBALS['aConfig']['aController'][$aTemp[$i]];
            }
        }
        for($i = count($aTemp) - 1; $i >= 0; $i--) {
            $sController = '\\Iiigel\\Controller\\'.implode('\\', array_slice($aTemp, 0, ($i+1)));
            if(class_exists($sController)) {
                $GLOBALS['aRequest']['c'] = $sController;
                $i++;
                if(isset($aTemp[$i])) {
                    if(method_exists($sController, $aTemp[$i])) {
                        $GLOBALS['aRequest']['a'] = $aTemp[$i];
                    } else {
                        $aParam[] = $aTemp[$i];
                    }
                    for($j = $i+1; $j < count($aTemp); $j++) {
                        $aParam[] = $aTemp[$j];
                    }
                }
                break;
            }
        }
    }
}

if(class_exists($GLOBALS['aRequest']['c'])) {
    $oApp = new $GLOBALS['aRequest']['c']();

    //find action
    if(!isset($GLOBALS['aRequest']['a']) || !method_exists($oApp, $GLOBALS['aRequest']['a'])) {
        $GLOBALS['aRequest']['a'] = $GLOBALS['aRequest']['c']::DEFAULT_ACTION;
    }
    
    //find additional action params
    $oReflection = new \ReflectionMethod($oApp, $GLOBALS['aRequest']['a']);
    $aMethodParam = $oReflection->getParameters();
    for($i = 0; $i < count($aMethodParam); $i++) {
        $aMethodParam[$i] = $aMethodParam[$i]->name;
        if(!isset($aParam[$i]) && isset($GLOBALS['aRequest'][$aMethodParam[$i]])) {
            $aParam[$i] = $GLOBALS['aRequest'][$aMethodParam[$i]];
        }
    }

    //log action for currently logged-in user
    if(isset($GLOBALS['oUserLogin'])) {
        $GLOBALS['oUserLogin']->action();
    }
	
    call_user_func_array(array($oApp, $GLOBALS['aRequest']['a']), $aParam);
	
    echo $oApp->output();
}

//debug, depending on call format
if(!$GLOBALS['bAjax'] && $GLOBALS['aConfig']['bDebug']) {
    $aParam = $GLOBALS['aRequest'];
    unset($aParam['a'], $aParam['c']);
    debug(
        array(
            _('debug.executiontime') => microtime(TRUE) - $GLOBALS['dInit'],
            _('debug.version') => VERSION,
            _('debug.url') => URL,
            _('debug.urlpath') => PATH_URL,
            _('debug.basepath') => PATH_DIR,
            _('debug.controller') => $GLOBALS['aRequest']['c'],
            _('debug.action') => $GLOBALS['aRequest']['a'],
            _('debug.params') => $aParam,
            _('debug.session') => session_id(),
            _('debug.loggedinuser') => isset($GLOBALS['oUserLogin']) ? $GLOBALS['oUserLogin'] : NULL
        ), 
        _('debug.pagegenerationstats')
    );
}

echo $GLOBALS['sDebugOutput'];

?>
