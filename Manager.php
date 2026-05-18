<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Ios;

use Aurora\System\Api;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2023, Afterlogic Corp.
 *
 * @property Module $oModule
 */
class Manager extends \Aurora\System\Managers\AbstractManager
{
    /**
     * @var \Aurora\Modules\Dav\Module
     */
    private $oDavModule;

    /**
     * @param \Aurora\System\Module\AbstractModule $oModule
     */
    public function __construct($oModule = null)
    {
        parent::__construct($oModule);

        $this->oDavModule = Api::GetModule('Dav');
    }

    /**
     *
     * @param \DOMDocument $oXmlDocument
     * @param array $aPayload
     *
     * @return \DOMElement
     */
    private function _generateDict($oXmlDocument, $aPayload)
    {
        $oDictElement = $oXmlDocument->createElement('dict');

        foreach ($aPayload as $sKey => $mValue) {
            $oDictElement->appendChild($oXmlDocument->createElement('key', $sKey));

            if (is_int($mValue)) {
                $oDictElement->appendChild($oXmlDocument->createElement('integer', $mValue));
            } elseif (is_bool($mValue)) {
                $oDictElement->appendChild($oXmlDocument->createElement($mValue ? 'true' : 'false'));
            } elseif (is_array($mValue)) {
                $oArray = $oXmlDocument->createElement('array');
                foreach ($mValue as $mItem) {
                    $oArray->appendChild($oXmlDocument->createElement('string', $mItem));
                }
                $oDictElement->appendChild($oArray);
            } else {
                $oDictElement->appendChild($oXmlDocument->createElement('string', $mValue));
            }
        }
        return $oDictElement;
    }

