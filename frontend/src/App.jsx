import { createBrowserRouter } from 'react-router'
import { RouterProvider } from 'react-router/dom'
import RequireAdmin from './components/admin/RequireAdmin'
import { AuthProvider } from './context/AuthProvider'
import AdminLayout from './layouts/AdminLayout'
import MainLayout from './layouts/MainLayout'
import Categories from './pages/admin/Categories'
import Dashboard from './pages/admin/Dashboard'
import Login from './pages/admin/Login'
import Analysis from './pages/Analysis'
import BuildDetail from './pages/BuildDetail'
import Builder from './pages/Builder'
import Builds from './pages/Builds'
import Compare from './pages/Compare'
import ComponentDetail from './pages/ComponentDetail'
import Components from './pages/Components'
import Home from './pages/Home'
import NotFound from './pages/NotFound'

const router = createBrowserRouter([
  {
    element: <MainLayout />,
    children: [
      { path: '/', element: <Home /> },
      { path: '/builds', element: <Builds /> },
      { path: '/builds/:slug', element: <BuildDetail /> },
      { path: '/builder', element: <Builder /> },
      { path: '/components', element: <Components /> },
      { path: '/components/:slug', element: <ComponentDetail /> },
      { path: '/compare', element: <Compare /> },
      { path: '/analysis', element: <Analysis /> },
      { path: '*', element: <NotFound /> },
    ],
  },
  { path: '/admin/login', element: <Login /> },
  {
    path: '/admin',
    element: <RequireAdmin />,
    children: [
      {
        element: <AdminLayout />,
        children: [
          { index: true, element: <Dashboard /> },
          { path: 'categories', element: <Categories /> },
        ],
      },
    ],
  },
])

export default function App() {
  return (
    <AuthProvider>
      <RouterProvider router={router} />
    </AuthProvider>
  )
}
