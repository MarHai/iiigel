<?php namespace Iiigel\View;

class Page extends \Iiigel\View\DefaultView {
    private $oTemplate = NULL;
    private $aMarker = array(
        'sUrl' => URL,
        'sBase' => PATH_URL,
        'aContent' => array(),
        'bLogin' => FALSE,
        'bModuleMode' => FALSE,
        'bAdmin' => FALSE,
        'sPage' => '/',
        'sCurrentUrl' => URL,
        'bDashboardNavShown' => TRUE
    );
    private $aCss = array();
    private $aJs = array();
    private $oTwig = NULL;
    
    /**
     * Display a single page including JS and CSS code, HTML header and the like.
     * Use $this->sTitle for page title adaptation.
     */
    public function __construct() {
        parent::__construct();
        $this->sTitle = _('app.name').' v.'.VERSION;
        $oTwigLoader = new \Twig_Loader_Filesystem(PATH_DIR.'res/tmpl/');
        $this->oTwig = new \Twig_Environment($oTwigLoader, array(
            'cache' => PATH_DIR.'res/tmpl/cache/',
            'debug' => $GLOBALS['aConfig']['bDebug'],
            'auto_reload' => $GLOBALS['aConfig']['bDebug']
        ));
        $this->oTwig->addExtension(new \Twig_Extensions_Extension_I18n());
        $this->bLogin = \Iiigel\Model\User::checkLogin();
        $this->bAdmin = $this->bLogin ? $GLOBALS['oUserLogin']->bAdmin : FALSE;
        if($this->bLogin) {
            $this->bDashboardNavShown = $GLOBALS['oUserLogin']->bDashboardNavShown;
        }
        $this->sUserHash = $this->bLogin ? md5(strtolower(trim($GLOBALS['oUserLogin']->sMail))) : FALSE;
        $this->sCurrentUrl = URL.(isset($GLOBALS['aRequest']['path']) ? $GLOBALS['aRequest']['path'] : '');
        $this->aCurrentUser = array(
            'sMail' => ($this->bLogin ? $GLOBALS['oUserLogin']->sMail : ''),
            'sName' => ($this->bLogin ? $GLOBALS['oUserLogin']->sName : '')
        );
        $this->aCountry = $GLOBALS['aConfig']['aLanguage']['aCountry'];
        $this->aDomain = $GLOBALS['aConfig']['aLanguage']['aDomain'];
        $this->sCountry = $GLOBALS['sLanguage'];
        $this->sDomain = $GLOBALS['sDomain'];
        $this->sDateFormatJs = $GLOBALS['aConfig']['sDateFormatJs'];
        $this->sDateFormatPhp = $GLOBALS['aConfig']['sDateFormatPhp'];
        if($this->bLogin) {
            $this->bInstitutionAdmin = count($GLOBALS['oUserLogin']->getInstitutions(TRUE, TRUE)) > 0;
            $this->bGroupAdmin = $this->bInstitutionAdmin || count($GLOBALS['oUserLogin']->getGroups(TRUE, TRUE)) > 0;
            $this->bModuleAdmin = count($GLOBALS['oUserLogin']->getModules(TRUE, TRUE)) > 0;
            $this->aActiveModule = $GLOBALS['oUserLogin']->getModules();
        }
        $this->createJsI18nIfNeeded();
    }
    
    protected function createJsI18nIfNeeded() {
        if(!file_exists(PATH_DIR.'res/i18n/'.$this->sCountry.'/LC_MESSAGES/'.$this->sDomain.'.js')) {
            file_put_contents(
                PATH_DIR.'res/i18n/'.$this->sCountry.'/LC_MESSAGES/'.$this->sDomain.'.js',
                'function i18n() { var oLang = '.
                    \Gettext\Generators\Jed::toString(
                        \Gettext\Extractors\Mo::fromFile(PATH_DIR.'res/i18n/'.$this->sCountry.'/LC_MESSAGES/'.$this->sDomain.'.mo')
                    ).
                '; if(arguments.length == 1 || arguments[1] <= 1) return typeof(oLang.messages[arguments[0]]) !== \'undefined\' ? oLang.messages[arguments[0]][1] : arguments[0]; else return typeof(oLang.messages[arguments[0]]) !== \'undefined\' ? oLang.messages[arguments[0]][2] : arguments[0]; }'.
                'function i18n_datetime(_nTimestamp) { return moment.unix(_nTimestamp).format(\''.$GLOBALS['aConfig']['sDateFormatJsMoment'].'\') }'
            );
        }
    }
    
