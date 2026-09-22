import { Link } from 'react-router'
import { formatPrice } from '../utils/format'
import ImagePlaceholder from './ImagePlaceholder'
import SpecTable from './SpecTable'

export default function ComponentCard({ product }) {
  return (
    <Link to={`/components/${product.slug}`}
      className="group flex flex-col rounded-xl border border-slate-200 bg-white p-3 transition hover:border-brand-300 hover:shadow-md">
      {product.image?.thumb
        ? <img src={product.image.thumb} alt="" className="aspect-[4/3] rounded-lg object-cover" loading="lazy" />
        : <ImagePlaceholder category={product.category} />}
      <div className="mt-3 flex flex-1 flex-col px-1">
        <p className="text-xs font-medium text-slate-400">{product.brand}</p>
        <h3 className="font-semibold text-slate-900 group-hover:text-brand-700">{product.name}</h3>
        <div className="mt-2"><SpecTable specs={product.specs} highlightOnly /></div>
        <p className="mt-auto pt-3 text-lg font-bold">{formatPrice(product.price)}</p>
      </div>
    </Link>
  )
}
