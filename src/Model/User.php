<?php namespace Iiigel\Model;

class User extends \Iiigel\Model\GenericModel {
    const TABLE = 'user';
    const DEFAULT_ORDER = 'sMail ASC';
    
    /**
     * Encode password with md5 and salt and stuff.
     * 
     * @param  string $_sPassword password to be encrypted
     * @return string encrypted password
     */
    private function encodePassword($_sPassword) {
        return md5($GLOBALS['aConfig']['sSalt'].$_sPassword);
    }
    
    public function create() {
        return $this->register();
    }
    
    /**
     * Registers a new user and sends out confirmation (double opt-in) email.
     * this->sMail and this->sPassword have to be set, this->nIdInstitution optionally.
     * 
     * @param string activation code (for group or institution), could be NULL so user does not adhere to neither of them
     * @return boolean TRUE if successful (= registered and email went out)
     */
    public function register($_sHashId = NULL) {
        if($this->sMail === NULL || $this->sPassword === NULL) {
            throw new \Exception(_('error.nodatagiven'));
            return FALSE;
        } else {
            $aRow = $GLOBALS['oDb']->getOneRow('SELECT COUNT(*) FROM `user` WHERE sMail = '.$GLOBALS['oDb']->escape($this->sMail).' AND NOT bDeleted');
            $nExistent = array_pop($aRow);
            if($nExistent > 0) {
                throw new \Exception(_('error.emailalreadytaken'));
                return FALSE;
            } else {
                if($GLOBALS['oDb']->query('INSERT INTO `user` 
                    (bDeleted, nCreate, nIdCreator, sMail, sName, sPassword, bAdmin, bMailIfOffline, bActive, sLanguage)
                    VALUES (
                        0,
                        '.standardized_time().',
                        '.(isset($GLOBALS['oUserLogin']) ? $GLOBALS['oUserLogin']->nId : 0).',
                        '.$GLOBALS['oDb']->escape(trim(strtolower($this->sMail))).',
                        '.$GLOBALS['oDb']->escape(trim(isset($this->sName) ? $this->sName : $this->sMail)).',
                        '.$GLOBALS['oDb']->escape($this->encodePassword($this->sPassword)).',
                        '.$GLOBALS['oDb']->escape(isset($this->bAdmin) ? $this->bAdmin : FALSE).',
                        '.$GLOBALS['oDb']->escape(isset($this->bMailIfOffline) ? $this->bMailIfOffline : TRUE).',
                        0,
                        '.$GLOBALS['oDb']->escape($GLOBALS['sLanguage']).'
                    )') === FALSE) {

                    throw new \Exception(_('error.create').' '.$GLOBALS['oDb']->sLastError);
                } else {
                    if($this->setId($GLOBALS['oDb']->getLastId())) {
                        if($this->load()) {
                            if($GLOBALS['oDb']->query('UPDATE `user` SET sHashId = '.$GLOBALS['oDb']->escape($this->hashId()).' WHERE nId = '.$this->nId.' LIMIT 1')) {
                                $this->addAffiliation($_sHashId);
                                $oMail = new \Iiigel\Generic\Mail();
                                try {
                                    $oMail->send(
                                        $this->sMail,
                                        _('mail.reg.subject'),
                                        sprintf(_('mail.reg.message'), $this->sHashId, (URL.'html/activate/'.$this->sHashId))
                                    );
                                    return TRUE;
                                } catch(\Exception $oException) {
                                    throw new \Exception(_('error.regmailnotsent'), 0, $oException);
                                    return FALSE;
                                }
                            } else {
                                throw new \Exception(_('error.update'));
                            }
                        } else {
                            throw new \Exception(sprintf(_('error.objectload'), 'User'));
                        }
                    } else {
                        throw new \Exception(_('error.lastidnotfound'));
                    }
                }
            }
        }
    }
    
    /**
     * Create new affiliation between current user and group or institution.
     * Check if user-group/institution affiliation already exists.
     * 
     * @param string $_sHashId either institution or group hash ID
     */
    public function addAffiliation($_sHashId) {
        if($_sHashId !== NULL && strlen($_sHashId) > 0 && $this->nId > 0) {
            $oModel = NULL;
            $aModelExistent = array();
            $oAffiliation = NULL;
            $sId = '';
            if($_sHashId{0} == 'i') {
                $oModel = new \Iiigel\Model\Institution($_sHashId);
                $aModelExistent = $this->getInstitutions();
                $oAffiliation = new \Iiigel\Model\InstitutionAffiliation();
                $sId = 'nIdInstitution';
            } elseif($_sHashId{0} == 'g') {
                $oModel = new \Iiigel\Model\Group($_sHashId);
                $aModelExistent = $this->getGroups();
                $oAffiliation = new \Iiigel\Model\GroupAffiliation();
                $sId = 'nIdGroup';
            }
            if($oModel !== NULL && $oModel->load()) {
                foreach($aModelExistent as $oModelExistent) {
                    if($oModelExistent->nId == $oModel->nId) {
                        return;
                    }
                }
                $oAffiliation->setData(array(
                    'nIdUser' => $this->nId,
                    $sId => $oModel->nId
                ));
                $oAffiliation->create();
            }
        }
    }
    
    /**
     * Activates one user based on an activation key.
     * 
     * @param  string $_sRegistrationKey key that user received
     * @return mixed  FALSE if unsuccessful, user nId from activated user otherwise
     */
    public static function activate($_sRegistrationKey) {
        $aUser = $GLOBALS['oDb']->getOneRow('SELECT * FROM `user` WHERE NOT bDeleted AND NOT bActive AND sHashId = '.$GLOBALS['oDb']->escape($_sRegistrationKey).' LIMIT 1');
        if(isset($aUser['nId'])) {
            $aUser['nId'] = intval($aUser['nId']);
            if($GLOBALS['oDb']->query('UPDATE `user` SET bActive = 1 WHERE nId = '.$aUser['nId'])) {
                return $aUser['nId'];
            }
        }
        return FALSE;
    }

    /**
     * Statuc check wether a user is logged in currently.
     * 
     * @return boolean TRUE if a user should be logged in, FALSE otherwise
     */
    public function isOnline() {
    	$nId = $this->nId;
    	$nLimitCreate = (standardized_time() - $GLOBALS['aConfig']['nMaxSessionLifetime']);
    	$nLimitLastAction = (standardized_time() - $GLOBALS['aConfig']['nMaxActionDelay']);
    	
    	$oResult = $GLOBALS['oDb']->query('SELECT nLastAction FROM `session` WHERE sSession <> \'\' AND nIdCreator = '.$nId.' AND nCreate >= '.$nLimitCreate.' AND nLastAction >= '.$nLimitLastAction.' LIMIT 1');
    	
    	if ($GLOBALS['oDb']->count($oResult) > 0) {
    		return is_array($GLOBALS['oDb']->get($oResult));
    	} else {
    		return FALSE;
    	}
    }
    
    /**
     * Statuc check wether a user is logged in currently. This is based on the current session, the session table, and the lifetime setting.
     * 
     * @return boolean TRUE if a user should be logged in (which is set on $GLOBALS['oUserLogin'], FALSE otherwise
     */
    public static function checkLogin() {
        if(isset($GLOBALS['oUserLogin'])) {
            return $GLOBALS['oUserLogin']->nId > 0;
        } else {
            try {
                $mSession = $GLOBALS['oDb']->getOneRow('SELECT * FROM `session` WHERE sSession = '.$GLOBALS['oDb']->escape(session_id()).' AND nCreate >= '.(standardized_time() - $GLOBALS['aConfig']['nMaxSessionLifetime']));
                if(is_array($mSession) && isset($mSession['nIdCreator'])) {
                    $oUser = new \Iiigel\Model\User(intval($mSession['nIdCreator']));
                    return $oUser->login();
                }
                return FALSE;
            } catch(\Exception $oError) {
                return FALSE;
            }
        }
    }
    
    /**
     * Logs in user based on username/password if one matches.
     * If parameters are NULL, current object's email/password are taken.
     * 
     * @param string  $_sMail email address (typically from _GET/_POST)
     * @param string  $_sPassword password (as well, typically from _GET/_POST), not encrypted (yet)
     * @return boolean TRUE if successful (and GLOBALS['oUserLogin'] isset), FALSE if unsuccessful, NULL if user does not exist
     */
    public function login($_sMail = NULL, $_sPassword = NULL, $_sHashId = NULL) {
        if($_sMail !== NULL && $_sPassword !== NULL) {
            //first, check if it should be a register call ...
            $aMailCount = $GLOBALS['oDb']->getOneRow('SELECT COUNT(*) AS nCount FROM `user` WHERE sMail = '.$GLOBALS['oDb']->escape($_sMail));
            if($aMailCount['nCount'] == 0) {
                return NULL;
            }
            //second, go on
            try {
                $mUser = $GLOBALS['oDb']->getOneRow('SELECT * FROM `user` WHERE sMail = '.$GLOBALS['oDb']->escape($_sMail).' AND sPassword = '.$GLOBALS['oDb']->escape($this->encodePassword($_sPassword)).' AND bActive AND NOT bDeleted LIMIT 1');
                if(is_array($mUser) && isset($mUser['nId'])) {
                    $this->setId($mUser['nId']);
                    $this->setData($mUser);
                }
            } catch(\Exception $oError) {
                return FALSE;
            }
        }
        if($this->nId > 0) {
            $GLOBALS['oUserLogin'] = $this;
            $sSession = $GLOBALS['oDb']->escape(session_id());
            if(($oResult = $GLOBALS['oDb']->query('SELECT * FROM `session` WHERE nIdCreator = '.$this->nId.' AND sSession = '.$sSession))) {
                if($GLOBALS['oDb']->count($oResult) == 0) {
                    $GLOBALS['oDb']->query('INSERT INTO `session` (nCreate, nIdCreator, sSession, nLastAction)
                        VALUES ('.standardized_time().', '.$this->nId.', '.$sSession.', '.standardized_time().')');
                }
            }
            $this->addAffiliation($_sHashId);
            return TRUE;
        }
        return FALSE;
    }
    
    /**
     * Used by ->setData and __set magic method in order to bring stuff together.
     * Allows only columns not inside CONFIG_COLUMN (except if _bSetWithoutNameChecking is true.
     * Also, if a columns has the same name as an already existent parameter but the first char is s instead of n, this is set into ->aMapping as it normally represents the foreign key show value.
     * 
     * @param  string  $_sName                             parameter name to set
     * @param  mixed   $_mValue                            value of newly set parameter
     * @param  boolean [$_bSetWithoutNameChecking          = FALSE] if TRUE, no CONFIG_COLUMN checking takes place
     * @return boolean TRUE if parameter was set correctly
     */
    protected function setParameter($_sName, $_mValue, $_bSetWithoutNameChecking = FALSE) {
        if($_sName == 'sPassword' && !$_bSetWithoutNameChecking) {
            $_mValue = $this->encodePassword($_mValue);
        }
        return parent::setParameter($_sName, $_mValue, $_bSetWithoutNameChecking);
    }
    
    /**
     * Log out currently logged-in user.
     */
    public function logout() {
        if(isset($GLOBALS['oUserLogin'])) {
            unset($GLOBALS['oUserLogin']);
            $GLOBALS['oDb']->query('UPDATE `session` SET sSession = \'\' WHERE nIdCreator = '.$this->nId.' AND sSession = '.$GLOBALS['oDb']->escape(session_id()));
        }
    }
    
    /**
     * Log current action, just for logging reasons.
     */
    public function action() {
        if(isset($GLOBALS['oUserLogin'])) {
            $GLOBALS['oDb']->query('UPDATE session SET nLastAction = '.standardized_time().', sLastAction = '.$GLOBALS['oDb']->escape(json_encode($GLOBALS['aRequest'])).' WHERE nIdCreator = '.$this->nId.' AND sSession = '.$GLOBALS['oDb']->escape(session_id()));
        }
    }
    
    /**
     * Used by GenericModel in order to find out whether changes on the current entry are allowed for the currently logged-in user.
     * 
     * @return boolean true if allowed, false otherwise
     */
    protected function changesAllowed() {
        return isset($GLOBALS['oUserLogin']) && ($this->nId == $GLOBALS['oUserLogin']->nId || $GLOBALS['oUserLogin']->bAdmin);
    }
    
    /**
     * Runs through all columns (via ->get()) and returns the loaded entry (so far).
     * 
     * @param boolean $_bIncludeConfigColumns if true, really all columns are included
     * @return array   key/value pairing of all columns except CONFIG ones
     */
    public function getCompleteEntry($_bIncludeConfigColumns = FALSE) {
        $aData = parent::getCompleteEntry($_bIncludeConfigColumns);
        
        unset($aData['sPassword']);
        
        $aData["sHash"] = md5(strtolower(trim($this->sMail)));
        $aData["bOnline"] = FALSE | $this->isOnline();
        
        return $aData;
    }
    
    /**
     * Get an array full of Iiigel/Model/Group's based on this user's ID:
     * If first param is set to FALSE, only groups of which the current user is a member of are shown.
     * If first param is set to TRUE and user is admin, all groups are shown.
     * If first param is set to TRUE and user is not an admin, all groups for which the user has admin rights are shown.
     * 
     * @param  boolean [$_bWithAdminRights          = FALSE] if FALSE, all membered i. are returned, otherwise all managable ones
     * @return array   array full of Group objects
     */
    public function getGroups($_bWithAdminRights = FALSE, $_bReturnIdsOnly = FALSE) {
        $aGroup = array();
        $mResult = FALSE;
        if($_bWithAdminRights) {
            if($this->bAdmin) {
                $mResult = $GLOBALS['oDb']->query('SELECT * FROM `group` WHERE NOT bDeleted ORDER BY sName ASC');
            } else {
                $mResult = $GLOBALS['oDb']->query('SELECT * FROM `group` WHERE NOT bDeleted AND nId IN (
                    SELECT nIdType FROM `right` WHERE nIdUser = '.$this->nId.' AND eType = "group" ORDER BY nIdType ASC
                ) ORDER BY sName ASC');
            }
        } else {
            $mResult = $GLOBALS['oDb']->query('SELECT * FROM `group` WHERE NOT bDeleted AND nId IN (
                SELECT nIdGroup FROM user2group WHERE NOT bDeleted AND (nStart IS NULL OR nStart = 0 OR nStart < UNIX_TIMESTAMP()) AND (nEnd IS NULL OR nEnd = 0 OR nEnd > UNIX_TIMESTAMP()) AND nIdUser = '.$this->nId.' ORDER BY nId ASC
            ) ORDER BY sName ASC');
        }
        if($mResult) {
            while(($aRow = $GLOBALS['oDb']->get($mResult))) {
                if($_bReturnIdsOnly) {
                    $aGroup[] = $aRow['nId'];
                } else {
                    $aGroup[] = new \Iiigel\Model\Group($aRow);
                }
            }
        }
        return $aGroup;
    }
    
    /**
     * Get an array full of Iiigel/Model/Institution's based on this user's ID:
     * If first param is set to FALSE, only institutions of which the current user is a member of are shown.
     * If first param is set to TRUE and user is admin, all institutions are shown.
     * If first param is set to TRUE and user is not an admin, all institutions for which the user has admin rights are shown.
     * 
     * @param  boolean [$_bWithAdminRights          = FALSE] if FALSE, all membered i. are returned, otherwise all managable ones
     * @return array   array full of Institution objects
     */
    public function getInstitutions($_bWithAdminRights = FALSE, $_bReturnIdsOnly = FALSE) {
        $aInstitution = array();
        $mResult = FALSE;
        if($_bWithAdminRights) {
            if($this->bAdmin) {
                $mResult = $GLOBALS['oDb']->query('SELECT * FROM `institution` WHERE NOT bDeleted ORDER BY sName ASC');
            } else {
                $mResult = $GLOBALS['oDb']->query('SELECT * FROM `institution` WHERE NOT bDeleted AND nId IN (
                    SELECT nIdType FROM `right` WHERE nIdUser = '.$this->nId.' AND eType = "institution" ORDER BY nIdType ASC
                ) ORDER BY sName ASC');
            }
        } else {
            $mResult = $GLOBALS['oDb']->query('SELECT * FROM `institution` WHERE NOT bDeleted WHERE nId IN (
                SELECT nIdInstitution FROM user2institution WHERE NOT bDeleted AND (nStart IS NULL OR nStart = 0 OR nStart < UNIX_TIMESTAMP()) AND (nEnd IS NULL OR nEnd = 0 OR nEnd > UNIX_TIMESTAMP()) AND nIdUser = '.$this->nId.' ORDER BY nId ASC
            ) ORDER BY sName ASC');
        }
        if($mResult) {
            while(($aRow = $GLOBALS['oDb']->get($mResult))) {
                if($_bReturnIdsOnly) {
                    $aInstitution[] = $aRow['nId'];
                } else {
                    $aInstitution[] = new \Iiigel\Model\Institution($aRow);
                }
            }
        }
        return $aInstitution;
    }
    
    /**
     * Get an array full of Iiigel/Model/Module's based on this user's ID:
     * If first param is set to FALSE, only modules in which the current user is active are shown.
     * If first param is set to TRUE and user is admin, all modules are shown.
     * If first param is set to TRUE and user is not an admin, all modules for which the user has admin rights are shown.
     * 
     * @param  boolean [$_bWithAdminRights          = FALSE] if FALSE, all membered i. are returned, otherwise all managable ones
     * @return array   array full of Institution objects
     */
    public function getModules($_bWithAdminRights = FALSE, $_bReturnIdsOnly = FALSE) {
        $aModule = array();
        $mResult = FALSE;
        if($_bWithAdminRights) {
            if($this->bAdmin) {
                $mResult = $GLOBALS['oDb']->query('SELECT * FROM `module` WHERE NOT bDeleted ORDER BY sName ASC');
            } else {
                $mResult = $GLOBALS['oDb']->query('SELECT * FROM `module` WHERE NOT bDeleted AND nId IN (
                    SELECT nIdType FROM `right` WHERE nIdUser = '.$this->nId.' AND eType = "module" ORDER BY nIdType ASC
                ) ORDER BY sName ASC');
            }
        } else {
            $mResult = $GLOBALS['oDb']->query('SELECT * FROM `module` WHERE bLive AND NOT bDeleted AND nId IN (
                    SELECT nIdModule FROM module2group WHERE NOT bDeleted AND nIdGroup IN (
                        SELECT nId FROM `group` WHERE NOT bDeleted AND (
                            nId IN (
                                SELECT nIdGroup FROM user2group WHERE nIdUser = '.$this->nId.' AND (nStart IS NULL OR nStart = 0 OR nStart < UNIX_TIMESTAMP()) AND (nEnd IS NULL OR nEnd = 0 OR nEnd > UNIX_TIMESTAMP()) AND NOT bDeleted ORDER BY nId ASC
                            ) OR
                            nIdInstitution IN (
                                SELECT nIdInstitution FROM user2institution WHERE nIdUser = '.$this->nId.' AND (nStart IS NULL OR nStart = 0 OR nStart < UNIX_TIMESTAMP()) AND (nEnd IS NULL OR nEnd = 0 OR nEnd > UNIX_TIMESTAMP()) AND NOT bDeleted ORDER BY nId ASC
                            )
                        )
                    )
                ) AND nId IN (
                    SELECT nIdModule FROM user2group WHERE nIdUser = '.$this->nId.' AND (nStart IS NULL OR nStart = 0 OR nStart < UNIX_TIMESTAMP()) AND (nEnd IS NULL OR nEnd = 0 OR nEnd > UNIX_TIMESTAMP()) AND NOT bDeleted ORDER BY nId ASC
                ) ORDER BY sName ASC');
        }
        if($mResult) {
            while(($aRow = $GLOBALS['oDb']->get($mResult))) {
                if($_bReturnIdsOnly) {
                    $aModule[] = $aRow['nId'];
                } else {
                    $aModule[] = new \Iiigel\Model\Module($aRow);
                }
            }
        }
        return $aModule;
    }
    
    /**
     * Return list of users which should be shown in the dropdown.
     * 
     * @param string $_sHashId hashed ID
     * @return array array with arrays with nId and sName keys set
     */
    public static function getRightsUserSelection($_sHashId) {
        return array(
            0 => $GLOBALS['oDb']->getOneRow('SELECT nId, sName FROM `user` WHERE NOT bDeleted AND sHashId = '.$GLOBALS['oDb']->escape($_sHashId))
        );
    }
    
	/**
     * Deletes current row.
     * 
     * @return boolean true if successfully deleted, false otherwise
     */
    public function delete() {
    	if (parent::delete()) {
    		$oCloud = new \Iiigel\Model\Cloud($this);
    		$oCloud->delete();
    		
    		return TRUE;
    	} else {
    		return FALSE;
    	}
    }
    
}

?>
