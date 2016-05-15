<?php namespace Iiigel\Model;

class GroupAffiliation extends \Iiigel\Model\Affiliation {
    const TABLE = 'user2group';
    const CONFIG_COLUMN = array('nCreate', 'nUpdate', 'nIdCreator', 'nIdUpdater');

	const MODE_MEMBER = 1;
	const MODE_LEADER = 2;
	const MODE_MODULE = 3;
	const MODE_POSSIBLE = 4;

	protected function changesAllowed() {
		if (parent::changesAllowed()) {
			return TRUE;
		} else
		if (isset($GLOBALS['oUserLogin'])) {
			return $GLOBALS['oDb']->count($GLOBALS['oDb']->query('SELECT * FROM `user2group` WHERE nIdGroup = '.$this->nIdGroup.' AND nIdUser = '.$GLOBALS['oUserLogin']->nId.' AND bAdmin AND NOT bDeleted')) > 0;
		} else {
			return FALSE;
		}
	}
    
    /**
     * Load list of all entries, no matter of the current one.
     * 
     * @param  string _sHashId hashed ID of group (!)
     * @return object oDb result object
     */
    public function getList($_sHashId = NULL, $_nMode = NULL) {
        switch ($_nMode) {
			case $this::MODE_MEMBER:
				return $GLOBALS['oDb']->query('SELECT a.*, b.nIdModule AS nIdModule, b.sHashId AS sHashIdU2G
					FROM `user` a, `user2group` b
					WHERE
						NOT b.bDeleted
						AND NOT a.bDeleted
						AND a.nId = b.nIdUser
						AND b.nIdGroup = (SELECT nId FROM `group` WHERE sHashId = '.$GLOBALS['oDb']->escape($_sHashId).')
						AND NOT b.bAdmin
					ORDER BY a.sName ASC');
			case $this::MODE_LEADER:
				return $GLOBALS['oDb']->query('SELECT a.*, b.sHashId AS sHashIdU2G FROM `user` a, `user2group` b 
					WHERE
						NOT b.bDeleted
						AND NOT a.bDeleted
						AND a.nId = b.nIdUser
						AND b.nIdGroup = (SELECT nId FROM `group` WHERE sHashId = '.$GLOBALS['oDb']->escape($_sHashId).')
						AND b.bAdmin
					ORDER BY a.sName ASC');
			case $this::MODE_MODULE:
				return $GLOBALS['oDb']->query('SELECT a.* FROM `module` a, `module2group` b 
					WHERE
						NOT b.bDeleted
						AND NOT a.bDeleted
						AND b.nIdModule = a.nId
						AND b.nIdGroup = (SELECT nId FROM `group` WHERE sHashId = '.$GLOBALS['oDb']->escape($_sHashId).')
					ORDER BY a.sName ASC');
			case $this::MODE_POSSIBLE:
				return $GLOBALS['oDb']->query('SELECT a.*
					FROM `user` a, `user2institution` b
					WHERE
						NOT a.bDeleted
						AND NOT b.bDeleted
						AND b.nIdInstitution = (SELECT nIdInstitution FROM `group` WHERE sHashId = '.$GLOBALS['oDb']->escape($_sHashId).')
						AND (b.nStart IS NULL OR b.nStart = 0 OR b.nStart < UNIX_TIMESTAMP()) AND (b.nEnd IS NULL OR b.nEnd = 0 OR b.nEnd > UNIX_TIMESTAMP())
						AND a.nId = b.nIdUser
					ORDER BY a.sName ASC');
			default:
				return $GLOBALS['oDb']->query('SELECT a.*, b.sName AS sUser, c.sName AS sModule, d.sName AS sChapter
					FROM user2group a, `user` b, module c, chapter d
					WHERE 
						NOT a.bDeleted 
						AND a.nIdGroup = (SELECT nId FROM `group` WHERE sHashId = '.$GLOBALS['oDb']->escape($_sHashId).') 
						AND (a.nStart IS NULL OR a.nStart = 0 OR a.nStart < UNIX_TIMESTAMP()) AND (a.nEnd IS NULL OR a.nEnd = 0 OR a.nEnd > UNIX_TIMESTAMP())
						AND b.nId = a.nIdUser
						AND ((c.nId = a.nIdModule AND d.nId = a.nIdChapter) OR (a.nIdModule = 0 AND a.nIdChapter = 0 AND c.nId = (SELECT MIN(nId) FROM `module`) AND d.nId = (SELECT MIN(nId) FROM `chapter`)))
					ORDER BY sUser ASC');
		}
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
                'sModule' => $this->nIdModule == 0? '-' : $this->sModule,
                'sChapter' => $this->nIdChapter == 0? '-' : $this->sChapter
            );
        }
    }
}

?>
