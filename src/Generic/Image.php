<?php namespace Iiigel\Generic;

class Image {
    
    /**
     * Checks whether file is an image (and thus resizable).
     * 
     * @param  string  $_sFile complete file path and name
     * @return boolean TRUE if file is an image
     */
    static public function isImage($_sFile) {
        return strpos(finfo_file(finfo_open(FILEINFO_MIME_TYPE), $_sFile), 'image/') !== FALSE;
    }
    
    /**
     * Resize an image "in place" (i.e., current file gets overwritten).
     * 
     * @param string  $_sFile          complete file path and name
     * @param integer [$_nWidth        = 800] new width (height calculated accordingly)
     */
    static public function resize($_sFile, $_nWidth = 800) {
        $oImage = new \Imagick();
        $oImage->setSize($_nWidth, 0);
        $oImage->setResolution(72, 72);
        $oImage->readImage($_sFile);
        $oImage->writeImage();
    }
}

?>