<?php namespace Iiigel\Controller;

abstract class DefaultController {
    const DEFAULT_ACTION = '';
    private $oView = NULL;
    
    /**
     * Takes data and returns ready-for-output data.
     * Depending on action and AJAX state, this might be HTML code or JSON data.
     * 
     * @return string JSON-ready string or HTML data
     */
    public function output() {
        return '';
    }
    
    /**
     * Redirect to the page given. Use relative URL from URL constant onwards (without initial slash).
     * 
     * @param string $_sUrl relative URL to redirect to
     */
    public function redirect($_sUrl) {
        header('location: '.((substr($_sUrl, 0, 7) == 'http://' || substr($_sUrl, 0, 8) == 'https://') ? '' : URL).$_sUrl);
        exit;
    }
}

?>