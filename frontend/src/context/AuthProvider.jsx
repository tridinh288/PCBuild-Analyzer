import { useCallback, useEffect, useMemo, useState, useSyncExternalStore } from 'react'
import { AuthContext } from './authContext'
import { adminApi } from '../services/api'
import { clearToken, getToken, setToken, subscribe } from '../services/authStore'

/**
 * Admin session for the admin area only; public pages never need it (D-001).
 */
export function AuthProvider({ children }) {
  const token = useSyncExternalStore(subscribe, getToken)
  const [user, setUser] = useState(null)

  // Validate a remembered token once; a 401 clears it through the Axios interceptor.
  useEffect(() => {
    if (!token) return undefined
    const controller = new AbortController()
    adminApi.me(controller.signal).then(({ data }) => setUser(data)).catch(() => {})
    return () => controller.abort()
  }, [token])

  const login = useCallback(async (email, password) => {
    const { data } = await adminApi.login(email, password)
    setToken(data.token, data.expires_at)
    setUser(data.user)
  }, [])

  const logout = useCallback(async () => {
    try {
      await adminApi.logout()
    } finally {
      clearToken()
      setUser(null)
    }
  }, [])

  const value = useMemo(() => ({
    isAuthenticated: Boolean(token),
    user: token ? user : null,
    login,
    logout,
  }), [token, user, login, logout])

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

