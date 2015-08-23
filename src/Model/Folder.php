<?php namespace Iiigel\Model;

class Folder extends \Iiigel\Model\GenericModel {
    const TABLE = 'cloud';
    const DEFAULT_ORDER = 'sName ASC';
    
    /**
     * No loading of list available here --> refers to Iiigel\Model\Cloud
     */
    public function getList() {
        throw new \Exception(_('error.usecloudforfilelist'));
    }
}

?>