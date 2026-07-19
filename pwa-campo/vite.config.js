import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  base: '/campo/',
  plugins: [react()],
  build: {
    outDir: '../campo',
    emptyOutDir: true,
    sourcemap: false,
  },
})
