import { useState } from 'react'
import { Link, NavLink, Outlet, ScrollRestoration } from 'react-router'
import ServerWakingBanner from '../components/ServerWakingBanner'

const NAV = [
  { to: '/builds', label: 'Cấu hình mẫu' },
  { to: '/builder', label: 'Tự build' },
  { to: '/components', label: 'Linh kiện' },
  { to: '/compare', label: 'So sánh' },
]

function navClass({ isActive }) {
  return `rounded-lg px-3 py-2 text-sm font-medium ${
    isActive ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
  }`
}

export default function MainLayout() {
  const [menuOpen, setMenuOpen] = useState(false)

  return (
    <div className="flex min-h-screen flex-col">
      <ServerWakingBanner />

      <header className="sticky top-0 z-30 border-b border-slate-200 bg-white/90 backdrop-blur">
        <div className="mx-auto flex h-16 max-w-7xl items-center justify-between px-4">
          <Link to="/" className="flex items-center gap-2 font-bold text-slate-900">
            <span className="grid size-8 place-items-center rounded-lg bg-brand-600 text-sm text-white">PC</span>
            PCBuild Analyzer
          </Link>

          <nav className="hidden gap-1 md:flex" aria-label="Điều hướng chính">
            {NAV.map((item) => <NavLink key={item.to} to={item.to} className={navClass}>{item.label}</NavLink>)}
          </nav>

          <button type="button" className="rounded-lg p-2 text-slate-600 hover:bg-slate-100 md:hidden"
            aria-expanded={menuOpen} aria-label="Mở menu" onClick={() => setMenuOpen((open) => !open)}>
            <svg className="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
              <path strokeLinecap="round" d="M4 7h16M4 12h16M4 17h16" />
            </svg>
          </button>
        </div>

        {menuOpen && (
          <nav className="flex flex-col gap-1 border-t border-slate-200 px-4 py-3 md:hidden" aria-label="Điều hướng chính">
            {NAV.map((item) => (
              <NavLink key={item.to} to={item.to} className={navClass} onClick={() => setMenuOpen(false)}>
                {item.label}
              </NavLink>
            ))}
          </nav>
        )}
      </header>

      <main className="mx-auto w-full max-w-7xl flex-1 px-4 py-8">
        <Outlet />
      </main>

      <footer className="border-t border-slate-200 bg-white">
        <div className="mx-auto max-w-7xl px-4 py-6 text-sm text-slate-500">
          Công suất và điểm cấu hình là <strong>ước tính</strong> theo quy tắc của dự án, không phải kết quả đo
          hay benchmark. Giá chỉ mang tính tham khảo.
        </div>
      </footer>

      <ScrollRestoration />
    </div>
  )
}
