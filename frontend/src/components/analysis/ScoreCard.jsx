const PARTS = { cpu: 'CPU', gpu: 'GPU', ram: 'RAM', storage: 'Ổ cứng' }

function scoreColor(score) {
  if (score >= 75) return 'text-emerald-600'
  if (score >= 50) return 'text-brand-600'
  return 'text-amber-600'
}

/**
 * Estimated configuration score for one profile, with sub-scores, weights and notes.
 * Never presented as a benchmark (D-015).
 */
export default function ScoreCard({ performance, detailed = false }) {
  return (
    <div className="rounded-xl border border-slate-200 bg-white p-4">
      <h3 className="text-sm font-semibold text-slate-500">Điểm cấu hình ước tính · {performance.profile_label}</h3>
      <p className={`mt-1 text-4xl font-bold ${scoreColor(performance.score)}`}>
        {performance.score}<span className="text-lg font-medium text-slate-400">/100</span>
      </p>

      <ul className="mt-4 space-y-2">
        {Object.entries(PARTS).map(([part, label]) => {
          const value = performance.sub_scores[part]
          return (
            <li key={part} className="text-sm">
              <div className="flex justify-between">
                <span className="text-slate-600">
                  {label}{detailed && <span className="text-slate-400"> · trọng số {Math.round(performance.weights[part] * 100)}%</span>}
                </span>
                <span className="font-medium">{value ?? 'Thiếu'}</span>
              </div>
              <div className="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100" aria-hidden="true">
                <div className="h-full rounded-full bg-brand-500" style={{ width: `${value ?? 0}%` }} />
              </div>
            </li>
          )
        })}
      </ul>

      {performance.adjustments.length > 0 && (
        <ul className="mt-4 space-y-1 text-sm text-amber-800">
          {performance.adjustments.map((adjustment) => (
            <li key={adjustment.reason}>−{adjustment.penalty} điểm: {adjustment.reason}</li>
          ))}
        </ul>
      )}

      <ul className="mt-3 space-y-1 text-xs text-slate-400">
        {performance.notes.map((note) => <li key={note}>{note}</li>)}
      </ul>
    </div>
  )
}
