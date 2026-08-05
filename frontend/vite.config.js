import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, '.', '')
  const publicUrl = (env.VITE_PUBLIC_URL || 'http://localhost:5173').replace(/\/$/, '')
  return {
    plugins: [vue(), { name: 'menuos-production-metadata', generateBundle() { this.emitFile({ type: 'asset', fileName: 'robots.txt', source: `User-agent: *\nAllow: /\nDisallow: /app/\nDisallow: /restaurant\nDisallow: /categories\nDisallow: /menu-items\nDisallow: /qr-code\nSitemap: ${publicUrl}/sitemap.xml\n` }) } }],
  }
})
