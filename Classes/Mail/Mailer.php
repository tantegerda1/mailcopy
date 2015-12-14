<?php
namespace Netztechniker\Mailcopy\Mail;

use Swift_Mime_Message;
use TYPO3\CMS\Core\Mail\Mailer as CoreMailer;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;

// Make sure Swift's auto-loader is registered
require_once PATH_typo3 . 'contrib/swiftmailer/swift_required.php';

/**
 * Mailer
 *
 * @author Ludwig Rafelsberger <info@netztechniker.at>, netztechniker.at
 */
class Mailer extends CoreMailer
{

    /**
     * extraTransports
     *
     * @var array
     */
    protected $extraTransports = array();






    // --------------------------- public methods ---------------------------
    /**
     * Send the given Message like it would be sent in a mail client.
     *
     * All recipients (with the exception of Bcc) will be able to see the other recipients this message was sent to.
     *
     * Recipient/sender data will be retrieved from the Message object.
     *
     * This Mailer instance will send the given message to additional transports besides the one configured via the
     * TYPO3 Mail API.
     *
     * @param Swift_Mime_Message $message The email message to send
     * @param array $failedRecipients List of (string) failed recipients e-mail addresses
     *
     * @return integer Number of recipients who were accepted for delivery
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $sent = parent::send($message, $failedRecipients);

        // send via additional transports
        foreach ($this->extraTransports as $transport) {
            /** @var \Swift_Transport $transport */
            if (!$transport->isStarted()) {
                $transport->start();
            }

            try {
                $transport->send($message);
            } catch (\Swift_RfcComplianceException $ignored) {
            }
        }

        // return result from "main" transport
        return $sent;
    }





    // -------------------------- object lifecycle --------------------------
    /**
     * When constructing, also initializes the \Swift_Transport like configured
     *
     * @param null|\Swift_Transport $transport optionally pass a transport to the constructor.
     * @throws \TYPO3\CMS\Core\Exception
     */
    public function __construct(\Swift_Transport $transport = null)
    {
        parent::__construct($this->transport);

        $backupSettings = $this->mailSettings;
        $backupTransport = $this->transport;

        // initialize addtional transports as configured in extension configuration re-using core initialization method
        $extConf =
            GeneralUtility::removeDotsFromTS(unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mailcopy']));
        foreach ($extConf['transports'] as $config) {
            if (false === (bool)$config['enabled']) {
                continue;
            }

            $type = $config['type'];
            $transportConfig = $config[$type];
            $this->mailSettings = array('transport' => $type);
            foreach ($transportConfig as $key => $value) {
                $this->mailSettings['transport_' . $type . '_' . $key] = $value;
            }

            $this->initializeTransport();
            $this->extraTransports[] = $this->transport;
        }

        $this->transport = $backupTransport;
        $this->mailSettings = $backupSettings;
    }


    /**
     * Prepares a transport using the TYPO3_CONF_VARS configuration
     *
     * This is an exact copy of TYPO3s initializeTransport() method and only needed because that method has private
     * visibility and is thus not accessible from inheriting classes.
     *
     * Used options:
     * $TYPO3_CONF_VARS['MAIL']['transport'] = 'smtp' | 'sendmail' | 'mail' | 'mbox'
     *
     * $TYPO3_CONF_VARS['MAIL']['transport_smtp_server'] = 'smtp.example.org';
     * $TYPO3_CONF_VARS['MAIL']['transport_smtp_port'] = '25';
     * $TYPO3_CONF_VARS['MAIL']['transport_smtp_encrypt'] = FALSE; # requires openssl in PHP
     * $TYPO3_CONF_VARS['MAIL']['transport_smtp_username'] = 'username';
     * $TYPO3_CONF_VARS['MAIL']['transport_smtp_password'] = 'password';
     *
     * $TYPO3_CONF_VARS['MAIL']['transport_sendmail_command'] = '/usr/sbin/sendmail -bs'
     *
     * @throws \TYPO3\CMS\Core\Exception
     * @throws \RuntimeException
     */
    private function initializeTransport()
    {
        switch ($this->mailSettings['transport']) {
            case 'smtp':
                // Get settings to be used when constructing the transport object
                list($host, $port) = preg_split('/:/', $this->mailSettings['transport_smtp_server']);
                if ($host === '') {
                    throw new Exception(
                        '$TYPO3_CONF_VARS[\'MAIL\'][\'transport_smtp_server\'] needs to be set when transport is set ' .
                            'to "smtp"',
                        1291068606
                    );
                }
                if ($port === null || $port === '') {
                    $port = '25';
                }
                $useEncryption = $this->mailSettings['transport_smtp_encrypt'] ?: null;
                // Create our transport
                $this->transport = \Swift_SmtpTransport::newInstance($host, $port, $useEncryption);
                // Need authentication?
                $username = $this->mailSettings['transport_smtp_username'];
                if ($username !== '') {
                    $this->transport->setUsername($username);
                }
                $password = $this->mailSettings['transport_smtp_password'];
                if ($password !== '') {
                    $this->transport->setPassword($password);
                }
                break;
            case 'sendmail':
                $sendmailCommand = $this->mailSettings['transport_sendmail_command'];
                if (empty($sendmailCommand)) {
                    throw new Exception(
                        '$TYPO3_CONF_VARS[\'MAIL\'][\'transport_sendmail_command\'] needs to be set when transport ' .
                            'is set to "sendmail"',
                        1291068620
                    );
                }
                // Create our transport
                $this->transport = \Swift_SendmailTransport::newInstance($sendmailCommand);
                break;
            case 'mbox':
                $mboxFile = $this->mailSettings['transport_mbox_file'];
                if ($mboxFile == '') {
                    throw new Exception(
                        '$TYPO3_CONF_VARS[\'MAIL\'][\'transport_mbox_file\'] needs to be set when transport is set ' .
                            'to "mbox"',
                        1294586645
                    );
                }
                // Create our transport
                $this->transport = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Mail\\MboxTransport', $mboxFile);
                break;
            case 'mail':
                // Create the transport, no configuration required
                $this->transport = \Swift_MailTransport::newInstance();
                break;
            default:
                // Custom mail transport
                $customTransport = GeneralUtility::makeInstance($this->mailSettings['transport'], $this->mailSettings);
                if ($customTransport instanceof \Swift_Transport) {
                    $this->transport = $customTransport;
                } else {
                    throw new \RuntimeException(
                        $this->mailSettings['transport'] . ' is not an implementation of \\Swift_Transport, but ' .
                            'must implement that interface to be used as a mail transport.',
                        1323006478
                    );
                }
        }
    }
}
