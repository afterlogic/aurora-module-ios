export default function isIosDevice() {
  if (typeof navigator === 'undefined') {
    return false
  }

  const userAgent = navigator.userAgent || ''
  if (userAgent.indexOf('Windows Phone') > -1) {
    return false
  }

  return (
    userAgent.indexOf('iPhone') > -1 ||
    userAgent.indexOf('iPod') > -1 ||
    userAgent.indexOf('iPad') > -1 ||
    (userAgent.indexOf('Macintosh') > -1 && navigator.maxTouchPoints > 1)
  )
}
