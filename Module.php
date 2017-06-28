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
		
		$oApiIntegrator = new \Aurora\Modules\Core\Managers\Integrator();
		$iUserId = \Aurora\System\Api::getAuthenticatedUserId();
		if (0 < $iUserId)
		{
			@\setcookie('skip_ios', '1', \time() + 3600 * 3600, '/', null, null, true);
			
			$sResult = strtr($sResult, array(
				'{{IOS/HELLO}}' => $this->i18N('HELLO', $iUserId),
				'{{IOS/DESC_P1}}' => $this->i18N('DESC_P1', $iUserId),
				'{{IOS/DESC_P2}}' => $this->i18N('DESC_P2', $iUserId),
				'{{IOS/DESC_P3}}' => $this->i18N('DESC_P3', $iUserId),
				'{{IOS/DESC_P4}}' => $this->i18N('DESC_P4', $iUserId),
				'{{IOS/DESC_P5}}' => $this->i18N('DESC_P5', $iUserId),
				'{{IOS/DESC_P6}}' => $this->i18N('DESC_P6', $iUserId),
				'{{IOS/DESC_P7}}' => $this->i18N('DESC_P7', $iUserId),
				'{{IOS/DESC_BUTTON_YES}}' => $this->i18N('DESC_BUTTON_YES', $iUserId),
				'{{IOS/DESC_BUTTON_SKIP}}' => $this->i18N('DESC_BUTTON_SKIP', $iUserId),
				'{{IOS/DESC_BUTTON_OPEN}}' => $this->i18N('DESC_BUTTON_OPEN', $iUserId),
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
		$oIosManager = new Managers\Ios();
		
		$oApiIntegrator = new \Aurora\Modules\Core\Managers\Integrator();
		$oAccount = $oApiIntegrator->getAuthenticatedDefaultAccount();
		
		$mResultProfile = $oIosManager && $oAccount ? $oIosManager->generateXMLProfile($oAccount) : false;
		
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
