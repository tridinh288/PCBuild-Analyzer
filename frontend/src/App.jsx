import { createBrowserRouter } from 'react-router'
import { RouterProvider } from 'react-router/dom'
import MainLayout from './layouts/MainLayout'
import BuildDetail from './pages/BuildDetail'
import Builds from './pages/Builds'
import Home from './pages/Home'
import NotFound from './pages/NotFound'
import Placeholder from './pages/Placeholder'

const router = createBrowserRouter([
  {
    element: <MainLayout />,
    children: [
      { path: '/', element: <Home /> },
      { path: '/builds', element: <Builds /> },
      { path: '/builds/:slug', element: <BuildDetail /> },
      { path: '/builder', element: <Placeholder title="Tự build" /> },
      { path: '/components', element: <Placeholder title="Linh kiện" /> },
      { path: '/components/:slug', element: <Placeholder title="Chi tiết linh kiện" /> },
      { path: '/compare', element: <Placeholder title="So sánh" /> },
      { path: '/analysis', element: <Placeholder title="Phân tích" /> },
      { path: '*', element: <NotFound /> },
    ],
  },
])

export default function App() {
  return <RouterProvider router={router} />
}
