const vnd = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND', maximumFractionDigits: 0 })
const number = new Intl.NumberFormat('vi-VN')

/** 26330000 → "26.330.000 ₫" (prices are integer VND, D-010) */
export function formatPrice(value) {
  return vnd.format(value ?? 0)
}

/** 2200000 → "+2.200.000 ₫", -500000 → "-500.000 ₫" */
export function formatPriceDelta(value) {
  return (value > 0 ? '+' : '') + formatPrice(value)
}

/** 392 → "392 W" */
export function formatWatts(value) {
  return `${number.format(value ?? 0)} W`
}

export function formatNumber(value) {
  return number.format(value ?? 0)
}

/** Vietnamese names of the Builder slots, used where the API only gives a slug. */
export const CATEGORY_NAMES = {
  cpu: 'CPU',
  motherboard: 'Bo mạch chủ',
  ram: 'RAM',
  gpu: 'Card đồ họa',
  storage: 'Ổ cứng',
  psu: 'Nguồn',
  case: 'Vỏ case',
  cooler: 'Tản nhiệt CPU',
}

export const PURPOSES = [
  { value: 'gaming', label: 'Chơi game' },
  { value: 'programming', label: 'Lập trình' },
  { value: 'workstation', label: 'Đồ họa / Workstation' },
  { value: 'general_use', label: 'Sử dụng chung' },
]
