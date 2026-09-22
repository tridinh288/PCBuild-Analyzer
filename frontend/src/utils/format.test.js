import { describe, expect, it } from 'vitest'
import { formatPrice, formatPriceDelta, formatWatts } from './format'

// Intl uses a non-breaking space before the currency sign.
const normalize = (text) => text.replace(/ /g, ' ')

describe('format', () => {
  it('formats integer VND prices', () => {
    expect(normalize(formatPrice(26330000))).toBe('26.330.000 ₫')
    expect(normalize(formatPrice(0))).toBe('0 ₫')
  })

  it('signs price differences', () => {
    expect(normalize(formatPriceDelta(2200000))).toBe('+2.200.000 ₫')
    expect(normalize(formatPriceDelta(-500000))).toBe('-500.000 ₫')
  })

  it('formats watts with a thousands separator', () => {
    expect(formatWatts(392)).toBe('392 W')
    expect(formatWatts(1200)).toBe('1.200 W')
  })
})
