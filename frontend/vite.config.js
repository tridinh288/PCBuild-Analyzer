import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react(), tailwindcss()],
  test: {
    // Unit tests cover pure logic (reducer, URL format, formatters): no DOM needed.
    environment: 'node',
    include: ['src/**/*.test.js'],
  },
})
