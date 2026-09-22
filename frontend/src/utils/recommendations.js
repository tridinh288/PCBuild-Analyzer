import { CATEGORY_NAMES, formatWatts } from './format'

/**
 * Plain-language next steps derived from an analysis response. Only restates what the engine
 * already found (missing parts, problems, PSU sizing, score adjustments); it invents nothing.
 *
 * @returns {Array<{ tone: 'error'|'warning'|'info'|'ok', text: string }>}
 */
export function recommendationsFor(analysis) {
  const items = []

  if (analysis.missing_slots.length > 0) {
    items.push({
      tone: 'info',
      text: `Chọn thêm: ${analysis.missing_slots.map((slot) => CATEGORY_NAMES[slot] ?? slot).join(', ')}.`,
    })
  }

  analysis.compatibility.results
    .filter((result) => result.status === 'incompatible')
    .forEach((result) => items.push({ tone: 'error', text: `${result.title}: ${result.message}` }))

  const { selected_psu_watts: selected, recommended_psu_watts: recommended } = analysis.power
  if (selected !== null && selected < recommended) {
    items.push({ tone: 'warning', text: `Nên dùng nguồn từ ${formatWatts(recommended)} trở lên để có dư công suất.` })
  }

  analysis.compatibility.results
    .filter((result) => result.status === 'warning' && result.rule !== 'psu_wattage')
    .forEach((result) => items.push({ tone: 'warning', text: `${result.title}: ${result.message}` }))

  analysis.performance.adjustments.forEach((adjustment) => items.push({ tone: 'warning', text: adjustment.reason }))

  if (items.length === 0) {
    items.push({ tone: 'ok', text: 'Cấu hình đầy đủ và cân đối, không có khuyến nghị thêm.' })
  }

  return items
}
