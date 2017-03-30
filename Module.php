<?php
/**
 * @copyright Copyright (c) 2017, Afterlogic Corp.
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
 */

namespace Aurora\Modules\Ios;

/**
 * @package Modules
 */
class Module extends \Aurora\System\Module\AbstractModule
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
		
		$oApiIntegrator = \Aurora\System\Api::GetSystemManager('integrator');
		$iUserId = \Aurora\System\Api::getAuthenticatedUserId();
		if (0 < $iUserId)
		{
			$oAccount = $oApiIntegrator->getAuthenticatedDefaultAccount();

			@\setcookie('skip_ios', '1', \time() + 3600 * 3600, '/', null, null, true);
			
			$sResult = strtr($sResult, array(
				'{{IOS/HELLO}}' => \Aurora\System\Api::ClientI18N('IOS/HELLO', $oAccount),
				'{{IOS/DESC_P1}}' => \Aurora\System\Api::ClientI18N('IOS/DESC_P1', $oAccount),
				'{{IOS/DESC_P2}}' => \Aurora\System\Api::ClientI18N('IOS/DESC_P2', $oAccount),
				'{{IOS/DESC_P3}}' => \Aurora\System\Api::ClientI18N('IOS/DESC_P3', $oAccount),
				'{{IOS/DESC_P4}}' => \Aurora\System\Api::ClientI18N('IOS/DESC_P4', $oAccount),
				'{{IOS/DESC_P5}}' => \Aurora\System\Api::ClientI18N('IOS/DESC_P5', $oAccount),
				'{{IOS/DESC_P6}}' => \Aurora\System\Api::ClientI18N('IOS/DESC_P6', $oAccount),
				'{{IOS/DESC_P7}}' => \Aurora\System\Api::ClientI18N('IOS/DESC_P7', $oAccount),
				'{{IOS/DESC_BUTTON_YES}}' => \Aurora\System\Api::ClientI18N('IOS/DESC_BUTTON_YES', $oAccount),
				'{{IOS/DESC_BUTTON_SKIP}}' => \Aurora\System\Api::ClientI18N('IOS/DESC_BUTTON_SKIP', $oAccount),
				'{{IOS/DESC_BUTTON_OPEN}}' => \Aurora\System\Api::ClientI18N('IOS/DESC_BUTTON_OPEN', $oAccount),
				'{{AppVersion}}' => AURORA_APP_VERSION,
				'{{IntegratorLinks}}' => $oApiIntegrator->buildHeadersLink()
			));
		}
		else
		{
			\Aurora\System\Api::Location('./');
		}
		
		return $sResult;
	}
	
	/**
	 * @ignore
	 */
	public function EntryProfile()
	{
		/* @var $oApiIosManager \CApiIosManager */
		$oApiIosManager = \Aurora\System\Api::GetSystemManager('ios');
		
		$oApiIntegrator = \Aurora\System\Api::GetSystemManager('integrator');
		$oAccount = $oApiIntegrator->getAuthenticatedDefaultAccount();
		
		$mResultProfile = $oApiIosManager && $oAccount ? $oApiIosManager->generateXMLProfile($oAccount) : false;
		
		if (!$mResultProfile)
		{
			\Aurora\System\Api::Location('./?IOS/Error');
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
