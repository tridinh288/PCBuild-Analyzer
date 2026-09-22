import { Link } from 'react-router'
import BuildCard from '../components/BuildCard'
import { ErrorState, LoadingState } from '../components/ui/States'
import { useApi } from '../hooks/useApi'
import { api } from '../services/api'
import { PURPOSES } from '../utils/format'

function BuildRow({ title, params, link }) {
  const { data, error, loading, reload } = useApi((signal) => api.builds(params, signal), JSON.stringify(params))

  return (
    <section className="mt-12">
      <div className="flex items-end justify-between">
        <h2 className="text-xl font-bold">{title}</h2>
        <Link to={link} className="text-sm font-medium text-brand-700 hover:underline">Xem tất cả →</Link>
      </div>
      <div className="mt-4">
        {error && <ErrorState error={error} onRetry={reload} />}
        {loading && !data && <LoadingState />}
        {data && (
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {data.map((build) => <BuildCard key={build.id} build={build} />)}
          </div>
        )}
      </div>
    </section>
  )
}

export default function Home() {
  return (
    <div>
      <section className="rounded-2xl bg-gradient-to-br from-brand-700 to-brand-500 px-6 py-12 text-white sm:px-12">
        <p className="text-sm font-semibold uppercase tracking-widest text-brand-100">Tìm – So sánh – Phân tích</p>
        <h1 className="mt-3 max-w-2xl text-3xl font-bold leading-tight sm:text-4xl">
          Chọn linh kiện PC mà không lo lắp không vừa
        </h1>
        <p className="mt-4 max-w-2xl text-brand-100">
          Kiểm tra tương thích theo thời gian thực, ước tính công suất và nguồn cần dùng, xem giá tiền phân bổ ra sao
          và so sánh các cấu hình với nhau. Không cần đăng ký.
        </p>
        <div className="mt-8 flex flex-wrap gap-3">
          <Link to="/builder" className="rounded-lg bg-white px-5 py-3 font-semibold text-brand-700 hover:bg-brand-50">
            Tự build cấu hình
          </Link>
          <Link to="/builds" className="rounded-lg px-5 py-3 font-semibold text-white ring-1 ring-white/60 hover:bg-white/10">
            Xem cấu hình mẫu
          </Link>
        </div>
      </section>

      <section className="mt-12">
        <h2 className="text-xl font-bold">Theo mục đích sử dụng</h2>
        <div className="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          {PURPOSES.map((purpose) => (
            <Link key={purpose.value} to={`/builds?purpose=${purpose.value}`}
              className="rounded-xl border border-slate-200 bg-white p-4 font-medium hover:border-brand-300 hover:text-brand-700">
              {purpose.label}
            </Link>
          ))}
        </div>
      </section>

      <BuildRow title="Cấu hình nổi bật" params={{ featured: 1, per_page: 4 }} link="/builds?featured=1" />
      <BuildRow title="Mới thêm gần đây" params={{ sort: 'newest', per_page: 4 }} link="/builds" />
    </div>
  )
}
