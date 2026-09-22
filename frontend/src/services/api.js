import axios from 'axios'
import { clearToken, getToken } from './authStore'
import { trackRequest } from './serverStatus'

/**
 * The only place that knows URLs and the API envelope ({ success, data, meta }).
 * Pages call these functions and receive { data, meta }, or catch an ApiError.
 */
const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:8000/api',
  headers: { Accept: 'application/json' },
  // A sleeping free-tier server can take about a minute to answer the first request.
  timeout: 90_000,
})

export class ApiError extends Error {
  constructor(message, status = 0, errors = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.errors = errors
  }
}

http.interceptors.request.use((config) => {
  config.metadata = { done: trackRequest() }
  const token = getToken()
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

http.interceptors.response.use(
  (response) => {
    response.config.metadata?.done()
    return response
  },
  (error) => {
    error.config?.metadata?.done()

    if (axios.isCancel(error)) {
      return Promise.reject(error)
    }

    // Expired or revoked admin token: forget it; RequireAdmin then shows the login page.
    if (error.response?.status === 401 && getToken()) {
      clearToken()
    }

    const body = error.response?.data
    const message = body?.message
      ?? (error.code === 'ECONNABORTED'
        ? 'Máy chủ phản hồi quá lâu, vui lòng thử lại.'
        : 'Không kết nối được máy chủ. Vui lòng kiểm tra mạng và thử lại.')

    return Promise.reject(new ApiError(message, error.response?.status ?? 0, body?.errors ?? {}))
  },
)

async function get(url, params, signal) {
  const { data } = await http.get(url, { params, signal })
  return { data: data.data, meta: data.meta ?? {} }
}

async function post(url, body, signal) {
  const { data } = await http.post(url, body, { signal })
  return { data: data.data, meta: data.meta ?? {} }
}

async function put(url, body) {
  const { data } = await http.put(url, body)
  return { data: data.data, meta: data.meta ?? {} }
}

async function destroy(url) {
  await http.delete(url)
}

/** Multipart upload (POST: PHP does not parse multipart bodies on PUT). */
async function upload(url, file) {
  const form = new FormData()
  form.append('image', file)
  const { data } = await http.post(url, form)
  return { data: data.data, meta: data.meta ?? {} }
}

export const api = {
  categories: (signal) => get('/categories', undefined, signal),
  categoryFilters: (slug, signal) => get(`/categories/${slug}/filters`, undefined, signal),

  components: (params, signal) => get('/components', params, signal),
  component: (slug, signal) => get(`/components/${slug}`, undefined, signal),

  builds: (params, signal) => get('/builds', params, signal),
  build: (slug, signal) => get(`/builds/${slug}`, undefined, signal),
  buildAnalysis: (slug, profile, signal) => get(`/builds/${slug}/analysis`, profile ? { profile } : undefined, signal),

  builderOptions: (body, signal) => post('/builder/options', body, signal),
  builderAnalyze: (body, signal) => post('/builder/analyze', body, signal),

  compare: (body, signal) => post('/compare', body, signal),
}

export const adminApi = {
  login: (email, password) => post('/admin/login', { email, password }),
  logout: () => post('/admin/logout'),
  me: (signal) => get('/admin/me', undefined, signal),

  categories: (signal) => get('/admin/categories', undefined, signal),
  updateCategory: (id, body) => put(`/admin/categories/${id}`, body),
  specSchema: (slug, signal) => get(`/admin/categories/${slug}/spec-schema`, undefined, signal),

  products: (params, signal) => get('/admin/products', params, signal),
  product: (id, signal) => get(`/admin/products/${id}`, undefined, signal),
  createProduct: (body) => post('/admin/products', body),
  updateProduct: (id, body) => put(`/admin/products/${id}`, body),
  deleteProduct: (id) => destroy(`/admin/products/${id}`),
  uploadProductImage: (id, file) => upload(`/admin/products/${id}/image`, file),
  deleteProductImage: (id) => destroy(`/admin/products/${id}/image`),

  builds: (params, signal) => get('/admin/builds', params, signal),
  build: (id, signal) => get(`/admin/builds/${id}`, undefined, signal),
  createBuild: (body) => post('/admin/builds', body),
  updateBuild: (id, body) => put(`/admin/builds/${id}`, body),
  deleteBuild: (id) => destroy(`/admin/builds/${id}`),
  updateBuildItems: (id, items) => put(`/admin/builds/${id}/items`, { items }),
  uploadBuildImage: (id, file) => upload(`/admin/builds/${id}/image`, file),
  deleteBuildImage: (id) => destroy(`/admin/builds/${id}/image`),
}
