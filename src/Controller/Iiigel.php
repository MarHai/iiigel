<?php namespace Iiigel\Controller;

class Iiigel extends \Iiigel\Controller\StaticPage {
    const DEFAULT_ACTION = 'show';
    
    protected $sRawOutput = NULL;
    
    public function __construct() {
        if(!isset($GLOBALS['oUserLogin'])) {
            throw new \Exception(_('error.permission'));
        }
        parent::__construct();
    }
    
    /**
     * Output rendered static HTML page.
     * 
     * @return string HTML code
     */
    public function output() {
        return ($GLOBALS['bAjax'] && $this->sRawOutput !== NULL) ? $this->sRawOutput : $this->oView->render();
    }
    
    /**
     * Display the main module training view.
     * 
     * @param string $_sHashId if module ID, first chapter of this module is shown; if chapter ID, this chapter is shown
     */
    public function show($_sHashId = '') {
        $oChapter = $oModule = NULL;
        if($_sHashId{0} == 'm') {
            $oModule = new \Iiigel\Model\Module($_sHashId);
            $oChapter = new \Iiigel\Model\Chapter();
            $oResult = $oChapter->getList($oModule->nId);
            if(($aRow = $GLOBALS['oDb']->get($oResult))) {
                $oChapter = new \Iiigel\Model\Chapter($aRow);
            } else {
                $oChapter = NULL;
            }
        } else {
            $oChapter = new \Iiigel\Model\Chapter($_sHashId);
            $oModule = new \Iiigel\Model\Module($oChapter->nIdModule);
        }
        if($oModule->nId > 0) {
            //check if current user is in this module
            //load module data (incl. chapter)
            $this->oView->oModule = $oModule;
            $this->oView->oChapter = $oChapter;
            $this->oView->nEditorWaitTime = $GLOBALS['aConfig']['nEditorWaitTime'];
            $this->loadFile('iiigel');
        } else {
            throw new \Exception(sprintf(_('error.objectload'), $_sHashId));
        }
    }
    
    /**
     * Loads cloud and sets ->sRawOutput accordingly.
     */
    public function cloud() {
        $this->sRawOutput = json_encode($this->getCloud());
    }
    
    protected function getCloud() {
            /**
            * TESTING
            */
        return array(
            array(
                'sName' => sprintf(_('cloud.root'), $GLOBALS['oUserLogin']->sName),
                'sHashId' => 'sdlfiji3fjlin',
                'sUpdate' => date($GLOBALS['aConfig']['sDateFormatPhp'], 1439960329),
                'sSize' => sprintf(_('cloud.countfiles'), 4),
                'sType' => 'folder',
                'aChildren' => array(
                    array(
                        'sName' => 'ordner1',
                        'sHashId' => 'sdf3fwg4tgfg4',
                        'sUpdate' => date($GLOBALS['aConfig']['sDateFormatPhp'], 1440015549),
                        'sSize' => sprintf(_('cloud.countfiles'), 3),
                        'sType' => 'folder',
                        'aChildren' => array(
                            array(
                                'sName' => 'ordnar 2',
                                'sHashId' => 'al8j3ljsfö80u',
                                'sUpdate' => date($GLOBALS['aConfig']['sDateFormatPhp'], 1440015549),
                                'sSize' => sprintf(_('cloud.countfiles'), 2),
                                'sType' => 'folder',
                                'aChildren' => array(
                                    array(
                                        'sName' => 'test_element.css',
                                        'sHashId' => 'muhaoj3jl8jf3',
                                        'sUpdate' => date($GLOBALS['aConfig']['sDateFormatPhp'], 1440000762),
                                        'sSize' => '318 KByte',
                                        'sType' => 'text/css',
                                        'bOpen' => TRUE
                                    ),
                                    array(
                                        'sName' => 'test_element.min.js',
                                        'sHashId' => 'asdf3effw3ss3',
                                        'sUpdate' => date($GLOBALS['aConfig']['sDateFormatPhp'], 1440000032),
                                        'sSize' => '84 Byte',
                                        'sType' => 'text/js',
                                        'bOpen' => FALSE
                                    )
                                )
                            ),
                            array(
                                'sName' => 'bar.php',
                                'sHashId' => 'skliuj3fflii',
                                'sUpdate' => date($GLOBALS['aConfig']['sDateFormatPhp'], 1440018654),
                                'sSize' => '318 KByte',
                                'sType' => 'text/php',
                                'bOpen' => FALSE
                            )
                        )
                    ),
                    array(
                        'sName' => 'foo.html',
                        'sHashId' => 'bkalkjsdlfjd',
                        'sUpdate' => date($GLOBALS['aConfig']['sDateFormatPhp'], 1440017230),
                        'sSize' => '212 KByte',
                        'sType' => 'text/html',
                        'bOpen' => TRUE
                    )
                )
            )
        );
            /**
            * TESTING done
            */
    }
    
