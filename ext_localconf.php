<?php
defined('TYPO3_MODE') or die();
/** @var string $_EXTKEY */


call_user_func(function ($packageKey) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Mail\\Mailer'] = array(
        'className' => 'Netztechniker\\Mailcopy\\Mail\\Mailer'
    );
}, $_EXTKEY);
