import { describe, expect, it } from 'vitest'
import { initialSpecValues, isFieldVisible, specsForSubmit } from './specForm'

const coolerFields = [
  { key: 'type', type: 'enum', required: true, options: [{ value: 'air' }, { value: 'aio' }] },
  { key: 'supported_sockets', type: 'enum_list', required: true },
  { key: 'max_tdp', type: 'integer', required: true },
  { key: 'height_mm', type: 'integer', required: { when: { type: 'air' } } },
  { key: 'radiator_mm', type: 'integer', required: { when: { type: 'aio' } } },
]

describe('specForm', () => {
  it('shows conditional fields only when their condition holds', () => {
    expect(isFieldVisible(coolerFields[3], { type: 'air' })).toBe(true)
    expect(isFieldVisible(coolerFields[3], { type: 'aio' })).toBe(false)
    expect(isFieldVisible(coolerFields[0], {})).toBe(true)
  })

  it('builds form values from stored specs', () => {
    expect(initialSpecValues(coolerFields, { type: 'aio', supported_sockets: ['am5'], max_tdp: 250, radiator_mm: 240 }))
      .toEqual({ type: 'aio', supported_sockets: ['am5'], max_tdp: '250', height_mm: '', radiator_mm: '240' })
  })

  it('submits real types, drops hidden fields and sends null for empty required values', () => {
    const values = { type: 'aio', supported_sockets: ['am5'], max_tdp: '250', height_mm: '155', radiator_mm: '' }

    expect(specsForSubmit(coolerFields, values)).toEqual({
      type: 'aio', supported_sockets: ['am5'], max_tdp: 250, radiator_mm: null,
    })
  })

  it('keeps booleans as booleans', () => {
    const fields = [{ key: 'includes_cooler', type: 'boolean', required: true }]
    expect(specsForSubmit(fields, { includes_cooler: false })).toEqual({ includes_cooler: false })
    expect(initialSpecValues(fields, {})).toEqual({ includes_cooler: false })
  })
})
