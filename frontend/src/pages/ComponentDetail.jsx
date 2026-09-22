import { Link, useParams } from 'react-router'
import ImagePlaceholder from '../components/ImagePlaceholder'
import SpecTable from '../components/SpecTable'
import { ErrorState, LoadingState } from '../components/ui/States'
import { useApi } from '../hooks/useApi'
import { api } from '../services/api'
import { formatPrice } from '../utils/format'

export default function ComponentDetail() {
  const { slug } = useParams()
  const { data: product, error, reload } = useApi((signal) => api.component(slug, signal), slug)

  if (error) {
    return <ErrorState error={error} onRetry={reload} />
  }

  if (!product) {
    return <LoadingState />
  }

  return (
    <div>
      <nav className="text-sm text-slate-500">
        <Link to={`/components?category=${product.category}`} className="hover:text-brand-700">{product.category_name}</Link>
        {' / '}{product.name}
      </nav>

      <div className="mt-6 grid gap-8 lg:grid-cols-2">
        {product.image?.large
          ? <img src={product.image.large} alt={product.name} className="w-full rounded-xl object-contain" />
          : <ImagePlaceholder category={product.category} className="aspect-[4/3] w-full rounded-xl text-lg" />}

        <div>
          <p className="text-sm font-medium text-slate-500">{product.brand} · {product.model}</p>
          <h1 className="mt-1 text-2xl font-bold sm:text-3xl">{product.name}</h1>
          <p className="mt-3 text-2xl font-bold text-brand-700">{formatPrice(product.price)}</p>
          {product.description && <p className="mt-4 text-slate-600">{product.description}</p>}

          <Link to={`/builder?${product.category}=${product.id}`}
            className="mt-6 inline-block rounded-lg bg-brand-600 px-4 py-2.5 font-semibold text-white hover:bg-brand-700">
            Bắt đầu build với linh kiện này
          </Link>

          <h2 className="mb-3 mt-8 text-lg font-semibold">Thông số kỹ thuật</h2>
          <SpecTable specs={product.specs} />
        </div>
      </div>
    </div>
  )
}
