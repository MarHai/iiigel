<?php namespace Iiigel\Model;

class Chapter extends \Iiigel\Model\GenericModel {
    const TABLE = 'chapter';
    const DEFAULT_ORDER = 'nOrder ASC';
    
    
    /**
     * Return list of entries which should be shown in the dropdown.
     * 
     * @param string $_sHashId hashed ID
     * @return array array with up to four keys (chapter, group, institution, module) which itself hold arrays with nId and sName keys set
     */
    public static function getRightsTypeIdSelection($_sHashId) {
        return array(
            'chapter' => array(
                0 => $GLOBALS['oDb']->getOneRow('SELECT sHashId AS nId, sName FROM `chapter` WHERE NOT bDeleted AND sHashId = '.$GLOBALS['oDb']->escape($_sHashId))
            )
        );
    }
    
    /**
     * Load list of all entries, no matter of the current one.
     * If first parameter is set to a module ID, only this module's chapters are loaded.
     * 
     * @param integer $_nIdModule if set, only this module's chapters are loaded
     * @return object  oDb result object
     */
    public function getList($_nIdModule = NULL) {
         return $GLOBALS['oDb']->query('SELECT nId,sHashId,sName,sNote,bLive,nOrder FROM `chapter` WHERE  NOT bDeleted'.($_nIdModule === NULL ? '' : (' AND nIdModule = '.$GLOBALS['oDb']->escape($_nIdModule))).' ORDER BY '.$this::DEFAULT_ORDER);
    }
    
    
    /**
    * Replaces our own Tags with respective html/js Tags
    * 
    * @param string $_sHashId hashed representation of ID
    * returns the string that needs to be displayed
    */
    public function replaceTags ($_sContent)
    {
        $sMyDocument = str_replace('<', '&lt;', str_replace('>', '&gt;', $_sContent));
        $sTags = $GLOBALS['oDb']->query('SELECT sTagFrom,sTagIn FROM tags');        
        for ($x = 0; $x <= $GLOBALS['oDb']->count($sTags);$x++) {
            $aRow = $GLOBALS['oDb']->get($sTags);
          $sMyDocument =  str_replace ($aRow['sTagFrom'],$aRow['sTagIn'],$sMyDocument);
        } 
        return $sMyDocument;
    }
}

?>
