import { describe, expect, it } from 'vitest'
import { recommendationsFor } from './recommendations'

function analysis(overrides = {}) {
  return {
    missing_slots: [],
    compatibility: { results: [] },
    power: { selected_psu_watts: 750, recommended_psu_watts: 550 },
    performance: { adjustments: [] },
    ...overrides,
  }
}

describe('recommendationsFor', () => {
  it('says nothing is needed for a complete, balanced build', () => {
    expect(recommendationsFor(analysis())).toEqual([
      { tone: 'ok', text: 'Cấu hình đầy đủ và cân đối, không có khuyến nghị thêm.' },
    ])
  })

  it('lists missing slots, errors, PSU sizing, warnings and score adjustments in that order', () => {
    const result = recommendationsFor(analysis({
      missing_slots: ['case', 'cooler'],
      compatibility: {
        results: [
          { rule: 'psu_wattage', status: 'warning', title: 'Công suất nguồn', message: '…' },
          { rule: 'cpu_motherboard_socket', status: 'incompatible', title: 'Socket', message: 'Không khớp.' },
          { rule: 'cooler_cpu_tdp', status: 'warning', title: 'Tản nhiệt', message: 'Hơi yếu.' },
        ],
      },
      power: { selected_psu_watts: 450, recommended_psu_watts: 550 },
      performance: { adjustments: [{ reason: 'Nghẽn cổ chai.', penalty: 10 }] },
    }))

    expect(result.map((item) => item.tone)).toEqual(['info', 'error', 'warning', 'warning', 'warning'])
    expect(result[0].text).toBe('Chọn thêm: Vỏ case, Tản nhiệt CPU.')
    expect(result[2].text).toBe('Nên dùng nguồn từ 550 W trở lên để có dư công suất.')
  })

  it('does not suggest a PSU when none is selected', () => {
    const result = recommendationsFor(analysis({ power: { selected_psu_watts: null, recommended_psu_watts: 550 } }))
    expect(result.some((item) => item.text.includes('nguồn'))).toBe(false)
  })
})
