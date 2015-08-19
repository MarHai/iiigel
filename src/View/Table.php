<?php namespace Iiigel\View;

class Table extends \Iiigel\View\DefaultView {
    private $mHeadline = NULL;
    private $aHead = array();
    private $aData = array();
    private $aFoot = array();
    private $aParam = array();
    private $aCssClass = array('table', 'table-striped', 'table-condensed', 'table-hover');
    private $sRowClick = NULL;
    private $sHeadline = NULL;
    
    /**
     * Setup table that is either returned as HTML table or as JSON object.
     * 
     * @param array data array, if set, table is set directly with array keys as headlines, and returned
     */
    public function __construct($_aData = array()) {
        parent::__construct();
        if(count($_aData) > 0) {
            $this->setData($_aData, TRUE);
        }
    }
    
    /**
     * Replace all current data within table with new data.
     * 
     * @param  array   $_aData                    rows of data
     * @param  boolean [$_bUseKeysAsHead          = FALSE] if true, array keys are used as headlines (may be changed, though)
     * @return integer number of rows after adding data (excl. headline/footer)
     */
    public function setData($_aData, $_bUseKeysAsHead = FALSE) {
        $this->aData = $_aData;
        if($_bUseKeysAsHead) {
            if(count($_aData) > 0) {
                $this->setHeader(array_keys(array_pop($_aData)), TRUE);
            }
        }
        return $this->length;
    }
    
    /**
     * Add a headline above the table.
     * 
     * @param string  $_sHeadline            text of headline
     */
    public function addHeadline($_sHeadline) {
        $this->sHeadline = trim($_sHeadline);
    }
    
    /**
     * Add data to the bottom of the current table.
     * 
     * @param  array   $_aData rows to add
     * @return integer (new) number of rows within table
     */
    public function addData($_aData) {
        reset($_aData);
        foreach($_aData as $aRow) {
            $this->aData[] = $aRow;
        }
        return $this->length;
    }
    
    /**
     * Set headlines.
     * 
     * @param array   $_aHead       headlines, one at each position. If you need colspan, set the cells not-existent to NULL.
     * @param boolean $_bAutoHeader if auto-heading is set, column names are taken and, thus, a little optimized (camel-case split, dismiss first character)
     * @return integer amount of columns
     */
    public function setHeader($_aHead, $_bAutoHeader = FALSE) {
        $this->mHeadline = array();
        reset($_aHead);
        foreach($_aHead as $mHeadline) {
            if(!is_object($mHeadline) && !is_array($mHeadline)) {
                if($_bAutoHeader) {
                    $mHeadline = $this->makeCamelCaseNicer($mHeadline);
                }
                $this->mHeadline[] = $mHeadline;
            }
        }
        return $this->width;
    }
    
    /**
     * Add a row of data to the <thead /> section, below the headlines (set through setHeader).
     * 
     * @param  array   $_aData single row of data
     * @return integer number of rows within thead after adding this one (incl. headlines)
     */
    public function addHeaderRow($_aData) {
        reset($_aData);
        foreach($_aData as $aRow) {
            $this->aHead[] = $aRow;
        }
        return $this->length_head;
    }
    
    /**
     * Add a row of data to the <tfoot /> section.
     * 
     * @param  array   $_aData single row of data
     * @return integer number of rows within tfoot after adding this one
     */
    public function addFooterRow($_aData) {
        reset($_aData);
        foreach($_aData as $aRow) {
            $this->aFoot[] = $aRow;
        }
        return $this->length_foot;
    }
    
    /**
     * Read table meta data.
     * 
     * @param  string  $_sName one of length (number of data rows), length_head (number of rows within <thead />), length_foot (rows within <tfoot />), width (number of columns)
     * @return integer numeric value corresponding to the requested data, NULL if option not found
     */
    public function __get($_sName) {
        switch(strtolower(trim($_sName))) {
            case 'length':
                return count($this->aData);
            case 'length_head':
                return count($this->aHead) + (is_null($this->mHeadline) ? 0 : 1);
            case 'length_foot':
                return count($this->aFoot);
            case 'width':
                if(is_null($this->mHeadline)) {
                    if($this->length == 0) {
                        return 0;
                    } else {
                        return count(end($this->aData));
                    }
                } else {
                    return count($this->mHeadline);
                }
            default:
                return NULL;
        }
    }
    
