<?php

$GLOBALS['aConfig'] = array(
    
    //if true, debug information is shown every now and then
    'bDebug' => TRUE,
    
    //if URL root path is not /, then it needs to be specified here (without trailing slash)
    'sRootPath' => 'iiigel',
    
    //database settings
    'aDb' => array(
        'sHost' => '127.0.0.1',
        'sUsername' => 'iiigel',
        'sPassword' => '',
        'sDatabase' => 'iiigel',
        'sCharset' => 'utf8',
        'sInitialQuery' => 'SET NAMES utf8'
    ),
    
    //md5-hashing salt
    'sSalt' => 'löa8i3jölhj())j3J(jl.sH-a-slijf3esS',
    
    //maximum lifetime of a session to stay logged in at one and the same computer
    'nMaxSessionLifetime' => (60*60*24*7),
	
	//maximum delay between actions to stay shown as online
	'nMaxActionDelay' => (60*60),
    
	//mail settings, used for IMAP connection, sending/receiving mails
	'aMail' => array(
		'sAddress' => '',
		'sUsername' => '',
		'sPassword' => '',
		'sHost' => ''
	),
		
	//default time zone
	'sTimeZone' => 'Europe/Berlin',
    
    //default date format
    'sDateFormatJs' => 'dd. mm. yyyy hh:ii',
    'sDateFormatJsMoment' => 'DD.MM.YYYY HH:mm',
    'sDateFormatPhp' => 'd.m.Y H:i',
    
    //file uploads
    'sUploadDir' => 'upload/',
    'nUploadMaxSize' => 10*1024*1024,
    'sFileUrl' => 'file/',
    
    //interval (in milliseconds) after which both (1) the live interpretation takes place (2) and the editor-content is saved after 
    'nEditorWaitTime' => 2500
);

?>
