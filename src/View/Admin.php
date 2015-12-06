<?php namespace Iiigel\View;

class Admin extends \Iiigel\View\Page {
    public function __construct() {
        parent::__construct();
        if(!$this->bAdmin && !$this->bInstitutionAdmin && !$this->bModuleAdmin && !$this->bGroupAdmin) {
            throw new \Exception(_('error.permission'));
        }
        $this->loadTemplate('page.html');
    }
}