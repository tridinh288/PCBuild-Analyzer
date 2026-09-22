import { Link, NavLink, Outlet, useNavigate } from 'react-router'
import ServerWakingBanner from '../components/ServerWakingBanner'
import { useAuth } from '../context/AuthContext'

const NAV = [
  { to: '/admin/products', label: 'Linh kiện' },
  { to: '/admin/builds', label: 'Cấu hình mẫu' },
  { to: '/admin/categories', label: 'Danh mục' },
]

export default function AdminLayout() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()

  const signOut = async () => {
    await logout()
    navigate('/admin/login', { replace: true })
  }

  return (
    <div className="min-h-screen bg-slate-100">
      <ServerWakingBanner />
      <header className="bg-slate-900 text-white">
        <div className="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 px-4 py-3">
          <div className="flex items-center gap-4">
            <Link to="/admin/products" className="font-bold">PCBuild Admin</Link>
            <nav className="flex gap-1" aria-label="Quản trị">
              {NAV.map((item) => (
                <NavLink key={item.to} to={item.to}
                  className={({ isActive }) => `rounded-md px-3 py-1.5 text-sm ${isActive ? 'bg-white/15' : 'text-slate-300 hover:bg-white/10'}`}>
                  {item.label}
                </NavLink>
              ))}
            </nav>
          </div>
          <div className="flex items-center gap-3 text-sm">
            <Link to="/" className="text-slate-300 hover:text-white">Xem trang công khai</Link>
            <span className="text-slate-400">{user?.email}</span>
            <button type="button" onClick={signOut} className="rounded-md px-3 py-1.5 ring-1 ring-white/30 hover:bg-white/10">
              Đăng xuất
            </button>
          </div>
        </div>
      </header>
      <main className="mx-auto max-w-7xl px-4 py-8">
        <Outlet />
      </main>
    </div>
  )
}
