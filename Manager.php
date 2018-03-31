<?php
/**
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or AfterLogic Software License
 *
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */


namespace Aurora\Modules\Ios;

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
	 * @param type $oXmlDocument
	 * @param array $aPayload
	 * 
	 * @return DOMElement
	 */
	private function _generateDict($oXmlDocument, $aPayload)
	{
		$oDictElement = $oXmlDocument->createElement('dict');

		foreach ($aPayload as $sKey => $mValue)
		{
			$oDictElement->appendChild($oXmlDocument->createElement('key', $sKey));

			if (is_int($mValue))
			{
				$oDictElement->appendChild($oXmlDocument->createElement('integer', $mValue));
			}
			else if (is_bool($mValue))
			{
				$oDictElement->appendChild($oXmlDocument->createElement($mValue ? 'true': 'false'));
			}
			else
			{
				$oDictElement->appendChild($oXmlDocument->createElement('string', $mValue));
			}
		}
		return $oDictElement;
	}

	/**
	 * 
	 * @param type $oXmlDocument
	 * @param string $sPayloadId
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
	 * @param bool $bIsDemo Default false
	 * 
	 * @return boolean
	 */
	private function _generateEmailDict($oXmlDocument, $sPayloadId, $oAccount, $bIsDemo = false)
	{
		$oSettings =\Aurora\System\Api::GetSettings();
		
		$oServer = $oAccount->GetServer();

		$sIncomingServer = $oServer->IncomingServer;
		$iIncomingPort = $oServer->IncomingPort;

		if ($sIncomingServer == 'localhost' || $sIncomingServer == '127.0.0.1')
		{
			$sIncomingServer = $oSettings->GetConf('ExternalHostNameOfLocalImap', $sIncomingServer);
			
			if (!empty($sIncomingServer))
			{
				$aParsedUrl = parse_url($sIncomingServer);
				if (isset($aParsedUrl['host']))
				{
					$sIncomingServer = $aParsedUrl['host'];
				}
				if (isset($aParsedUrl['port']))
				{
					$iIncomingPort = $aParsedUrl['port'];
				}
			}
		}

		$sOutgoingServer = $oServer->IncomingServer;
		$iOutgoingPort = $oServer->IncomingPort;

		if ($sOutgoingServer == 'localhost' || $sOutgoingServer == '127.0.0.1')
		{
			$sOutgoingServer = $oSettings->GetConf('ExternalHostNameOfLocalSmtp', $sOutgoingServer);
			
			if (!empty($sOutgoingServer))
			{
				$aParsedUrl = parse_url($sOutgoingServer);
				if (isset($aParsedUrl['host']))
				{
					$sOutgoingServer = $aParsedUrl['host'];
				}
				if (isset($aParsedUrl['port']))
				{
					$iOutgoingPort = $aParsedUrl['port'];
				}
			}
		}
		
		if (empty($sIncomingServer) || empty($sOutgoingServer))
		{
			return false;
		}

		$bIncludePasswordInProfile = $this->GetModule()->getConfig('IncludePasswordInProfile', true);
		$aEmail = array(
			'PayloadVersion'					=> 1,
			'PayloadUUID'						=> \Sabre\DAV\UUIDUtil::getUUID(),
			'PayloadType'						=> 'com.apple.mail.managed',
			'PayloadIdentifier'					=> $sPayloadId.'.email',
			'PayloadDisplayName'				=> 'Email Account',
			'PayloadOrganization'				=> $oSettings->GetConf('SiteName'),
			'PayloadDescription'				=> 'Configures email account',
			'EmailAddress'						=> $oAccount->Email,
			'EmailAccountType'					=> 'EmailTypeIMAP',
			'EmailAccountDescription'			=> $oAccount->Email,
			'EmailAccountName'					=> 0 === strlen($oAccount->FriendlyName)
				? $oAccount->Email : $oAccount->FriendlyName,
			'IncomingMailServerHostName'		=> $sIncomingServer,
			'IncomingMailServerPortNumber'		=> $iIncomingPort,
			'IncomingMailServerUseSSL'			=> 993 === $iIncomingPort,
			'IncomingMailServerUsername'		=> $oAccount->IncomingLogin,
			'IncomingPassword'					=> $bIsDemo ? 'demo' : ($bIncludePasswordInProfile ? $oAccount->IncomingPassword : ''),
			'IncomingMailServerAuthentication'	=> 'EmailAuthPassword',
			'OutgoingMailServerHostName'		=> $sOutgoingServer,
			'OutgoingMailServerPortNumber'		=> $iOutgoingPort,
			'OutgoingMailServerUseSSL'			=> 465 === $iIncomingPort,
			'OutgoingMailServerUsername'		=> $oServer->SmtpAuthType === \Aurora\Modules\Mail\Enums\SmtpAuthType::UseSpecifiedCredentials 
				? $oServer->SmtpLogin : $oAccount->IncomingLogin,
			'OutgoingPassword'					=> $bIsDemo ? 'demo' : ($bIncludePasswordInProfile ? ($oServer->SmtpAuthType === \Aurora\Modules\Mail\Enums\SmtpAuthType::UseSpecifiedCredentials
				? $oServer->SmtpPassword : $oAccount->IncomingPassword) : ''),
			'OutgoingMailServerAuthentication'	=> $oServer->SmtpAuthType === \Aurora\Modules\Mail\Enums\SmtpAuthType::NoAuthentication
				? 'EmailAuthNone' : 'EmailAuthPassword',
		);

		return $this->_generateDict($oXmlDocument, $aEmail);
	}

	/**
	 * 
	 * @param type $oXmlDocument
	 * @param string $sPayloadId
	 * @param \Aurora\Modules\Core\Classes\User $oUser
	 * @param bool $bIsDemo Default false
	 * 
	 * @return DOMElement
	 */
	private function _generateCaldavDict($oXmlDocument, $sPayloadId, $oUser, $bIsDemo = false)
	{
		$oSettings =&\Aurora\System\Api::GetSettings();
		$bIncludePasswordInProfile = $this->GetModule()->getConfig('IncludePasswordInProfile', true);
		$aCaldav = array(
			'PayloadVersion'			=> 1,
			'PayloadUUID'				=> \Sabre\DAV\UUIDUtil::getUUID(),
			'PayloadType'				=> 'com.apple.caldav.account',
			'PayloadIdentifier'			=> $sPayloadId.'.caldav',
			'PayloadDisplayName'		=> 'CalDAV Account',
			'PayloadOrganization'		=> $oSettings->GetConf('SiteName'),
			'PayloadDescription'		=> 'Configures CalDAV Account',
			'CalDAVAccountDescription'	=> $oSettings->GetConf('SiteName').' Calendars',
			'CalDAVHostName'			=> $this->oDavModule ? $this->oDavModule->GetServerHost() : '',
			'CalDAVUsername'			=> $oUser->PublicId,
			'CalDAVPassword'			=> $bIsDemo ? 'demo' : ($bIncludePasswordInProfile ? $oUser->IncomingPassword : ''),
			'CalDAVUseSSL'				=> $this->oDavModule ? $this->oDavModule->IsSsl() : '',
			'CalDAVPort'				=> $this->oDavModule ? $this->oDavModule->GetServerPort() : '',
			'CalDAVPrincipalURL'		=> $this->oDavModule ? $this->oDavModule->GetPrincipalUrl() : '',
		);

		return $this->_generateDict($oXmlDocument, $aCaldav);
	}

	/**
	 * 
	 * @param type $oXmlDocument
	 * @param string $sPayloadId
	 * @param \Aurora\Modules\Core\Classes\User $oUser
	 * @param bool $bIsDemo Default false
	 * 
	 * @return DOMElement
	 */
	
	private function _generateCarddavDict($oXmlDocument, $sPayloadId, $oUser, $bIsDemo = false)
	{
		$oSettings =&\Aurora\System\Api::GetSettings();
		$bIncludePasswordInProfile = $this->GetModule()->getConfig('IncludePasswordInProfile', true);
		$aCarddav = array(
			'PayloadVersion'			=> 1,
			'PayloadUUID'				=> \Sabre\DAV\UUIDUtil::getUUID(),
			'PayloadType'				=> 'com.apple.carddav.account',
			'PayloadIdentifier'			=> $sPayloadId.'.carddav',
			'PayloadDisplayName'		=> 'CardDAV Account',
			'PayloadOrganization'		=> $oSettings->GetConf('SiteName'),
			'PayloadDescription'		=> 'Configures CardDAV Account',
			'CardDAVAccountDescription'	=> $oSettings->GetConf('SiteName').' Contacts',
			'CardDAVHostName'			=> $this->oDavModule ? $this->oDavModule->GetServerHost() : '',
			'CardDAVUsername'			=> $oUser->PublicId,
			'CardDAVPassword'			=> $bIsDemo ? 'demo' : ($bIncludePasswordInProfile ? $oUser->IncomingPassword : ''),
			'CardDAVUseSSL'				=> $this->oDavModule ? $this->oDavModule->IsSsl() : '',
			'CardDAVPort'				=> $this->oDavModule ? $this->oDavModule->GetServerPort() : '',
			'CardDAVPrincipalURL'		=> $this->oDavModule ? $this->oDavModule->GetPrincipalUrl() : '',
		);

		return $this->_generateDict($oXmlDocument, $aCarddav);
	}

	/**
	 * @param \Aurora\Modules\Core\Classes\User $oUser
	 * @return string
	 */
	public function generateXMLProfile($oUser)
	{
		$mResult = false;

		if ($oUser)
		{
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

			$sPayloadId = $this->oDavModule ? 'afterlogic.'.$this->oDavModule->GetServerHost() : '';
			
			$oSettings =&\Aurora\System\Api::GetSettings();
			$aPayload = array(
				'PayloadVersion'			=> 1,
				'PayloadUUID'				=> \Sabre\DAV\UUIDUtil::getUUID(),
				'PayloadType'				=> 'Configuration',
				'PayloadRemovalDisallowed'	=> false,
				'PayloadIdentifier'			=> $sPayloadId,
				'PayloadOrganization'		=> $oSettings->GetConf('SiteName'),
				'PayloadDescription'		=> $oSettings->GetConf('SiteName').' Mobile',
				'PayloadDisplayName'		=> $oSettings->GetConf('SiteName').' Mobile Profile',
//				'ConsentText'				=> 'AfterLogic Profile @ConsentText',
			);

			$oArrayElement = $oXmlDocument->createElement('array');

			$oDemoModePlugin = \Aurora\System\Api::GetModule('DemoModePlugin');
			if (!($oDemoModePlugin && $oDemoModePlugin->IsDemo()))
			{
				$oMailModule = \Aurora\System\Api::GetModule('Mail');
				if ($oMailModule)
				{
					$aAccounts = $oMailModule->GetAccounts($oUser->EntityId);
					if (is_array($aAccounts) && 0 < count($aAccounts))
					{
						foreach ($aAccounts as $oAccountItem)
						{
							$oEmailDictElement = $this->_generateEmailDict($oXmlDocument, $sPayloadId, $oAccountItem, $bIsDemo);

							if ($oEmailDictElement === false)
							{
								return false;
							}
							else
							{
								$oArrayElement->appendChild($oEmailDictElement);
							}

							unset($oAccountItem);
							unset($oEmailDictElement);
						}
					}
				}
			}

			
			$oMobileSyncModule = \Aurora\System\Api::GetModule('MobileSync');
			if ($oMobileSyncModule && !$oMobileSyncModule->getConfig('Disabled', false))
			{
				// Calendars
				$oCaldavDictElement = $this->_generateCaldavDict($oXmlDocument, $sPayloadId, $oUser, $bIsDemo);
				$oArrayElement->appendChild($oCaldavDictElement);

				// Contacts
				$oCarddavDictElement = $this->_generateCarddavDict($oXmlDocument, $sPayloadId, $oUser, $bIsDemo);
				$oArrayElement->appendChild($oCarddavDictElement);
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
