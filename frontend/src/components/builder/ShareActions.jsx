import { useEffect, useState } from 'react'
import { buildAsText } from '../../utils/buildText'
import { copyText } from '../../utils/clipboard'

/**
 * Share a configuration without an account (D-019): the URL is the configuration.
 */
export default function ShareActions({ builder }) {
  const [feedback, setFeedback] = useState(null)

  useEffect(() => {
    if (!feedback) return undefined
    const timer = setTimeout(() => setFeedback(null), 2500)
    return () => clearTimeout(timer)
  }, [feedback])

  const analysis = builder.analysis.data
  const hasParts = Object.keys(builder.selection).length > 0

  const copy = async (text) => {
    setFeedback(await copyText(text) ? 'Đã sao chép' : 'Không sao chép được, hãy sao chép thủ công.')
  }

  const copyLink = () => copy(window.location.href)

  const copyAsText = () => copy(buildAsText({
    items: (analysis?.items ?? []).map((item) => ({
      category: item.category, name: item.product.name, quantity: item.quantity, price: item.product.price,
    })),
    total: analysis?.price.total ?? 0,
    estimatedWatts: analysis?.power.estimated_watts ?? 0,
    recommendedPsuWatts: analysis?.power.recommended_psu_watts ?? 0,
    url: window.location.href,
  }))

  const button = 'rounded-lg px-3 py-2 text-sm font-medium ring-1 ring-slate-300 hover:bg-slate-100 disabled:opacity-40'

  return (
    <div className="flex flex-wrap items-center gap-2">
      <button type="button" className={button} onClick={copyLink} disabled={!hasParts}>Sao chép liên kết</button>
      <button type="button" className={button} onClick={copyAsText} disabled={!hasParts || !analysis}>Sao chép dạng văn bản</button>
      <span className="text-sm font-medium text-emerald-700" aria-live="polite">{feedback}</span>
    </div>
  )
}
