<?php
namespace Netztechniker\Mailcopy\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Utility to help building Extension Manager Configuration
 *
 * @author Ludwig Rafelsberger <info@netztechniker.at>, netztechniker.at
 */
class ExtensionManagerConfigurationUtility
{

    /**
     * Render a selector element that allows to select the encryption method for SMTP
     *
     * @param array $params Field information to be rendered
     *
     * @return string The HTML selector
     */
    public function buildSmtpEncryptSelector(array $params)
    {
        $extConf = GeneralUtility::removeDotsFromTS(
            unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['mailcopy'])
        );

        $transports = stream_get_transports();
        if (empty($transports)) {
            return '';
        }
        $markup = '<select class="valid" name="' . $params['fieldName'] . '" id=em-' . $params['propertyName'] . '">';

        $active = (string)self::extract($extConf, explode('.', $params['propertyName']));
        foreach ($transports as $transport) {
            $markup .= '<option value="' . $transport . '"' . ($transport === $active ? ' selected="selected"' : '') .
                '>' . $transport . '</option>';
        }
        $markup .= '</select>';

        return $markup;
    }



    // ---------------------- internal helper methods -----------------------
    /**
     * Extract a nested array subkey
     *
     * Using an input of ['foo' => ['bar' => 'some value']], the path ['foo', 'bar'] would return 'some value'.
     *
     * @param array $data The input array to extract the nested key
     * @param array $path Path to the key to be extracted. Every item selects a key one level deeper in $data.
     *
     * @return null|mixed The value of the nested key in $data given by $path or null if that key does not exist
     */
    protected static function extract(array $data, array $path = array())
    {
        $pathPart = array_shift($path);

        if (!array_key_exists($pathPart, $data)) {
            return null;
        }

        if (empty($path)) {
            return $data[$pathPart];
        }
     
        return self::extract($data[$pathPart], $path);
    }
}
