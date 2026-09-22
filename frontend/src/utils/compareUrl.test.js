import { describe, expect, it } from 'vitest'
import { parseComparison, serializeComparison, toApiConfiguration } from './compareUrl'

describe('compareUrl', () => {
  it('parses templates and custom configurations', () => {
    const params = new URLSearchParams()
    params.append('c', 'gaming-1080p-am5')
    params.append('c', '~cpu=3&ram=12x2')

    expect(parseComparison(params)).toEqual([
      { type: 'template', slug: 'gaming-1080p-am5' },
      { type: 'custom', selection: { cpu: [{ id: 3, quantity: 1 }], ram: [{ id: 12, quantity: 2 }] } },
    ])
  })

  it('drops invalid entries and keeps at most three', () => {
    const params = new URLSearchParams('c=a&c=Bad Slug&c=~nothing=1&c=b&c=c&c=d')
    expect(parseComparison(params).map((entry) => entry.slug)).toEqual(['a', 'b'])
  })

  it('round-trips through the URL', () => {
    const entries = [
      { type: 'template', slug: 'gaming-2k' },
      { type: 'custom', selection: { cpu: [{ id: 3, quantity: 1 }], storage: [{ id: 4, quantity: 1 }, { id: 9, quantity: 2 }] } },
    ]
    expect(parseComparison(serializeComparison(entries))).toEqual(entries)
  })

  it('builds the API request items', () => {
    expect(toApiConfiguration({ type: 'template', slug: 'x' }, 0)).toEqual({ type: 'template', slug: 'x' })
    expect(toApiConfiguration({ type: 'custom', selection: { cpu: [{ id: 3, quantity: 1 }] } }, 1))
      .toEqual({ type: 'custom', selected: { cpu: 3 }, label: 'Cấu hình tùy chỉnh 2' })
  })
})
