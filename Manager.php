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
    /*
     * @var $oDavModule \Aurora\Modules\Dav\Module
     */
    private $oDavModule;

    /**
     * @param \Aurora\System\Module\AbstractModule $oModule
     */
    public function __construct($oModule = null)
    {
        parent::__construct($oModule);

        /*
         * @var $oApiDavManager CApiDavManager
         */
        $this->oDavModule = \Aurora\System\Api::GetModule('Dav');
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
     * @param \Aurora\Modules\Mail\Models\MailAccount $oAccount
     * @param bool $bIsDemo Default false
     *
     * @return boolean
     */
    private function _generateEmailDict($oXmlDocument, $sPayloadId, $oAccount, $bIsDemo = false)
    {
        $oModuleManager = \Aurora\System\Api::GetModuleManager();

        $oServer = $oAccount->GetServer();

        $sIncomingServer = $oServer->IncomingServer;
        $iIncomingPort = $oServer->IncomingPort;
        $bIncomingUseSsl = $oServer->IncomingUseSsl;

        if ($sIncomingServer == 'localhost' || $sIncomingServer == '127.0.0.1') {
            $sIncomingServer = $oServer->ExternalAccessImapServer;

            if (!empty($sIncomingServer)) {
                $iIncomingPort = $oServer->ExternalAccessImapPort;
                $bIncomingUseSsl = $oServer->ExternalAccessImapUseSsl;
            }
        }

        $sOutgoingServer = $oServer->OutgoingServer;
        $iOutgoingPort = $oServer->OutgoingPort;
        $bOutgoingUseSsl = $oServer->OutgoingUseSsl;

        if ($sOutgoingServer == 'localhost' || $sOutgoingServer == '127.0.0.1') {
            $sOutgoingServer = $oServer->ExternalAccessSmtpServer;

            if (!empty($sOutgoingServer)) {
                $iOutgoingPort = $oServer->ExternalAccessSmtpPort;
                $bOutgoingUseSsl = $oServer->ExternalAccessSmtpUseSsl;
            }
        }

        if (empty($sIncomingServer) || empty($sOutgoingServer)) {
            return false;
        }

        $bIncludePasswordInProfile = $this->oModule->oModuleSettings->IncludePasswordInProfile;
        $sOutgoingMailServerUsername = $oAccount->IncomingLogin;
        $sOutgoingPassword = $oAccount->getPassword();
        $sOutgoingMailServerAuthentication = 'EmailAuthPassword';
        if (class_exists('\Aurora\Modules\Mail\Enums\SmtpAuthType')) {
            if ($oServer->SmtpAuthType === \Aurora\Modules\Mail\Enums\SmtpAuthType::UseSpecifiedCredentials) {
                $sOutgoingMailServerUsername = $oServer->SmtpLogin;
                $sOutgoingPassword = $oServer->SmtpLogin;
            }
            if ($oServer->SmtpAuthType === \Aurora\Modules\Mail\Enums\SmtpAuthType::NoAuthentication) {
                $sOutgoingMailServerAuthentication = 'EmailAuthNone';
            }
        }

        $aEmail = array(
            'PayloadVersion'					=> 1,
            'PayloadUUID'						=> \Sabre\DAV\UUIDUtil::getUUID(),
            'PayloadType'						=> 'com.apple.mail.managed',
            'PayloadIdentifier'					=> $sPayloadId.'.' . $oAccount->Email . '.email',
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
            'OutgoingMailServerHostName'		=> $sOutgoingServer,
            'OutgoingMailServerPortNumber'		=> $iOutgoingPort,
            'OutgoingMailServerUseSSL'			=> $bOutgoingUseSsl,
            'OutgoingMailServerUsername'		=> $sOutgoingMailServerUsername,
            'OutgoingPassword'					=> $bIsDemo ? 'demo' : ($bIncludePasswordInProfile ? $sOutgoingPassword : ''),
            'OutgoingMailServerAuthentication'	=> $sOutgoingMailServerAuthentication,
        );

        return $this->_generateDict($oXmlDocument, $aEmail);
    }

    private function getAuthenticatedAccountPassword()
    {
        $aUserInfo = \Aurora\System\Api::getAuthenticatedUserInfo();

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
        $oModuleManager = \Aurora\System\Api::GetModuleManager();
        $bIncludePasswordInProfile = $this->oModule->oModuleSettings->IncludePasswordInProfile;
        $aCaldav = array(
            'PayloadVersion'			=> 1,
            'PayloadUUID'				=> \Sabre\DAV\UUIDUtil::getUUID(),
            'PayloadType'				=> 'com.apple.caldav.account',
            'PayloadIdentifier'			=> $sPayloadId.'.caldav',
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
        $oModuleManager = \Aurora\System\Api::GetModuleManager();
        $bIncludePasswordInProfile = $this->oModule->oModuleSettings->IncludePasswordInProfile;
        $aCarddav = array(
            'PayloadVersion'			=> 1,
            'PayloadUUID'				=> \Sabre\DAV\UUIDUtil::getUUID(),
            'PayloadType'				=> 'com.apple.carddav.account',
            'PayloadIdentifier'			=> $sPayloadId.'.carddav',
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

            $sPayloadId = $this->oDavModule ? 'afterlogic.'.$this->oDavModule->GetServerHost() . '.' . $oUser->PublicId : '';

            $oModuleManager = \Aurora\System\Api::GetModuleManager();
            $aPayload = array(
                'PayloadVersion'			=> 1,
                'PayloadUUID'				=> \Sabre\DAV\UUIDUtil::getUUID(),
                'PayloadType'				=> 'Configuration',
                'PayloadRemovalDisallowed'	=> false,
                'PayloadIdentifier'			=> $sPayloadId,
                'PayloadOrganization'		=> $oModuleManager->getModuleConfigValue('Core', 'SiteName'),
                'PayloadDescription'		=> $oModuleManager->getModuleConfigValue('Core', 'SiteName') . ' Mobile',
                'PayloadDisplayName'		=> $oModuleManager->getModuleConfigValue('Core', 'SiteName') . ' (' . $oUser->PublicId  . ') Mobile Profile',
            );

            $oArrayElement = $oXmlDocument->createElement('array');

            /** @var \Aurora\Modules\DemoModePlugin\Module $oDemoModePlugin */
            $oDemoModePlugin = \Aurora\System\Api::GetModule('DemoModePlugin');
            $bIsDemo = false;
            if (!($oDemoModePlugin && $oDemoModePlugin->IsDemoUser())) {
                $oMailModule = \Aurora\System\Api::GetModule('Mail');
                /** @var \Aurora\Modules\Mail\Module $oMailModule */
                if ($oMailModule) {
                    $aAccounts = $oMailModule->GetAccounts($oUser->Id);
                    if (is_array($aAccounts) && 0 < count($aAccounts)) {
                        foreach ($aAccounts as $oAccountItem) {
                            $oEmailDictElement = $this->_generateEmailDict($oXmlDocument, $sPayloadId, $oAccountItem, $bIsDemo);

                            if ($oEmailDictElement === false) {
                                return false;
                            } else {
                                $oArrayElement->appendChild($oEmailDictElement);
                            }

                            unset($oAccountItem);
                            unset($oEmailDictElement);
                        }
                    }
                }
            } else {
                $bIsDemo = true;
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
