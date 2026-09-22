import { Link } from 'react-router'
import { formatPrice } from '../utils/format'
import ImagePlaceholder from './ImagePlaceholder'

export default function BuildCard({ build }) {
  return (
    <Link to={`/builds/${build.slug}`}
      className="group flex flex-col rounded-xl border border-slate-200 bg-white p-3 transition hover:border-brand-300 hover:shadow-md">
      {build.image?.thumb
        ? <img src={build.image.thumb} alt="" className="aspect-[4/3] rounded-lg object-cover" loading="lazy" />
        : <ImagePlaceholder />}

      <div className="mt-3 flex flex-1 flex-col px-1">
        <div className="flex flex-wrap items-center gap-2">
          <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">{build.purpose_label}</span>
          {build.is_featured && <span className="rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700">Nổi bật</span>}
        </div>
        <h3 className="mt-2 font-semibold text-slate-900 group-hover:text-brand-700">{build.name}</h3>
        {build.description && <p className="mt-1 line-clamp-2 text-sm text-slate-500">{build.description}</p>}
        <p className="mt-auto pt-3 text-lg font-bold text-slate-900">{formatPrice(build.total_price)}</p>
      </div>
    </Link>
  )
}
