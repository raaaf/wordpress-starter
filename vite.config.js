import { defineConfig } from 'vite';
import { fileURLToPath } from 'url';
import { dirname, resolve } from 'path';
import { visualizer } from 'rollup-plugin-visualizer';
import tailwindcss from '@tailwindcss/vite';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

// Note: Compression (gzip/brotli) is handled by the web server/CDN
// Image optimization can be added back when plugins support Vite 7
// Legacy browser support removed - saves ~44KB (modern browsers only)

export default defineConfig({
  plugins: [
    // TailwindCSS must be first for optimal performance
    tailwindcss(),
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
    minify: 'esbuild', // esbuild is faster and built into Vite
    cssCodeSplit: true,
    sourcemap: false,
    rollupOptions: {
      input: {
        app: resolve(__dirname, 'resources/js/app.ts'),
        styles: resolve(__dirname, 'resources/css/app.css'),
        // Note: Editor CSS/JS removed - all ACF blocks use edit mode (no preview rendering)
        // ACF field UI styling is handled via inline CSS in PHP
      },
      output: {
        manualChunks: {
          vendor: ['alpinejs', '@alpinejs/collapse', 'medium-zoom'],
        },
        entryFileNames: 'assets/[name]-[hash].js',
        chunkFileNames: 'assets/[name]-[hash].js',
        assetFileNames: 'assets/[name]-[hash].[ext]',
      },
    },
    // esbuild minify options (drop console/debugger in production)
    esbuild: {
      drop: ['console', 'debugger'],
    },
  },
  server: {
    origin: process.env.VITE_DEV_SERVER_URL || 'http://localhost:5173',
    host: true, // Listen on all interfaces
    port: parseInt(process.env.VITE_DEV_SERVER_PORT) || 5173,
    strictPort: true,
    cors: true,
    watch: {
      // Watch Blade templates
      ignored: ['!**/templates/**', '!**/config/**'],
    },
  },
});