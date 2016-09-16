<?php namespace Iiigel\Generic;

class Mail {
    private $oMailbox = NULL;
    private $aMail = array();
    private $nPos = 0;
    
    /**
     * Create an IMAP connection based on config settings.
     */
    public function __construct() {
    	if (!is_dir(PATH_DIR.'res/mail/')) {
    		mkdir(PATH_DIR.'res/mail/');
    	}
    	
        $this->oMailbox = new \PhpImap\Mailbox(
            $GLOBALS['aConfig']['aMail']['sHost'], 
            $GLOBALS['aConfig']['aMail']['sUsername'],
            $GLOBALS['aConfig']['aMail']['sPassword'],
            PATH_DIR.'res/mail/'
        );
    }
    
    /**
     * Load all emails from the major INBOX. Marks all loaded emails read.
     * 
     * @return integer number of emails loaded
     */
    public function loadMails() {
        $this->aMail = array();
        if(($aMailId = $this->oMailbox->searchMailBox('ALL'))) {
            foreach($aMailId as $sMailId) {
                $this->aMail[] = $this->oMailbox->getMail($sMailId, TRUE);
            }
            $this->nPos = 0;
        }
        return count($this->aMail);
    }
    
    /**
     * Method to run through all loaded mails (call ->loadMails() first).
     * Usage: while($oMail = ...->get()) { ... }
     * 
     * @return mixed either \PhpImap\IncomingMail object or false if end of stream reached
     */
    public function get() {
        if(isset($this->aMail[$this->nPos])) {
            $this->nPos++;
            return $this->aMail[$this->nPos - 1];
        } else {
            return FALSE;
        }
    }
    
    /**
     * Send an email.
     * 
     * @param  mixed  $_mRecipient              either a correct email address string or an array full of such strings (where every recipient gets his/her own email, no CC/BCC)
     * @param  string $_sSubject                subject of the message
     * @param  string $_sMessage                message body
     * @param  array  [$_mAttachment            = array()] array of file paths, each of which (if existent/readable) is attached to the message
     * @return mixed  if single recipient TRUE on success, FALSE otherwise; if array of recipients, an array is returned with recipients as keys and TRUE/FALSE as values
     */
    public function send($_mRecipient, $_sSubject, $_sMessage, $_mAttachment = array()) {
        if(is_array($_mRecipient)) {
            $aReturn = array();
            foreach($_mRecipient as $sRecipient) {
                $aReturn[$sRecipient] = $this->send($sRecipient, $_sSubject, $_sMessage, $_mAttachment);
            }
            return $aReturn;
        } else {
			
            $aBody = array();
            if(count($_mAttachment) > 0) {
                $aBody[] = array(
                    'type' => TYPEMULTIPART,
                    'subtype' => 'mixed'
                );
            }
            for($i = 0; $i < count($_mAttachment); $i++) {
                if(is_file($_mAttachment[$i]) && is_readable($_mAttachment[$i])) {
                    list($sFile) = explode('/', strrev(str_replace('\\', '/', $_mAttachment[$i])), 2);
                    $sFile = strrev($sFile);
                    $aBody[] = array(
                        'type' => TYPEAPPLICATION,
                        'encoding' => ENCBASE64,
                        'subtype' => 'octet-stream',
                        'description' => $sFile,
                        'disposition.type' => 'attachment',
                        'disposition' => array(
                            'filename' => $sFile
                        ),
                        'dparameters.filename' => $sFile,
                        'parameters.name' => $sFile,
                        'contents.data' => base64_encode(file_get_contents($_mAttachment[$i]))
                    );
                }
            }
			$header  = 'MIME-Version: 1.0' . "\r\n";
			$header .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
            
            return mail($_mRecipient, $_sSubject,$_sMessage,$header);
        }
    }
}

?>