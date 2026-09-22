import { describe, expect, it } from 'vitest'
import { isEmptySelection, parseSelection, serializeSelection, toApiSelection } from './builderUrl'

describe('builderUrl', () => {
  it('parses ids, quantities and multi-item slots', () => {
    expect(parseSelection('cpu=3&motherboard=7&ram=12x2&storage=4,9x2')).toEqual({
      cpu: [{ id: 3, quantity: 1 }],
      motherboard: [{ id: 7, quantity: 1 }],
      ram: [{ id: 12, quantity: 2 }],
      storage: [{ id: 4, quantity: 1 }, { id: 9, quantity: 2 }],
    })
  })

  it('ignores unknown slots, malformed tokens and duplicates', () => {
    expect(parseSelection('keyboard=1&cpu=abc&gpu=-2&storage=4,4x3,x2,5x0&psu=8')).toEqual({
      storage: [{ id: 4, quantity: 1 }],
      psu: [{ id: 8, quantity: 1 }],
    })
  })

  it('serializes in slot order regardless of selection order', () => {
    const selection = { storage: [{ id: 4, quantity: 1 }, { id: 9, quantity: 2 }], ram: [{ id: 12, quantity: 2 }], cpu: [{ id: 3, quantity: 1 }] }

    expect(serializeSelection(selection).toString()).toBe('cpu=3&ram=12x2&storage=4%2C9x2')
  })

  it('round-trips through the URL', () => {
    const query = 'cpu=3&ram=12x2&storage=4,9x2'
    expect(parseSelection(serializeSelection(parseSelection(query)))).toEqual(parseSelection(query))
  })

  it('builds the API request shape', () => {
    expect(toApiSelection(parseSelection('cpu=3&ram=12x2&gpu=8&storage=4,9x2'))).toEqual({
      cpu: 3,
      ram: { id: 12, quantity: 2 },
      gpu: 8,
      storage: [{ id: 4, quantity: 1 }, { id: 9, quantity: 2 }],
    })
  })

  it('detects an empty selection', () => {
    expect(isEmptySelection({})).toBe(true)
    expect(isEmptySelection({ cpu: [] })).toBe(true)
    expect(isEmptySelection({ cpu: [{ id: 1, quantity: 1 }] })).toBe(false)
  })
})
