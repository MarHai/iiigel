<?php namespace Iiigel\Controller;

class File extends \Iiigel\Controller\DefaultController {
    const DEFAULT_ACTION = 'show';
    
    /**
     * Show a file (with appropriate mime type and no-cache) from the upload directory.
     * Output "dies" afterwards.
     */
    public function show() {
        $sFile = str_replace(array('file/', 'File/'), '', $GLOBALS['aRequest']['path']);
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Content-Type: '.finfo_file(finfo_open(FILEINFO_MIME_TYPE), $GLOBALS['aConfig']['sUploadDir'].$sFile));
        header('HTTP/1.1 200 Ok');
        header('Content-Length: '.filesize($GLOBALS['aConfig']['sUploadDir'].$sFile));
        die(file_get_contents($GLOBALS['aConfig']['sUploadDir'].$sFile));
    }
    
    /**
     * Upload all files in queue.
     */
    public function upload() {
        $oUpload = new \Iiigel\Generic\Upload();
        die(json_encode($oUpload->getFiles()));
    }
    
    /**
     * Delete a just uploaded file.
     * 
     * @param string $_sFilehash hashed string retrieved via ->upload
     */
    public function delete($_sFilehash) {
        $oUpload = new \Iiigel\Generic\Upload();
        die($oUpload->delete($_sFilehash));
    }
}

?>