    /**
     * Load a template file from res/tmpl with the provided name.
     * 
     * @param  string  $_sFile filename, incl. extension, excl. path
     * @return boolean true on success, false otherwise
     */
    public function loadTemplate($_sFile) {
        try {
            $this->oTemplate = $this->oTwig->loadTemplate($_sFile);
            return TRUE;
        } catch(\Exception $oError) {
            throw new \Exception(_('error.templatenotloaded'), 0, $oError);
            return FALSE;
        }
    }
    
    /**
     * Add an external file to the template, namely a CSS or JS file.
     * The method decideds on the file's extension how to include it.
     * 
     * @param  string  $_sFile filename within res/style/ or res/script/ incl. extension or starting with / for absolute path.
     * @return boolean true on success
     */
    public function addExternal($_sFile) {
        list($sType) = explode('.', strrev($_sFile), 2);
        $sType = strtolower(strrev($sType)) == 'css' ? 'style' : 'script';
        $sFile = $_sFile{0} == '/' ? $_sFile : (PATH_DIR.'res/'.$sType.'/'.$_sFile);
        if(is_file($sFile) && is_readable($sFile)) {
            if($sType == 'style') {
                return $this->addCSScode(file_get_contents($sFile));
            } else {
                return $this->addJScode(file_get_contents($sFile));
            }
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    /**
     * Add CSS code directly.
     * 
     * @param string $_sCode code to be added; no interpretation (i.e., file loading) takes place
     * @return boolean TRUE if CSS code is not empty and thus attached to template
     */
    public function addCSScode($_sCode) {
        if($_sCode != '') {
            $this->aCss[] = $_sCode;
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    /**
     * Add JS code directly.
     * 
     * @param  string  $_sCode javascript code to be added (no interpretation takes place)
     * @return boolean TRUE if JS code is not empty and thus attached to template
     */
    public function addJScode($_sCode) {
        if($_sCode != '') {
            $this->aJs[] = $_sCode;
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    /**
     * Add any marker to the template.
     * Reserved names: sTitle (HTML document title), sUrl, sBase (base dir), aContent, nColumns
     * 
     * @param string $_sName  marker name
     * @param mixed  $_mValue marker value (for Twig interpretation)
     */
    public function __set($_sName, $_mValue) {
        if($_sName == 'aContent') {
        	$nRows = count($this->aMarker['aContent']);
        	
        	if ($nRows == 0) {
        		$this->addRow();
        		$nRows++;
        	}
        	
            $this->aMarker['aContent'][$nRows - 1][] = $_mValue;
        } else {
            $this->aMarker[$_sName] = $_mValue;
        }
    }
    
    /**
     * Get any marker from the template.
     * 
     * @param  string $_sName marker name
     * @return mixed  marker value as set before
     */
    public function __get($_sName) {
        return $this->aMarker[$_sName];
    }
    
    /**
     * Check if a marker exists/isset.
     * 
     * @param  string  $_sName marker name
     * @return boolean TRUE if marker isset
     */
    public function __isset($_sName) {
        return isset($this->aMarker[$_sName]);
    }
    
    /**
     * Adds a new row to content
     */
    public function addRow() {
    	$this->aMarker['aContent'][] = array();
    }
    
    /**
     * Renders the current template with all its markers.
     * 
     * @return string full-page HTML code
     */
    public function render() {
    	$nRows = count($this->aMarker['aContent']);
        $this->aMarker['nRows'] = $nRows;
        
        for ($j = 0; $j < $nRows; $j++) {
        	$this->aMarker['nColumns'][] = count($this->aMarker['aContent'][$j]);
        }
        
        return $this->oTemplate->render($this->aMarker);
    }
}

?>