    /**
     * Interpreteas a specific file. Sets any (HTML) output into ->sRawOutput (without doctype, html, head, body tags).
     * 
     * @param string $_sHashId hash ID of element to interpret
     */
    public function interpret($_sHashId) {
        //TEST CODE FROM HERE
        $this->sRawOutput = '<p>now interpreting ... <code>'.$_sHashId.'</code></p>';
    }
    
    /**
     * Open a specific file. Sets File() object to ->sRawOutput, allowing to manually redirect in case the MIME type is not text/...
     * 
     * @param string $_sHashId hash ID of element to open
     */
    public function open($_sHashId) {
        //SET file to be opened
        //READ file content and return
        
        //TEST CODE FROM HERE
        $aFile = $this->deletelater__findFileInCloud($_sHashId, $this->getCloud());
        $aFile['sFile'] = '<html>head></head><body><p>hallo welt</p></body></html>';
        $this->sRawOutput = json_encode($aFile);
    }
    
    private function deletelater__findFileInCloud($_sHashId, $_aCloud) {
        for($i = 0; $i < count($_aCloud); $i++) {
            if($_aCloud[$i]['sHashId'] == $_sHashId) {
                return $_aCloud[$i];
            } elseif(isset($_aCloud[$i]['aChildren'])) {
                $mReturn = $this->deletelater__findFileInCloud($_sHashId, $_aCloud[$i]['aChildren']);
                if($mReturn !== NULL) {
                    return $mReturn;
                }
            }
        }
        return NULL;
    }
    
    /**
     * Closes file.
     * 
     * @param string $_sHashId hash ID of element to close
     */
    public function close($_sHashId) {
        //SET file to be closed
        $this->sRawOutput = TRUE;
    }
    
    /**
     * Save a file's new contents. Does output TRUE on success.
     * 
     * @param string $_sHashId  hash ID of element to be updated
     * @param string $_sContent new contents
     */
    public function update($_sHashId, $_sContent) {
        //SAVE content to this file
        $this->sRawOutput = TRUE;
    }
    
    /**
     * Create a new file (empty) and returns the new cloud structure.
     * 
     * @param string $_sHashIdParent hash ID of parent element, if invalid/NULL/empty, element is put to root element
     * @param string $_sName         new file's name
     */
    public function createFile($_sHashIdParent, $_sName) {
        //CREATE new file
        $this->sRawOutput = json_encode($this->getCloud());
    }
    
    /**
     * Create a new directory (empty) and returns the new cloud structure.
     * 
     * @param string $_sHashIdParent hash ID of parent element, if invalid/NULL/empty, element is put to root element
     * @param string $_sName         new folder's name
     */
    public function createDir($_sHashIdParent, $_sName) {
        //CREATE new directory
        $this->sRawOutput = json_encode($this->getCloud());
    }
    
    /**
     * Renames any element (either file or folder). Returns new cloud structure.
     * 
     * @param string $_sHashId  hash ID of element to be renamed
     * @param string $_sNewName new name
     */
    public function rename($_sHashId, $_sNewName) {
        //RENAME an object (could be file or directory)
        $this->sRawOutput = json_encode($this->getCloud());
    }
    
    /**
     * Deletes any element (either file or folder). If folder, all child elements are deleted as well. Returns new cloud structure.
     * 
     * @param string $_sHashId hash ID of element to be deleted
     */
    public function delete($_sHashId) {
        //DELETE an object (could be file or directory)
        $this->sRawOutput = json_encode($this->getCloud());
    }
    
    /**
     * Presents a file/folder for download. Uses HTTP headers and dies afterwards.
     * If element to be downloaded is a folder, this folder gets zip'ed and presented for download.
     * 
     * @param string $_sHashId hash ID of element to download
     */
    public function download($_sHashId) {
        //DOWNLOAD a file (presented as download) or ZIP&DOWNLOAD a folder (presented as download)
        //die() afterwards
    }
    
    /**
     * Upload a file from the local machine.
     * 
     * @param string $_sHashId hash ID of folder into which files should be loaded
     */
    public function uploadFromHost($_sHashId) {
        $oUpload = new \Iiigel\Generic\Upload();
        //PUSH $oUpload->getFiles() into cloud from current folder
        $this->sRawOutput = json_encode($this->getCloud());
    }
    
    /**
     * Upload a file from the web.
     * 
     * @param string $_sHashId hash ID of folder into which files should be loaded
     * @param string $_sUrl    URL to be included
     */
    public function uploadFromUrl($_sHashId, $_sUrl) {
        //PUSH file_get_contents or curl into cloud from current folder
        $this->sRawOutput = json_encode($this->getCloud());
    }
}

?>