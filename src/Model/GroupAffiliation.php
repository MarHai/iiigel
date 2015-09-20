<?php namespace Iiigel\Model;

class GroupAffiliation extends \Iiigel\Model\Affiliation {
    const TABLE = 'user2group';
    
    /**
     * Load list of all entries, no matter of the current one.
     * 
     * @param  string _sHashId hashed ID of group (!)
     * @return object oDb result object
     */
    public function getList($_sHashId = NULL) {
        return $GLOBALS['oDb']->query('SELECT a.*, b.sName AS sUser, c.sName AS sModule, d.sName AS sChapter
FROM user2group a, `user` b, module c, chapter d
WHERE 
	NOT a.bDeleted 
	AND a.nIdGroup = (SELECT nId FROM `group` WHERE sHashId = '.$GLOBALS['oDb']->escape($_sHashId).') 
	AND (a.nStart IS NULL OR a.nStart = 0 OR a.nStart < UNIX_TIMESTAMP()) AND (a.nEnd IS NULL OR a.nEnd = 0 OR a.nEnd > UNIX_TIMESTAMP())
	AND b.nId = a.nIdUser
	AND c.nId = a.nIdModule
	AND d.nId = a.nIdChapter
ORDER BY sUser ASC');
    }
    
    /**
     * Runs through all columns (via ->get()) and returns the loaded entry (so far).
     * 
     * @param boolean $_bIncludeConfigColumns if true, really all columns are included
     * @return array   key/value pairing of all columns except CONFIG ones
     */
    public function getCompleteEntry($_bIncludeConfigColumns = FALSE) {
        if($_bIncludeConfigColumns) {
            return parent::getCompleteEntry(TRUE);
        } else {
            return array(
                'sUser' => $this->sUser,
                'bAdmin' => $this->bAdmin,
                'sModule' => $this->sModule,
                'sChapter' => $this->sChapter
            );
        }
    }
}

?>