    /**
     *
     * @param \DOMDocument $oXmlDocument
     * @param string $sPayloadId
     * @param \Aurora\System\Classes\Account $oAccount
     * @param bool $bIsDemo Default false
     *
     * @return \DOMElement|bool
     */
    private function _generateEmailDict($oXmlDocument, $sPayloadId, $oAccount, $bIsDemo = false)
    {
        $result = false;

        if (class_exists('\Aurora\Modules\Mail\Models\MailAccount')) {
            /** @var \Aurora\Modules\Mail\Models\MailAccount $oAccount */
            Api::Log('Generating email part for account: ' . $oAccount->Email);

            $oModuleManager = Api::GetModuleManager();

            $sIncomingServer = '';
            $iIncomingPort = 143;
            $bIncomingUseSsl = false;

            $sOutgoingServer = '';
            $iOutgoingPort = 25;
            $bOutgoingUseSsl = false;

            $oServer = $oAccount->GetServer();

            // Incoming Server
            if ($oServer->SetExternalAccessServers && !empty($oServer->ExternalAccessImapServer)) { // from external Server settings
                $sIncomingServer = $oServer->ExternalAccessImapServer;
                $iIncomingPort = $oServer->ExternalAccessImapPort;
                $bIncomingUseSsl = $oServer->ExternalAccessImapUseSsl;
            } else {
                $sIncomingServer = \trim($oModuleManager->getModuleConfigValue('ExternalHostNameOfLocalImap', null));
                if (0 < \strlen($sIncomingServer)) { // from Mail module settings
                    $aMatch = array();
                    if (\preg_match('/:([\d]+)$/', $sIncomingServer, $aMatch) && !empty($aMatch[1]) && \is_numeric($aMatch[1])) {
                        $sIncomingServer = \preg_replace('/:[\d]+$/', $sIncomingServer, '');
                        $iIncomingPort = (int) $aMatch[1];
                        $bIncomingUseSsl = 993 === $iIncomingPort;
                    }
                } else { // from Server settings
                    $sIncomingServer = $oServer->IncomingServer;
                    $iIncomingPort = $oServer->IncomingPort;
                    $bIncomingUseSsl = $oServer->IncomingUseSsl;
                }
            }

            // Outgoing Server
            if ($oServer->SetExternalAccessServers && !empty($oServer->ExternalAccessSmtpServer)) { // from external Server settings
                $sOutgoingServer = $oServer->ExternalAccessSmtpServer;
                $iOutgoingPort = $oServer->ExternalAccessSmtpPort;
                $bOutgoingUseSsl = $oServer->ExternalAccessSmtpUseSsl;
            } else {
                if (0 < \strlen($sOutgoingServer)) { // from Mail module settings
                    $aMatch = array();
                    if (\preg_match('/:([\d]+)$/', $sOutgoingServer, $aMatch) && !empty($aMatch[1]) && \is_numeric($aMatch[1])) {
                        $sOutgoingServer = \preg_replace('/:[\d]+$/', $sOutgoingServer, '');
                        $iOutgoingPort = (int) $aMatch[1];
                        $bOutgoingUseSsl = 465 === $iOutgoingPort;
                    }
                } else { // from Server settings
                    $sOutgoingServer = $oServer->OutgoingServer;
                    $iOutgoingPort = $oServer->OutgoingPort;
                    $bOutgoingUseSsl = $oServer->OutgoingUseSsl;
                }
            }

            if (empty($sIncomingServer) || empty($sOutgoingServer)) {
                Api::Log('Error: IncomingServer or OutgoingServer is empty');
                return false;
            }

            $bIncludePasswordInProfile = $this->oModule->oModuleSettings->IncludePasswordInProfile;
            $sOutgoingMailServerUsername = $oAccount->IncomingLogin;
            $sOutgoingMailServerAuthentication = 'EmailAuthPassword';
            $bUseSpecifiedCredentials = false;
            if (class_exists('\Aurora\Modules\Mail\Enums\SmtpAuthType')) {
                if ($oServer->SmtpAuthType === \Aurora\Modules\Mail\Enums\SmtpAuthType::UseSpecifiedCredentials) {
                    $bUseSpecifiedCredentials = true;
                }
                if ($oServer->SmtpAuthType === \Aurora\Modules\Mail\Enums\SmtpAuthType::NoAuthentication) {
                    $sOutgoingMailServerAuthentication = 'EmailAuthNone';
                }
            }

            if ($oAccount->XOAuth === 'gmail') {
                $aEmail = [
                    'PayloadVersion'				=> 1,
                    'PayloadUUID'					=> \Sabre\DAV\UUIDUtil::getUUID(),
                    'PayloadType'					=> 'com.apple.account.Google',
                    'PayloadIdentifier'				=> 'com.company.gmail.account.' . $oAccount->Email,
                    'PayloadDisplayName'			=> 'Gmail',
                    'EmailAddress'					=> $oAccount->Email,
                    'EmailAccountName'				=> 0 === strlen($oAccount->FriendlyName)
                        ? $oAccount->Email : $oAccount->FriendlyName,
                    'Services'					    => ['Mail'],
                ];
            } else {
                $aEmail = [
                    'PayloadVersion'					=> 1,
                    'PayloadUUID'						=> \Sabre\DAV\UUIDUtil::getUUID(),
                    'PayloadType'						=> 'com.apple.mail.managed',
                    'PayloadIdentifier'					=> $sPayloadId . '.' . $oAccount->Email . '.email',
                    'PayloadDisplayName'				=> $oAccount->Email . ' Email Account',
                    'PayloadOrganization'				=> $oModuleManager->getModuleConfigValue('Core', 'SiteName'),
                    'PayloadDescription'				=> 'Configures email account',
                    'EmailAddress'						=> $oAccount->Email,
                    'EmailAccountType'					=> 'EmailTypeIMAP',
                    'EmailAccountDescription'			=> $oAccount->Email,
                    'EmailAccountName'					=> 0 === strlen($oAccount->FriendlyName)
                        ? $oAccount->Email : $oAccount->FriendlyName,
                    'IncomingMailServerHostName'		=> $sIncomingServer,
                    'IncomingMailServerPortNumber'		=> $iIncomingPort,
                    'IncomingMailServerUseSSL'			=> $bIncomingUseSsl,
                    'IncomingMailServerUsername'		=> $oAccount->IncomingLogin,
                    'IncomingPassword'					=> $bIsDemo ? 'demo' : ($bIncludePasswordInProfile ? $oAccount->getPassword() : ''),
                    'IncomingMailServerAuthentication'	=> 'EmailAuthPassword',
                ];
                if (!$bUseSpecifiedCredentials) {
                    $aEmail['OutgoingMailServerHostName'] = $sOutgoingServer;
                    $aEmail['OutgoingMailServerPortNumber'] = $iOutgoingPort;
                    $aEmail['OutgoingMailServerUseSSL']	= 587 == $iOutgoingPort ? true : $bOutgoingUseSsl;
                    $aEmail['OutgoingMailServerUsername'] = $sOutgoingMailServerUsername;
                    $aEmail['OutgoingPasswordSameAsIncomingPassword']	= true;
                    $aEmail['OutgoingMailServerAuthentication']	= $sOutgoingMailServerAuthentication;
                }
            }

            $result = $this->_generateDict($oXmlDocument, $aEmail);
        }

        return $result;
    }

