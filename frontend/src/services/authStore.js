/**
 * Admin bearer token (D-023). Kept in localStorage so the admin stays logged in across tabs.
 *
 * Trade-off: any script running on the page (XSS) could read it. Mitigations: React escapes
 * output by default, the token expires (8 h), logout revokes it server-side, and only the
 * admin area uses it. Cookies are not an option across onrender.com subdomains.
 *
 * External store read with useSyncExternalStore (context/AuthContext.jsx).
 */

const KEY = 'pcbuild:admin-token'
const listeners = new Set()

function read() {
  try {
    const saved = JSON.parse(localStorage.getItem(KEY) ?? 'null')
    if (saved?.token && Date.parse(saved.expiresAt) > Date.now()) {
      return saved.token
    }
  } catch {
    // Storage blocked or corrupt: treated as logged out.
  }
  return null
}

let token = read()

function emit() {
  listeners.forEach((listener) => listener())
}

export function subscribe(listener) {
  listeners.add(listener)
  return () => listeners.delete(listener)
}

export function getToken() {
  return token
}

export function setToken(value, expiresAt) {
  token = value
  try {
    localStorage.setItem(KEY, JSON.stringify({ token: value, expiresAt }))
  } catch {
    // Still logged in for this page view.
  }
  emit()
}

export function clearToken() {
  token = null
  try {
    localStorage.removeItem(KEY)
  } catch {
    // Nothing else to do.
  }
  emit()
}
