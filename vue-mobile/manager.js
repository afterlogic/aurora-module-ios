import VueCookies from 'vue-cookies'

import { getApiHost } from 'src/api/helpers'
import enums from 'src/enums'
import types from 'src/utils/types'

import settings from './settings'
import isIosDevice from './utils/is-ios-device'

const SKIP_COOKIE_NAME = 'skip-ios'

// Same rule as the desktop client (js/manager.js): offer the profile once per session, right after login.
// The "?ios" page is standalone and returns to "./", so the mobile mode stays on.
const _offerIosProfile = appData => {
  const UserRoles = enums.getUserRoles()
  const user = types.pObject(appData?.User)
  const role = types.pEnum(user.Role, UserRoles, UserRoles.Anonymous)

  if (
    role === UserRoles.Anonymous ||
    !isIosDevice() ||
    !settings.getSetting('allowIosProfile') ||
    !settings.getSetting('syncIosAfterLogin') ||
    VueCookies.get(SKIP_COOKIE_NAME) === '1'
  ) {
    return
  }

  VueCookies.set(
    SKIP_COOKIE_NAME,
    '1',
    0,
    settings.getSetting('cookiePath') || '/',
    '',
    settings.getSetting('cookieSecure')
  )
  window.location.href = getApiHost() + '?ios'
}

export default {
  moduleName: 'Ios',

  requiredModules: [],

  init(appData) {
    settings.init(appData)
    _offerIosProfile(appData)
  },
}
