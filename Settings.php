<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\Ios;

use Aurora\System\SettingsProperty;

/**
 * @property bool $Disabled
 * @property bool $IncludeInMobile
 * @property bool $IncludePasswordInProfile
 * @property bool $AllowIosProfile
 * @property bool $SyncIosAfterLogin
 */

class Settings extends \Aurora\System\Module\Settings
{
    protected function initDefaults()
    {
        $this->aContainer = [
            "Disabled" => new SettingsProperty(
                false,
                "bool",
                null,
                "Setting to true disables the module",
            ),
            "IncludeInMobile" => new SettingsProperty(
                true,
                "bool",
                null,
                "If true, the module is used in mobile version of the interface",
            ),
            "IncludePasswordInProfile" => new SettingsProperty(
                true,
                "bool",
                null,
                "If true, password will be included when offering iOS profile download",
            ),
            "AllowIosProfile" => new SettingsProperty(
                true,
                "bool",
                null,
                "",
            ),
            "SyncIosAfterLogin" => new SettingsProperty(
                true,
                "bool",
                null,
                "",
            ),
        ];
    }
}
