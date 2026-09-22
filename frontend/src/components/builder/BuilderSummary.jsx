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
    </div>
  )
}
