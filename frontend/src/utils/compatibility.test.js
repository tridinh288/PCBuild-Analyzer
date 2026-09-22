import { describe, expect, it } from 'vitest'
import { problemsFor, worstStatus } from './compatibility'

const compatibility = {
  results: [
    { rule: 'cpu_motherboard_socket', status: 'compatible' },
    { rule: 'psu_wattage', status: 'warning' },
    { rule: 'gpu_case_clearance', status: 'incompatible' },
  ],
  by_category: { psu: ['psu_wattage'], gpu: ['gpu_case_clearance'], case: ['gpu_case_clearance'] },
}

describe('compatibility helpers', () => {
  it('finds the problems of one slot', () => {
    expect(problemsFor('psu', compatibility).map((r) => r.rule)).toEqual(['psu_wattage'])
    expect(problemsFor('cpu', compatibility)).toEqual([])
    expect(problemsFor('cpu', null)).toEqual([])
  })

  it('returns the worst status', () => {
    expect(worstStatus(compatibility.results)).toBe('incompatible')
    expect(worstStatus([{ status: 'warning' }, { status: 'skipped' }])).toBe('warning')
    expect(worstStatus([])).toBe('compatible')
  })
})
