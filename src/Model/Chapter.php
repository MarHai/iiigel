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
    

    
    
 //   echo "<script>console.log( 'Debug Objects: " . $iOffset . "' );</script>";
    
    /**
    * Replaces our own Tags with respective html/js Tags
    * 
    * @param string $_sHashId hashed representation of ID
    * returns the string that needs to be displayed
    */
    public function replaceTags ($_sContent)
    {
        $sMyDocument = str_replace(' ', '&nbsp;',  str_replace("\n",'<br>', str_replace("]\n",']' ,str_replace('<', '&lt;', str_replace('>', '&gt;', $_sContent)))));

        $sTags = $GLOBALS['oDb']->query('SELECT sTagFrom,sTagIn,sParam FROM tags');     
        for ($x = 0; $x <= $GLOBALS['oDb']->count($sTags);$x++) {
            $aRow = $GLOBALS['oDb']->get($sTags);
            if (isset($aRow['sParam'])<> true) {
                $sMyDocument =  str_replace ($aRow['sTagFrom'],$aRow['sTagIn'],$sMyDocument);
            } else {
                $iOffset = 0;
                $i = 1;
                $myCount =substr_count($sMyDocument, $aRow['sTagFrom']); 
                if ($myCount > 0){
                    while ( $i <= $myCount ){ 
                        if ($i > 0){$iOffset = strpos($sMyDocument,$aRow['sTagFrom']);}
                        $i = $i +1;
                        if (substr($sMyDocument,$iOffset+strlen($aRow['sTagFrom']) ,1)=='{'){
                            
                            $sMyParam = substr($sMyDocument,strpos($sMyDocument,'{' , $iOffset)+1,strpos ($sMyDocument,'}',$iOffset)-1-strpos($sMyDocument,'{' , $iOffset));
                            $sTest = $sMyParam;
                            $iParamOffset = 0;
                            $sMyWorkStr ='';
                            for ($e = 0; $e <= substr_count($sMyParam, ';')+1;$e++){                               
                                if (strpos($sMyParam,';') > 0) {    
                                    $sOneParam = substr($sMyParam,$iParamOffset,strpos($sMyParam,';',$iParamOffset)-$iParamOffset);
                                    $iParamOffset = strpos($sMyParam,$sOneParam,$iParamOffset);
                                    $sMyParam = preg_replace('/'.preg_quote($sOneParam .';', '/').'/','',$sMyParam);
                                } else {
                                    $sOneParam = substr($sMyParam,0,strlen($sMyParam)-1);
                                    $sMyParam= preg_replace('/'.preg_quote($sOneParam, '/').'/','',$sMyParam);
                                }
                                if (strpos('#' . $aRow['sParam'],substr($sOneParam,0,strpos($sOneParam,'=')))> 0){
                                    $ishortOffset = strpos($sOneParam,'"')+1;   
                                    $sMyWorkStr = $sMyWorkStr . substr($sOneParam,0,strpos($sOneParam,'"',$ishortOffset)+1) . ' '; 
                                }
                               
                        
                            }
                            
            
                        }
                    


                
                        $sToReplace = $aRow['sTagIn'];
                        $sTrReplace = str_replace('>',' ' . $sMyWorkStr,$sToReplace);
                        $sTrReplace = $sTrReplace . ">";

                        $iReplaceOffset = strpos($sMyDocument,'}',$iOffset)+1-$iOffset;

                        
                 
                        
                        $sMyDocument = str_replace(substr($sMyDocument,$iOffset,$iReplaceOffset),$sTrReplace,$sMyDocument);
                    }
                }
            }
        }
        
        return $sMyDocument;
    }
}

?>
