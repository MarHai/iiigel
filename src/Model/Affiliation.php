<?php namespace Iiigel\Model;

abstract class Affiliation extends \Iiigel\Model\GenericModel {
    const TABLE = '';
    const DEFAULT_ORDER = 'nIdUser ASC, nStart ASC, nCreate ASC';
    
    /**
     * Initiates new object which could be one of the following cases:
     * (1) set up object based on ID (integer)
     * (2) set up object based on row data including an nId
     * (3) set up new object (row) data without an ID (for login or registration)
     * (4) set up object based on hash ID (string)
     * (5) for the moment, do nothing
     * 
     * @param mixed [$_mInit         = NULL] integer if case 1, array with nId field if case 2, array without nId if case 3, NULL otherwise (4)
     */
    public function __construct($_mInit = NULL) {
        $this->nStart = NULL;
        $this->nEnd = NULL;
        parent::__construct($_mInit);
    }
    
    /**
     * Transform nId into a short hash value in order to show it to the users.
     * 
     * @return string 12-character hash value representation of nId (and some other stuff)
     */
    public function hashId() {
        list(, $sTable) = explode('2', $this::TABLE, 2);
        return '2u'.strtolower($sTable{0}).substr(parent::hashId(), 1);
    }
}

?>