<?php namespace Iiigel\Model;

class Cloud {
    /**
     * Setup new cloud based on user given or currently logged in user if not explicitely given.
     * 
     * @param mixed $_mIdUser user ID or NULL (in the latter case, take currently logged in user)
     */
    public function __construct($_mIdUser = NULL) {
    }
    
    public function openDir($_sName) {
        return new \Iiigel\Model\Folder();
    }
    
    public function loadFile($_sName) {
        return new \Iiigel\Model\File();
    }
}

?>