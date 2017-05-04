<?php
/**
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or AfterLogic Software License
 *
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

/**
 * @package IOS
 */
class CApiIosManager extends \Aurora\System\Managers\AbstractManager
{
	/*
	 * @var $oApiUsersManager CApiUsersManager
	 */
	private $oApiUsersManager;

	/*
	 * @var $oApiDavManager CApiDavManager
	 */
	private $oApiDavManager;

	/**
	 * @param \Aurora\System\Managers\GlobalManager &$oManager
	 * @param string $sForcedStorage
	 */
	public function __construct(\Aurora\System\Managers\GlobalManager &$oManager, $sForcedStorage = '')
	{
		parent::__construct('', $oManager);

		/*
		 * @var $oApiUsersManager CApiUsersManager
		 */
//		$this->oApiUsersManager =\Aurora\System\Api::GetSystemManager('users');

		/*
		 * @var $oApiDavManager CApiDavManager
		 */
		$this->oApiDavManager =\Aurora\System\Api::Manager('dav');
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
	 * @param \CAccount $oAccount
	 * @param bool $bIsDemo Default false
	 * 
	 * @return boolean
	 */
	private function _generateEmailDict($oXmlDocument, $sPayloadId, $oAccount, $bIsDemo = false)
	{
		$oSettings =\Aurora\System\Api::GetSettings();

		$sIncomingServer = $oAccount->IncomingServer;
		$iIncomingPort = $oAccount->IncomingPort;

		if ($sIncomingServer == 'localhost' || $sIncomingServer == '127.0.0.1')
		{
			$sIncomingServer = $oSettings->GetConf('WebMail/ExternalHostNameOfLocalImap');
			
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

		$sOutgoingServer = $oAccount->OutgoingServer;
		$iOutgoingPort = $oAccount->OutgoingPort;

		if ($sOutgoingServer == 'localhost' || $sOutgoingServer == '127.0.0.1')
		{
			$sOutgoingServer = $oSettings->GetConf('WebMail/ExternalHostNameOfLocalSmtp');
			
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
		if (empty($sIncomingServer) || empty($sOutgoingServer))
		{
			return false;
		}

		$oSettings =&\Aurora\System\Api::GetSettings();
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
			'OutgoingMailServerUsername'		=> 0 === strlen($oAccount->OutgoingLogin)
				? $oAccount->IncomingLogin : $oAccount->OutgoingLogin,
			'OutgoingPassword'					=> $bIsDemo ? 'demo' : ($bIncludePasswordInProfile ? (0 === strlen($oAccount->OutgoingPassword)
				? $oAccount->IncomingPassword : $oAccount->OutgoingPassword) : ''),
			'OutgoingMailServerAuthentication'	=> $oAccount->OutgoingUseAuth
				? 'EmailAuthPassword' : 'EmailAuthNone',
		);

		return $this->_generateDict($oXmlDocument, $aEmail);
	}

	/**
	 * 
	 * @param type $oXmlDocument
	 * @param string $sPayloadId
	 * @param \CAccount $oAccount
	 * @param bool $bIsDemo Default false
	 * 
	 * @return DOMElement
	 */
	private function _generateCaldavDict($oXmlDocument, $sPayloadId, $oAccount, $bIsDemo = false)
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
			'CalDAVHostName'			=> $this->oApiDavManager ? $this->oApiDavManager->getServerHost($oAccount) : '',
			'CalDAVUsername'			=> $oAccount->Email,
			'CalDAVPassword'			=> $bIsDemo ? 'demo' : ($bIncludePasswordInProfile ? $oAccount->IncomingPassword : ''),
			'CalDAVUseSSL'				=> $this->oApiDavManager ? $this->oApiDavManager->isUseSsl($oAccount) : '',
			'CalDAVPort'				=> $this->oApiDavManager ? $this->oApiDavManager->getServerPort($oAccount) : '',
			'CalDAVPrincipalURL'		=> $this->oApiDavManager ? $this->oApiDavManager->getPrincipalUrl($oAccount) : '',
		);

		return $this->_generateDict($oXmlDocument, $aCaldav);
	}

	/**
	 * 
	 * @param type $oXmlDocument
	 * @param string $sPayloadId
	 * @param \CAccount $oAccount
	 * @param bool $bIsDemo Default false
	 * 
	 * @return DOMElement
	 */
	
	private function _generateCarddavDict($oXmlDocument, $sPayloadId, $oAccount, $bIsDemo = false)
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
			'CardDAVHostName'			=> $this->oApiDavManager ? $this->oApiDavManager->getServerHost($oAccount) : '',
			'CardDAVUsername'			=> $oAccount->Email,
			'CardDAVPassword'			=> $bIsDemo ? 'demo' : ($bIncludePasswordInProfile ? $oAccount->IncomingPassword : ''),
			'CardDAVUseSSL'				=> $this->oApiDavManager ? $this->oApiDavManager->isUseSsl($oAccount) : '',
			'CardDAVPort'				=> $this->oApiDavManager ? $this->oApiDavManager->getServerPort($oAccount) : '',
			'CardDAVPrincipalURL'		=> $this->oApiDavManager ? $this->oApiDavManager->getPrincipalUrl($oAccount) : '',
		);

		return $this->_generateDict($oXmlDocument, $aCarddav);
	}

	/**
	 * @param \CAccount $oAccount
	 * @return string
	 */
	public function generateXMLProfile($oAccount)
	{
		$mResult = false;

		if ($oAccount)
		{
			$oDomImplementation = new DOMImplementation();
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

			$sPayloadId = $this->oApiDavManager ? 'afterlogic.'.$this->oApiDavManager->getServerHost($oAccount) : '';
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

			$bIsDemo = false;

			if (!$bIsDemo)
			{
				$aInfo = $this->oApiUsersManager->getUserAccounts($oAccount->IdUser);
				if (is_array($aInfo) && 0 < count($aInfo))
				{
					foreach (array_keys($aInfo) as $iIdAccount)
					{
						if ($oAccount->IdAccount === $iIdAccount)
						{
							$oAccountItem = $oAccount;
						}
						else
						{
							$oAccountItem = $this->oApiUsersManager->getAccountById($iIdAccount);
						}

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
				else
				{
					return false;
				}
			}

			// Calendars
			$oCaldavDictElement = $this->_generateCaldavDict($oXmlDocument, $sPayloadId, $oAccount, $bIsDemo);
			$oArrayElement->appendChild($oCaldavDictElement);

			// Contacts
			$oCarddavDictElement = $this->_generateCarddavDict($oXmlDocument, $sPayloadId, $oAccount, $bIsDemo);
			$oArrayElement->appendChild($oCarddavDictElement);

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