    private function getAuthenticatedAccountPassword()
    {
        $aUserInfo = Api::getAuthenticatedUserInfo();

        $sAccountPassword = '';

        if (isset($aUserInfo['account']) && isset($aUserInfo['accountType'])) {
            $oAccount = call_user_func_array([$aUserInfo['accountType'], 'find'], [(int)$aUserInfo['account']]);
            if ($oAccount) {
                $sAccountPassword = $oAccount->getPassword();
            }
        }

        return $sAccountPassword;
    }

    /**
     *
     * @param \DOMDocument $oXmlDocument
     * @param string $sPayloadId
     * @param \Aurora\Modules\Core\Models\User $oUser
     * @param bool $bIsDemo Default false
     *
     * @return \DOMElement
     */
    private function _generateCaldavDict($oXmlDocument, $sPayloadId, $oUser, $bIsDemo = false)
    {
        Api::Log('Generating caldav part for user: ' . $oUser->PublicId);

        $oModuleManager = Api::GetModuleManager();
        $bIncludePasswordInProfile = $this->oModule->oModuleSettings->IncludePasswordInProfile;
        $aCaldav = array(
            'PayloadVersion'			=> 1,
            'PayloadUUID'				=> \Sabre\DAV\UUIDUtil::getUUID(),
            'PayloadType'				=> 'com.apple.caldav.account',
            'PayloadIdentifier'			=> $sPayloadId . '.caldav',
            'PayloadDisplayName'		=> $oUser->PublicId . ' - CalDAV Account',
            'PayloadOrganization'		=> $oModuleManager->getModuleConfigValue('Core', 'SiteName'),
            'PayloadDescription'		=> 'Configures CalDAV Account',
            'CalDAVAccountDescription'	=> $oModuleManager->getModuleConfigValue('Core', 'SiteName') . ' Calendars',
            'CalDAVHostName'			=> $this->oDavModule ? $this->oDavModule->GetServerHost() : '',
            'CalDAVUsername'			=> $oUser->PublicId,
            'CalDAVPassword'			=> $bIsDemo ? 'demo' : ($bIncludePasswordInProfile ? $this->getAuthenticatedAccountPassword() : ''),
            'CalDAVUseSSL'				=> $this->oDavModule ? $this->oDavModule->IsSsl() : '',
            'CalDAVPort'				=> $this->oDavModule ? $this->oDavModule->GetServerPort() : '',
            'CalDAVPrincipalURL'		=> $this->oDavModule ? $this->oDavModule->GetPrincipalUrl() : '',
        );

        return $this->_generateDict($oXmlDocument, $aCaldav);
    }

    /**
     *
     * @param \DOMDocument $oXmlDocument
     * @param string $sPayloadId
     * @param \Aurora\Modules\Core\Models\User $oUser
     * @param bool $bIsDemo Default false
     *
     * @return \DOMElement
     */

    private function _generateCarddavDict($oXmlDocument, $sPayloadId, $oUser, $bIsDemo = false)
    {
        Api::Log('Generating carddav part for user: ' . $oUser->PublicId);

        $oModuleManager = Api::GetModuleManager();
        $bIncludePasswordInProfile = $this->oModule->oModuleSettings->IncludePasswordInProfile;
        $aCarddav = array(
            'PayloadVersion'			=> 1,
            'PayloadUUID'				=> \Sabre\DAV\UUIDUtil::getUUID(),
            'PayloadType'				=> 'com.apple.carddav.account',
            'PayloadIdentifier'			=> $sPayloadId . '.carddav',
            'PayloadDisplayName'		=> $oUser->PublicId . ' - CardDAV Account',
            'PayloadOrganization'		=> $oModuleManager->getModuleConfigValue('Core', 'SiteName'),
            'PayloadDescription'		=> 'Configures CardDAV Account',
            'CardDAVAccountDescription'	=> $oModuleManager->getModuleConfigValue('Core', 'SiteName') . ' Contacts',
            'CardDAVHostName'			=> $this->oDavModule ? $this->oDavModule->GetServerHost() : '',
            'CardDAVUsername'			=> $oUser->PublicId,
            'CardDAVPassword'			=> $bIsDemo ? 'demo' : ($bIncludePasswordInProfile ? $this->getAuthenticatedAccountPassword() : ''),
            'CardDAVUseSSL'				=> $this->oDavModule ? $this->oDavModule->IsSsl() : '',
            'CardDAVPort'				=> $this->oDavModule ? $this->oDavModule->GetServerPort() : '',
            'CardDAVPrincipalURL'		=> $this->oDavModule ? $this->oDavModule->GetPrincipalUrl() : '',
        );

        return $this->_generateDict($oXmlDocument, $aCarddav);
    }

