import types from 'src/utils/types'

class IosSettings {
  constructor(appData) {
    const iosData = types.pObject(appData.Ios)
    this.allowIosProfile = types.pBool(iosData.AllowIosProfile)
    this.syncIosAfterLogin = types.pBool(iosData.SyncIosAfterLogin)

    const coreData = types.pObject(appData.Core)
    this.cookiePath = types.pString(coreData.CookiePath)
    this.cookieSecure = types.pBool(coreData.CookieSecure)
  }
}

let settings = null

export default {
  init(appData) {
    settings = new IosSettings(appData)
  },

  getSetting(settingName) {
    return settings ? settings[settingName] : null
  },
}