    /**
     * Add or remove an element from $this->aCssClass.
     * 
     * @param string  $_sName             CSS class to be added/removed
     * @param boolean [$_bInclude         = TRUE] if true, it is added (uniquely once); otherwise it is removed
     */
    private function toggleCss($_sName, $_bInclude = TRUE) {
        if($_bInclude) {
            if(!in_array($_sName, $this->aCssClass)) {
                $this->aCssClass[] = $_sName;
            }
        } else {
            if(($mPos = array_search($_sName, $this->aCssClass)) !== FALSE) {
                unset($this->aCssClass[$mPos]);
            }
        }
    }
    
    /**
     * Set Bootstrap table CSS classes or remove them.
     * 
     * @param string  $_sName  one of striped, condensed, ###
     * @param boolean $_bValue true if CSS class should be set, false if it should be removed
     */
    public function __set($_sName, $_mValue) {
        switch(strtolower(trim($_sName))) {
            case 'striped':
                $this->toggleCss('table-striped', $_mValue ? TRUE : FALSE);
                break;
            case 'hoverable':
                $this->toggleCss('table-hover', $_mValue ? TRUE : FALSE);
                break;
            case 'bordered':
                $this->toggleCss('table-border', $_mValue ? TRUE : FALSE);
                break;
            case 'compact':
                $this->toggleCss('table-condensed', $_mValue ? TRUE : FALSE);
                break;
            default:
                //set other parameters for <table /> tag
                $this->aParam[$_sName] = $_mValue;
                break;
        }
    }
    
    /**
     * Output depends on AJAX mode. If in AJAX mode, a JSON object is returned with all data necessary in order to build table in JavaScript. If not in AJAX mode HTML code is returned.
     * 
     * @return string JSON string or HTML code (depending on $GLOBALS['bAjax'])
     */
    public function render() {
        $aHeadCombination = array_reverse($this->aHead);
        $aHeadCombination[] = $this->mHeadline;
        if($GLOBALS['bAjax']) {
            return json_encode(array_merge(
                $this->aParam,
                array(
                    'aHead' => array_reverse($aHeadCombination),
                    'aData' => $this->aData,
                    'aFoot' => $this->aFoot,
                    'aCss' => $this->aCssClass
                )
            ));
        } else {
            $oTwigLoader = new \Twig_Loader_Filesystem(PATH_DIR.'res/tmpl/');
            $oTwig = new \Twig_Environment($oTwigLoader, array(
                'cache' => PATH_DIR.'res/tmpl/cache/',
                'debug' => $GLOBALS['aConfig']['bDebug'],
                'auto_reload' => $GLOBALS['aConfig']['bDebug']
            ));
            $oTwig->addExtension(new \Twig_Extensions_Extension_I18n());
            return $oTwig->render('table.html', array(
                'aHead' => array_reverse($aHeadCombination),
                'aData' => $this->aData,
                'aFoot' => $this->aFoot,
                'aCss' => $this->aCssClass,
                'aParam' => $this->aParam,
                'nLength' => $this->length,
                'nWidth' => $this->width,
                'sRowClick' => (strpos($this->sRowClick, '%s') === FALSE ? ($this->sRowClick.'%s') : $this->sRowClick),
                'sHeadline' => $this->sHeadline,
                'nHeadlineLevel' => $this->nHeadlineLevel
            ));
        }
    }
    
    /**
     * Makes table interactive (searchable, sortable, pageable). As JS needs to be included this function requires a reference to the parent page.
     * 
     * @param \Iiigel\View\Page &$_oPage reference to the parent page
     */
    public function makeInteractive(\Iiigel\View\Page &$_oPage) {
        //$_oPage->addExternal('');
        $this->toggleCss('Iiigel-interactive');
    }
    
    /**
     * Set a link in which %s gets replaced (or it gets appended) with the current sHashId. For editing/deleting purposes.
     * 
     * @param string $_sLink link, without URL in the beginning, either including a %s or ready to be appended with hashed ID
     */
    public function onRowClick($_sLink) {
        $this->sRowClick = $_sLink;
    }
}

?>