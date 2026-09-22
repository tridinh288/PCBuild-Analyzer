/**
 * Pure helpers for the admin spec form, driven by GET /admin/categories/{slug}/spec-schema.
 * Form inputs hold strings/booleans/arrays; the API needs real numbers and booleans (D-005).
 */

/**
 * A field with `required: { when: { key: value } }` only applies when that condition holds
 * (e.g. cooler height only for air coolers).
 */
export function isFieldVisible(field, values) {
  const condition = field.required?.when
  if (!condition) return true
  return Object.entries(condition).every(([key, expected]) => values[key] === expected)
}

export function isFieldRequired(field) {
  return field.required === true || Boolean(field.required?.when)
}

/** Form values from stored raw specs (or empty values for a new product). */
export function initialSpecValues(fields, rawSpecs = {}) {
  return Object.fromEntries(fields.map((field) => {
    const value = rawSpecs[field.key]
    switch (field.type) {
      case 'integer': return [field.key, value === undefined || value === null ? '' : String(value)]
      case 'boolean': return [field.key, value === true]
      case 'enum_list': return [field.key, Array.isArray(value) ? value : []]
      default: return [field.key, value ?? '']
    }
  }))
}

/**
 * Request `specs` from form values: hidden fields are left out, empty values become null so the
 * API reports "required" on the right field, numbers are real numbers.
 */
export function specsForSubmit(fields, values) {
  const specs = {}

  fields.filter((field) => isFieldVisible(field, values)).forEach((field) => {
    const value = values[field.key]
    switch (field.type) {
      case 'integer':
        specs[field.key] = value === '' || value === null || value === undefined ? null : Number(value)
        break
      case 'boolean':
        specs[field.key] = value === true
        break
      case 'enum_list':
        specs[field.key] = Array.isArray(value) ? value : []
        break
      default:
        specs[field.key] = value === '' ? null : value
    }
  })

  return specs
}
