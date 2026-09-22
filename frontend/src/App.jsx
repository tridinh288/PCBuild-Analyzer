import { createBrowserRouter } from 'react-router'
import { RouterProvider } from 'react-router/dom'
import MainLayout from './layouts/MainLayout'
import Analysis from './pages/Analysis'
import BuildDetail from './pages/BuildDetail'
import Builder from './pages/Builder'
import Builds from './pages/Builds'
import ComponentDetail from './pages/ComponentDetail'
import Components from './pages/Components'
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
      { path: '/builder', element: <Builder /> },
      { path: '/components', element: <Components /> },
      { path: '/components/:slug', element: <ComponentDetail /> },
      { path: '/compare', element: <Placeholder title="So sánh" /> },
      { path: '/analysis', element: <Analysis /> },
      { path: '*', element: <NotFound /> },
    ],
  },
])

export default function App() {
  return <RouterProvider router={router} />
}
