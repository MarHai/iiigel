<?php namespace Iiigel\Model;

class File extends \Iiigel\Model\GenericModel {
    const TABLE = 'cloud';
    const DEFAULT_ORDER = 'sName ASC';
    const CONFIG_COLUMN = array('bDeleted', 'nCreate', 'nUpdate', 'nIdCreator');
    
    protected $oCloud = NULL;
    
    /**
     * Initiates new object which could be one of the following cases:
     * (1) set up object based on ID (integer)
     * (2) set up object based on row data including an nId
     * (3) set up new object (row) data without an ID (for login or registration)
     * (4) set up object based on hash ID (string)
     * (5) for the moment, do nothing
     * In addition, the surround cloud (via ->setSurroundingCloud(...) can be set directly.
     * 
     * @param mixed [$_mInit         = NULL] integer if case 1, array with nId field if case 2, array without nId if case 3, NULL otherwise (4)
     * @param mixed [$_oCloud        = NULL] if set, surrounding cloud is set directly
     */
    public function __construct($_mInit = NULL, $_oCloud = NULL) {
        if($_oCloud !== NULL) {
            $this->setSurroundingCloud($_oCloud);
        }
        parent::__construct($_mInit);
    }
    
    /**
     * Sets the surrounding cloud.
     * 
     * @param object $_oCloud \Iiigel\Model\Cloud object
     */
    public function setSurroundingCloud($_oCloud) {
        $this->oCloud = $_oCloud;
    }
    
    /**
     * No loading of list available here --> refers to Iiigel\Model\Cloud
     */
    public function getList() {
        throw new \Exception(_('error.usecloudforfilelist'));
    }
    
    /**
     * Overwrite in order to not return file content.
     * 
     * @return array same as ->getCompleteEntry(TRUE)
     */
    public function jsonSerialize() {
        $aFile = $this->getCompleteEntry(TRUE);
        unset($aFile['sFile']);
        return $aFile;
    }
    
    /**
     * Overwrite magic get in order to serve with size.
     * 
     * @param  string $_sName data param name
     * @return mixed  depending on parameter; NULL if not set
     */
    public function __get($_sName) {
        if($_sName == 'sSize') {
            //NEEDS rework for local files
            $nSize = mb_strlen($this->sFile, 'utf8');
            $aPrefix = array('', 'K', 'M', 'G', 'T', 'P');
            $i = 0;
            while($nSize/1024 > 1 && isset($aPrefix[$i+1])) {
                $nSize /= 1024;
                $i++;
            }
            return $nSize.' '.$aPrefix[$i].'Byte';
        } else {
            return parent::__get($_sName);
        }
    }
    
    /**
     * Runs through all columns (via ->get()) and returns the loaded entry (so far).
     * 
     * @param boolean $_bIncludeConfigColumns if true, really all columns are included
     * @return array   key/value pairing of all columns except CONFIG ones
     */
    public function getCompleteEntry($_bIncludeConfigColumns = FALSE) {
        $aData = parent::getCompleteEntry($_bIncludeConfigColumns);
        $aData['sSize'] = $this->sSize;
        return $aData;
    }
}

?>