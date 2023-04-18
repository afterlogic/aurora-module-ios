<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Ios;

use Aurora\System\Application;

/**
 * Allows to configure iOS device automatically for syncing mails, contacts and calendars using iOS profile.
 *
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2023, Afterlogic Corp.
 *
 * @package Modules
 */
class Module extends \Aurora\System\Module\AbstractModule
{
    /**
     *
     * @return Module
     */
    public static function Decorator()
    {
        return parent::Decorator();
    }

    /**
     *
     * @return Settings
     */
    protected function GetModuleSettings()
    {
        return $this->oModuleSettings;
    }

    /***** private functions *****/
    /**
     * Initializes IOS Module.
     *
     * @ignore
     */
    public function init()
    {
        $this->AddEntries(
            array(
                'ios' => 'EntryIos',
                'profile' => 'EntryProfile'
            )
        );
    }
    /***** private functions *****/

    /***** public functions *****/
    /**
     *
     * @return array
     */
    public function GetSettings()
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::Anonymous);

        return array(
            'AllowIosProfile' => $this->getConfig('AllowIosProfile', false),
            'SyncIosAfterLogin' => $this->getConfig('SyncIosAfterLogin', false)
        );
    }

    /**
     * @ignore
     * @return string
     */
    public function EntryIos()
    {
        $sResult = \file_get_contents($this->GetPath().'/templates/Ios.html');

        $oApiIntegrator = \Aurora\System\Managers\Integrator::getInstance();
        $iUserId = \Aurora\System\Api::getAuthenticatedUserId();
        if (0 < $iUserId) {
            $sResult = strtr($sResult, array(
                '{{IOS/HELLO}}' => $this->i18N('HELLO'),
                '{{IOS/DESC_P1}}' => $this->i18N('DESC_P1'),
                '{{IOS/DESC_P2}}' => $this->i18N('DESC_P2'),
                '{{IOS/DESC_P3}}' => $this->i18N('DESC_P3'),
                '{{IOS/DESC_P4}}' => $this->i18N('DESC_P4'),
                '{{IOS/DESC_P5}}' => $this->i18N('DESC_P5'),
                '{{IOS/DESC_P6}}' => $this->i18N('DESC_P6'),
                '{{IOS/DESC_P7}}' => $this->i18N('DESC_P7'),
                '{{IOS/DESC_BUTTON_YES}}' => $this->i18N('DESC_BUTTON_YES'),
                '{{IOS/DESC_BUTTON_SKIP}}' => $this->i18N('DESC_BUTTON_SKIP'),
                '{{IOS/DESC_BUTTON_OPEN}}' => $this->i18N('DESC_BUTTON_OPEN'),
                '{{AppVersion}}' => Application::GetVersion(),
                '{{IntegratorLinks}}' => $oApiIntegrator->buildHeadersLink()
            ));
            \Aurora\Modules\CoreWebclient\Module::Decorator()->SetHtmlOutputHeaders();
        } else {
            \Aurora\System\Api::Location('./');
        }

        return $sResult;
    }

    /**
     * @ignore
     */
    public function EntryProfile()
    {
        $oIosManager = new Manager($this);

        $oUser = \Aurora\System\Api::getAuthenticatedUser();

        $mResultProfile = $oIosManager && $oUser ? $oIosManager->generateXMLProfile($oUser) : false;

        if (!$mResultProfile) {
            \Aurora\System\Api::Location('./?IOS/Error');
        } else {
            \header('Content-type: application/x-apple-aspen-config; chatset=utf-8');
            \header('Content-Disposition: attachment; filename="afterlogic.mobileconfig"');
            echo $mResultProfile;
        }
    }
    /***** public functions *****/
}
