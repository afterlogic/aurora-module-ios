<?php
/**
 * @copyright Copyright (c) 2016, Afterlogic Corp.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 * 
 * @package Modules
 */

namespace Aurora\Modules;

class IosModule extends \AApiModule
{
	/***** private functions *****/
	/**
	 * Initializes IOS Module.
	 * 
	 * @ignore
	 */
	public function init() 
	{
		parent::init();
		
		$this->AddEntries(array(
				'ios' => 'EntryIos',
				'profile' => 'EntryProfile'
			)
		);
	}
	/***** private functions *****/
	
	/***** public functions *****/
	/**
	 * @ignore
	 * @return string
	 */
	public function EntryIos()
	{
		$sResult = \file_get_contents($this->GetPath().'/templates/Ios.html');
		
		$oApiIntegrator = \CApi::GetSystemManager('integrator');
		$iUserId = \CApi::getAuthenticatedUserId();
		if (0 < $iUserId)
		{
			$oAccount = $oApiIntegrator->getAuthenticatedDefaultAccount();
			$aPaths = \System\Service::GetPaths();
			$bError = isset($aPaths[1]) && 'error' === \strtolower($aPaths[1]); // TODO
			
			@\setcookie('skip_ios', '1', \time() + 3600 * 3600, '/', null, null, true);
			
			$sResult = strtr($sResult, array(
				'{{IOS/HELLO}}' => \CApi::ClientI18N('IOS/HELLO', $oAccount),
				'{{IOS/DESC_P1}}' => \CApi::ClientI18N('IOS/DESC_P1', $oAccount),
				'{{IOS/DESC_P2}}' => \CApi::ClientI18N('IOS/DESC_P2', $oAccount),
				'{{IOS/DESC_P3}}' => \CApi::ClientI18N('IOS/DESC_P3', $oAccount),
				'{{IOS/DESC_P4}}' => \CApi::ClientI18N('IOS/DESC_P4', $oAccount),
				'{{IOS/DESC_P5}}' => \CApi::ClientI18N('IOS/DESC_P5', $oAccount),
				'{{IOS/DESC_P6}}' => \CApi::ClientI18N('IOS/DESC_P6', $oAccount),
				'{{IOS/DESC_P7}}' => \CApi::ClientI18N('IOS/DESC_P7', $oAccount),
				'{{IOS/DESC_BUTTON_YES}}' => \CApi::ClientI18N('IOS/DESC_BUTTON_YES', $oAccount),
				'{{IOS/DESC_BUTTON_SKIP}}' => \CApi::ClientI18N('IOS/DESC_BUTTON_SKIP', $oAccount),
				'{{IOS/DESC_BUTTON_OPEN}}' => \CApi::ClientI18N('IOS/DESC_BUTTON_OPEN', $oAccount),
				'{{AppVersion}}' => AURORA_APP_VERSION,
				'{{IntegratorLinks}}' => $oApiIntegrator->buildHeadersLink()
			));
		}
		else
		{
			\CApi::Location('./');
		}
		
		return $sResult;
	}
	
	/**
	 * @ignore
	 */
	public function EntryProfile()
	{
		/* @var $oApiIosManager \CApiIosManager */
		$oApiIosManager = \CApi::GetSystemManager('ios');
		
		$oApiIntegrator = \CApi::GetSystemManager('integrator');
		$oAccount = $oApiIntegrator->getAuthenticatedDefaultAccount();
		
		$mResultProfile = $oApiIosManager && $oAccount ? $oApiIosManager->generateXMLProfile($oAccount) : false;
		
		if (!$mResultProfile)
		{
			\CApi::Location('./?IOS/Error');
		}
		else
		{
			\header('Content-type: application/x-apple-aspen-config; chatset=utf-8');
			\header('Content-Disposition: attachment; filename="afterlogic.mobileconfig"');
			echo $mResultProfile;
		}
	}
	/***** public functions *****/
}
