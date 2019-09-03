'use strict';

var
	_ = require('underscore'),
	Types = require('%PathToCoreWebclientModule%/js/utils/Types.js'),
	
	AppData = window.auroraAppData
;

var Settings = {
	AllowIosProfile: false,
	SyncIosAfterLogin: false,
	
	/**
	 * Initializes settings from AppData object sections.
	 * 
	 * @param {Object} oAppData Object contained modules settings.
	 */
	init: function (oAppData)
	{
		var
			oAppDataIosSection = oAppData['%ModuleName%']
		;
		
		if (!_.isEmpty(oAppDataIosSection))
		{
			this.AllowIosProfile = Types.pBool(oAppDataIosSection.AllowIosProfile, this.AllowIosProfile);
			this.SyncIosAfterLogin = Types.pBool(oAppDataIosSection.SyncIosAfterLogin, this.SyncIosAfterLogin);
		}
	}
};

Settings.init(AppData);

module.exports = Settings;