    /**
     * @param \Aurora\Modules\Core\Models\User $oUser
     * @return string
     */
    public function generateXMLProfile($oUser)
    {
        Api::Log('Generating Ios profile.');

        $mResult = false;

        if ($oUser) {
            $oDomImplementation = new \DOMImplementation();
            $oDocumentType = $oDomImplementation->createDocumentType(
                'plist',
                '-//Apple//DTD PLIST 1.0//EN',
                'http://www.apple.com/DTDs/PropertyList-1.0.dtd'
            );

            $oXmlDocument = $oDomImplementation->createDocument('', '', $oDocumentType);
            $oXmlDocument->xmlVersion = '1.0';
            $oXmlDocument->encoding = 'UTF-8';
            $oXmlDocument->formatOutput = true;

            $oPlist = $oXmlDocument->createElement('plist');
            $oPlist->setAttribute('version', '1.0');

            $sPayloadId = $this->oDavModule ? 'afterlogic.' . $this->oDavModule->GetServerHost() . '.' . $oUser->PublicId : '';

            $oModuleManager = Api::GetModuleManager();
            $aPayload = array(
                'PayloadVersion'			=> 1,
                'PayloadUUID'				=> \Sabre\DAV\UUIDUtil::getUUID(),
                'PayloadType'				=> 'Configuration',
                'PayloadRemovalDisallowed'	=> false,
                'PayloadIdentifier'			=> $sPayloadId,
                'PayloadOrganization'		=> $oModuleManager->getModuleConfigValue('Core', 'SiteName'),
                'PayloadDescription'		=> $oModuleManager->getModuleConfigValue('Core', 'SiteName') . ' Mobile',
                'PayloadDisplayName'		=> $oModuleManager->getModuleConfigValue('Core', 'SiteName') . ' (' . $oUser->PublicId . ') Mobile Profile',
            );

            $oArrayElement = $oXmlDocument->createElement('array');

            $bIsDemo = false;

            if (class_exists('\Aurora\Modules\DemoModePlugin\Module')) {
                $oDemoModePlugin = \Aurora\Modules\DemoModePlugin\Module::getInstance();
                if ($oDemoModePlugin && $oDemoModePlugin->IsDemoUser()) {
                    $bIsDemo = true;
                }
            }

            if (class_exists('\Aurora\Modules\Mail\Module')) {
                $oMailModule = Api::GetModule('Mail');
                /** @var \Aurora\Modules\Mail\Module $oMailModule */
                if ($oMailModule && !$bIsDemo) {
                    $aAccounts = $oMailModule->GetAccounts($oUser->Id);
                    if (is_array($aAccounts) && 0 < count($aAccounts)) {
                        foreach ($aAccounts as $oAccountItem) {
                            $oEmailDictElement = $this->_generateEmailDict($oXmlDocument, $sPayloadId, $oAccountItem, $bIsDemo);

                            if ($oEmailDictElement === false) {
                                Api::Log('Error: incorrect email part is for account: ' . $oAccountItem->Email);
                                return false;
                            } else {
                                $oArrayElement->appendChild($oEmailDictElement);
                            }

                            unset($oAccountItem);
                            unset($oEmailDictElement);
                        }
                    }
                }
            }

            if (class_exists('\Aurora\Modules\MobileSync\Module')) {
                // $oMobileSyncModule = Api::GetModule('MobileSync');
                $oMobileSyncModule = \Aurora\Modules\MobileSync\Module::getInstance();

                if ($oMobileSyncModule && !$oMobileSyncModule->oModuleSettings->Disabled) {
                    // Calendars
                    $oCaldavDictElement = $this->_generateCaldavDict($oXmlDocument, $sPayloadId, $oUser, $bIsDemo);
                    $oArrayElement->appendChild($oCaldavDictElement);

                    // Contacts
                    $oCarddavDictElement = $this->_generateCarddavDict($oXmlDocument, $sPayloadId, $oUser, $bIsDemo);
                    $oArrayElement->appendChild($oCarddavDictElement);
                }
            }

            $oDictElement = $this->_generateDict($oXmlDocument, $aPayload);
            $oPayloadContentElement = $oXmlDocument->createElement('key', 'PayloadContent');
            $oDictElement->appendChild($oPayloadContentElement);
            $oDictElement->appendChild($oArrayElement);
            $oPlist->appendChild($oDictElement);

            $oXmlDocument->appendChild($oPlist);
            $mResult = $oXmlDocument->saveXML();
        }

        return $mResult;
    }
}
