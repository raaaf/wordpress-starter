import { defineConfig } from 'vite';
import { fileURLToPath } from 'url';
import { dirname, resolve } from 'path';
import { writeFileSync, existsSync, unlinkSync } from 'fs';
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
    // Write active port to file so PHP can read it
    {
      name: 'write-port-file',
      configureServer(server) {
        const portFile = resolve(__dirname, '.vite-port');

        server.httpServer?.once('listening', () => {
          const address = server.httpServer?.address();
          const port = typeof address === 'object' ? address?.port : null;
          if (port) {
            writeFileSync(portFile, String(port));
          }
        });

        const cleanup = () => {
          if (existsSync(portFile)) {
            unlinkSync(portFile);
          }
        };

        server.httpServer?.on('close', cleanup);
        process.on('SIGINT', cleanup);
        process.on('SIGTERM', cleanup);
      },
    },
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
        'admin-flexible-titles': resolve(__dirname, 'resources/js/admin/flexible-titles.ts'),
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
    origin: process.env.VITE_DEV_SERVER_URL || 'http://localhost:5180',
    host: true, // Listen on all interfaces
    port: parseInt(process.env.VITE_DEV_SERVER_PORT) || 5180,
    strictPort: false,
    cors: true,
    watch: {
      // Watch Blade templates
      ignored: ['!**/templates/**', '!**/config/**'],
    },
  },
});
