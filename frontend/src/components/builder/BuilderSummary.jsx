import { Link } from 'react-router'
import { serializeSelection } from '../../utils/builderUrl'
import { serializeComparison } from '../../utils/compareUrl'
import { PURPOSES } from '../../utils/format'
import CompatibilitySummary from '../analysis/CompatibilitySummary'
import PowerCard from '../analysis/PowerCard'
import PriceBreakdown from '../analysis/PriceBreakdown'
import ScoreCard from '../analysis/ScoreCard'
import { Select } from '../ui/Field'
import { ErrorState, LoadingState } from '../ui/States'

/**
 * Live analysis of the current selection. Power, price and score still show for incompatible
 * or incomplete configurations (D-018).
 */
export default function BuilderSummary({ builder }) {
  const { analysis } = builder

  if (analysis.error) {
    return <ErrorState error={analysis.error} onRetry={analysis.reload} />
  }

  if (!analysis.data) {
    return <LoadingState label="Đang phân tích…" />
  }

  const data = analysis.data

  return (
    <div className={`space-y-4 ${analysis.loading ? 'opacity-70' : ''}`}>
      <CompatibilitySummary compatibility={data.compatibility} missingSlots={data.missing_slots} />
      <PriceBreakdown price={data.price} />
      <PowerCard power={data.power} />
      <div className="space-y-2">
        <Select label="Chấm điểm theo mục đích" value={builder.profile} options={PURPOSES} placeholder={null}
          onChange={builder.setProfile} />
        <ScoreCard performance={data.performance} />
      </div>
      {Object.keys(builder.selection).length > 0 && (
        <div className="grid grid-cols-2 gap-2">
          <Link to={`/analysis?${serializeSelection(builder.selection)}`}
            className="rounded-lg px-4 py-2.5 text-center font-semibold text-brand-700 ring-1 ring-brand-200 hover:bg-brand-50">
            Phân tích chi tiết
          </Link>
          <Link to={`/compare?${serializeComparison([{ type: 'custom', selection: builder.selection }])}`}
            className="rounded-lg px-4 py-2.5 text-center font-semibold text-slate-700 ring-1 ring-slate-300 hover:bg-slate-100">
            So sánh
          </Link>
        </div>
      )}
    </div>
  )
}
