import { Navigate, Outlet, useLocation } from 'react-router'
import { useAuth } from '../../context/AuthContext'

/**
 * Route guard for /admin/*. The API is the real protection (auth:sanctum); this only avoids
 * showing admin screens that would fail anyway.
 */
export default function RequireAdmin() {
  const { isAuthenticated } = useAuth()
  const location = useLocation()

  if (!isAuthenticated) {
    return <Navigate to={`/admin/login?next=${encodeURIComponent(location.pathname + location.search)}`} replace />
  }

  return <Outlet />
}
