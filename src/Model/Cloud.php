<?php namespace Iiigel\Model;

class Cloud {
    public $oUser = NULL;
    
    /**
     * Setup new cloud based on user given or currently logged in user if not explicitely given.
     * 
     * @param mixed $_mIdUser user ID or NULL (in the latter case, take currently logged in user)
     */
    public function __construct($_mIdUser = NULL) {
        if($_mIdUser === NULL && isset($GLOBALS['oUserLogin'])) {
            $this->oUser = $GLOBALS['oUserLogin'];
        } else {
        //################ ACHTUNG: hier muss noch geprüft werden, ob der aktuell eingeloggte user das denn sehen darf
            $this->oUser = new \Iiigel\Model\User($_mIdUser);
        }
    }
    
    /**
     * Creates a new file (without content at this point).
     * 
     * @param  string  $_sName         new file's name
     * @param  mixed   $_mFolderParent if folder object, then file is created in this folder; if null, file is created in user's root dir.
     * @return boolean TRUE if successfully created
     */
    public function createFile($_sName, $_mFolderParent = NULL) {
        return TRUE;
    }
    
    /**
     * Checks whether a file is allowed to be opened by current user and returns \Iiigel\Model\File if appropriate.
     * Also sets file to be opened within database.
     * 
     * @param  string $_sHashId file's hashed ID (within cloud table)
     * @return object Iiigel/Model/File object
     */
    public function loadFile($_sHashId) {
    	// WARNING: intval($_sHashId) is WRONG ( please correct this to load with hashed id )
        $oFile = new \Iiigel\Model\File(intval($_sHashId), $this);
        $oFile->bOpen = TRUE;
        $oFile->update();
        return $oFile;
    }
    
    /**
     * Checks whether a file is allowed to be opened by current user and closes it if appropriate. Returns TRUE if successful (= allowed and database update was successful).
     * 
     * @param  string  $_sHashId file's hashed ID (within cloud table)
     * @return boolean  TRUE if successful
     */
    public function closeFile($_sHashId) {
        $oFile = new \Iiigel\Model\File($_sHashId, $this);
        $oFile->bOpen = FALSE;
        return $oFile->update();
    }
    
    /**
     * Creates a new folder.
     * 
     * @param  string  $_sName         new folder's name
     * @param  mixed   $_mFolderParent if folder object, then file is created in this folder; if null, file is created in user's root dir.
     * @return boolean TRUE if successfully created
     */
    public function createFolder($_sName, $_mFolderParent = NULL) {
        return TRUE;
    }
    
    /**
     * Get cloud structure incl. all sub files/folders.
     * Loads either the complete cloud (if no param given or param set to NULL) or starting from a specific folder.
     * Method returns "meta data" only, that is, no files' content is returned but only all other data.
     * 
     * @param  mixed [$_mFolderHashId         = NULL] if set and is valid dir, then returned structure starts from that point. if set to NULL, complete cloud (for current user) is returned.
     * @return array \Iiigel\Model\Folder and \Iiigel\Model\File objects returned (in adequate order) in an array
     */
    public function get($_mFolderHashId = NULL) {
        if($_mFolderHashId === NULL) {
            return array(new \Iiigel\Model\Folder('clJLjlLDFkjL(JKkj', $this));
        } else {
            switch($_mFolderHashId) {
                case 'cLljsl88lsdfY:':
                    return array(new \Iiigel\Model\File('cL(FJL(§Jdf8', $this));
                case 'cLljskjdfdfY:':
                    return array(
                        new \Iiigel\Model\Folder('cLljskksl(fY:', $this),
                        new \Iiigel\Model\File('cLoOoOoOf8kd', $this)
                    );
                case 'clJLjlLDFkjL(JKkj':
                    return array(
                        new \Iiigel\Model\Folder('cLljsl88lsdfY:', $this),
                        new \Iiigel\Model\Folder('cLljskjdfdfY:', $this),
                        new \Iiigel\Model\File('cNTQxZTg4MWNkNmY:', $this),
                        new \Iiigel\Model\File('cNTQxZkd8lsdfY:', $this)
                    );
                default:
                    return array();
            }
        }
    }
    
    /**
     * Checks whether allowed and, if so, changes a file's/folder's name.
     * 
     * @param  string  $_sHashId  file or folder hash ID (within cloud table)
     * @param  string  $_sNewName new name to be given
     * @return boolean TRUE if successful, FALSE otherwise
     */
    public function rename($_sHashId, $_sNewName) {
        return TRUE;
    }
}

?>
