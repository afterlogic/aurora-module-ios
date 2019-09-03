'use strict';

require('%PathToCoreWebclientModule%/js/enums.js');

module.exports = function (oAppData) {
	var
		App = require('%PathToCoreWebclientModule%/js/App.js'),
		Settings = require('modules/%ModuleName%/js/Settings.js'),
		Browser = require('%PathToCoreWebclientModule%/js/Browser.js'),
		iUserRole = App.getUserRole()
	;
	
	Settings.init(oAppData);
	
	return {
		routeToIos: function () {
			if (Browser.iosDevice && iUserRole !== Enums.UserRole.Anonymous && Settings.SyncIosAfterLogin && Settings.AllowIosProfile && $.cookie('skip-ios') !== '1')
			{
				$.cookie('skip-ios', '1');
				window.location.href = '?ios';
			}
		}
	};
};
