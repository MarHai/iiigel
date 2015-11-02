<?php namespace Iiigel\Model;

class Folder extends \Iiigel\Model\File {
    protected $aChild = NULL;
    
    /**
     * Access id (->nId) or any other data (->NAME).
     * 
     * @param  string $_sName data param name
     * @return mixed  depending on parameter; NULL if not set
     */
    public function __get($_sName) {
        if($_sName == 'aChildren') {
            if($this->aChild === NULL) {
                $this->aChild = $this->oCloud->get($this->sHashId);
            }
            return $this->aChild;
        } elseif($_sName == 'sSize') {
            //'##################### neeeds rework
            return sprintf(_('cloud.countfiles'), count($this->aChildren));
        } else {
            return parent::__get($_sName);
        }
    }
    
    /**
     * Sets the current object's data. Ignores some reserved fields (nId, nCreate, etc.) but uses all other fields submitted.
     * 
     * @param array   $_aData user data
     * @return integer number of data values set (in this process only)
     */
    public function setData($_aData) {
        if(isset($_aData['sName']) && isset($_aData['sType']) && $_aData['sType'] === 'root') {
            $_aData['sName'] = sprintf(_('cloud.root'), $this->oCloud->oUser->sName);
            $_aData['sType'] = 'folder';
        }
        return parent::setData($_aData);
    }
    
    /**
     * Runs through all columns (via ->get()) and returns the loaded entry (so far).
     * 
     * @param boolean $_bIncludeConfigColumns if true, really all columns are included
     * @return array   key/value pairing of all columns except CONFIG ones
     */
    public function getCompleteEntry($_bIncludeConfigColumns = FALSE) {
        $aData = parent::getCompleteEntry($_bIncludeConfigColumns);
        $aData['aChildren'] = $this->aChildren;
        return $aData;
    }

    /**
     * Deletes current row.
     * 
     * @return boolean true if successfully deleted, false otherwise
     */
    public function delete() {
    	if (parent::delete()) {
    		foreach ($this->aChildren as $oChild) {
    			$oChild->delete();
    		}
    		
    		return TRUE;
    	} else {
    		return FALSE;
    	}
    }

}

?>
