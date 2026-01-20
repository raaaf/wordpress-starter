import { defineConfig } from 'vite';
import { fileURLToPath } from 'url';
import { dirname, resolve } from 'path';
import legacy from '@vitejs/plugin-legacy';
import { visualizer } from 'rollup-plugin-visualizer';
import tailwindcss from '@tailwindcss/postcss';
import autoprefixer from 'autoprefixer';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// Note: Compression (gzip/brotli) is handled by the web server/CDN
// Image optimization can be added back when plugins support Vite 7

export default defineConfig({
  plugins: [
    legacy({
      targets: ['defaults', 'not IE 11'],
    }),
    visualizer({
      open: false,
      filename: 'dist/bundle-analysis.html',
    }),
  ],
  root: '.',
  base: './',
  build: {
    manifest: true,
    outDir: 'dist',
    assetsDir: 'assets',
    minify: 'terser',
    cssCodeSplit: true,
    sourcemap: false,
    rollupOptions: {
      input: {
        app: resolve(__dirname, 'resources/js/app.ts'),
        styles: resolve(__dirname, 'resources/css/app.css'),
        editor: resolve(__dirname, 'resources/css/editor-style.css'),
      },
      output: {
        manualChunks: {
          vendor: ['alpinejs'],
        },
        entryFileNames: 'assets/[name]-[hash].js',
        chunkFileNames: 'assets/[name]-[hash].js',
        assetFileNames: 'assets/[name]-[hash].[ext]',
      },
    },
    terserOptions: {
      compress: {
        drop_console: true,
        drop_debugger: true,
      },
    },
  },
  server: {
    origin: process.env.VITE_DEV_SERVER_URL || 'http://localhost:5173',
    port: parseInt(process.env.VITE_DEV_SERVER_PORT) || 5173,
    strictPort: true,
    watch: {
      // Watch Blade templates
      ignored: ['!**/templates/**', '!**/config/**'],
    },
  },
  css: {
    postcss: {
      plugins: [tailwindcss(), autoprefixer()],
    },
  },
});