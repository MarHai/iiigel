<?php namespace Iiigel\Controller\Admin;

class Right extends \Iiigel\Controller\Admin\DefaultController {
    const DEFAULT_ACTION = 'showList';
    const TABLE = 'right';
    const SHOW_RIGHTS = FALSE;
    
    /**
     * Redirect to the page given. Use relative URL from URL constant onwards (without initial slash).
     * 
     * @param string $_sUrl relative URL to redirect to
     */
    public function redirect($_sUrl) {
        parent::redirect($this->sPreviousUrl === NULL ? $_sUrl : $this->sPreviousUrl);
    }
}

?>