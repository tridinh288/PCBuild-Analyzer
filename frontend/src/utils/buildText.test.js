import { describe, expect, it } from 'vitest'
import { buildAsText } from './buildText'

describe('buildAsText', () => {
  it('lists components with quantity, total, power and the link', () => {
    const text = buildAsText({
      items: [
        { category: 'cpu', name: 'AMD Ryzen 5 7600', quantity: 1, price: 5290000 },
        { category: 'ram', name: 'Kingston FURY 16GB', quantity: 2, price: 1490000 },
      ],
      total: 8270000,
      estimatedWatts: 392,
      recommendedPsuWatts: 550,
      url: 'https://example.test/builder?cpu=2&ram=18x2',
    })

    expect(text).toBe([
      'Cấu hình PC — PCBuild Analyzer',
      '',
      'CPU: AMD Ryzen 5 7600 — 5.290.000 ₫',
      'RAM: Kingston FURY 16GB × 2 — 2.980.000 ₫',
      '',
      'Tổng: 8.270.000 ₫',
      'Công suất ước tính: 392 W · Nguồn khuyến nghị: 550 W',
      'Xem chi tiết: https://example.test/builder?cpu=2&ram=18x2',
    ].join('\n'))
  })
})
