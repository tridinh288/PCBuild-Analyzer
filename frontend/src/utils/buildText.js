import { CATEGORY_NAMES, formatPrice, formatWatts } from './format'

/**
 * A readable plain-text version of a configuration, for chat apps and forums
 * (spec section 21: component, name, quantity, price, total, power, recommended PSU, link).
 *
 * @param {{ items: Array<{category: string, name: string, quantity: number, price: number}>,
 *           total: number, estimatedWatts: number, recommendedPsuWatts: number, url: string }} build
 */
export function buildAsText({ items, total, estimatedWatts, recommendedPsuWatts, url }) {
  const lines = ['Cấu hình PC — PCBuild Analyzer', '']

  items.forEach((item) => {
    const quantity = item.quantity > 1 ? ` × ${item.quantity}` : ''
    lines.push(`${CATEGORY_NAMES[item.category] ?? item.category}: ${item.name}${quantity} — ${formatPrice(item.price * item.quantity)}`)
  })

  lines.push(
    '',
    `Tổng: ${formatPrice(total)}`,
    `Công suất ước tính: ${formatWatts(estimatedWatts)} · Nguồn khuyến nghị: ${formatWatts(recommendedPsuWatts)}`,
    `Xem chi tiết: ${url}`,
  )

  // Intl puts a non-breaking space before "₫"; plain spaces paste better everywhere.
  return lines.join('\n').replace(/ /g, ' ')
}
