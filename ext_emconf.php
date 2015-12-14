<?php
/** @var string $_EXTKEY */
$EM_CONF[$_EXTKEY] = array(
    'title' => 'Send outgoing mail to additional transports (e.g. mbox)',
    'description' => 'Send all mail going out through the TYPO3 Mail API to additional, configurable transports. ' .
        'One use case is to document every email sent.',
    'category' => 'misc',
    'author' => 'Ludwig Rafelsberger',
    'author_email' => 'info@netztechniker.at',
    'author_company' => 'netztechniker.at',
    'state' => 'beta',
    'uploadfolder' =>  false,
    'createDirs' => '',
    'clearCacheOnLoad' => true,
    'version' => '0.1.0',
    'constraints' => array(
        'depends' => array(
            'typo3' => '6.2.0-6.2.99',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    ),
);
