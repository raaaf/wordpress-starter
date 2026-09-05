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

// Normalises VITE_HOST into the value Vite's `server.host` option expects:
// 'true'/'1' (any case) -> boolean true (wildcard, all interfaces)
// unset -> 'localhost'
// anything else (e.g. '0.0.0.0') -> passed through as-is; note that
// '0.0.0.0' exposes the dev server on the LAN, not just this machine.
function resolveViteHost(value) {
  if (!value) {
    return 'localhost';
  }
  return ['true', '1'].includes(value.toLowerCase()) ? true : value;
}

export default defineConfig(({ mode }) => ({
  plugins: [
    // TailwindCSS must be first for optimal performance
    tailwindcss(),
    // Only generate the bundle analysis report for `npm run analyze`,
    // otherwise it ends up in every production build (and release zip)
    ...(mode === 'analyze'
      ? [
          visualizer({
            open: false,
            filename: 'dist/bundle-analysis.html',
          }),
        ]
      : []),
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
        'editor-style': resolve(__dirname, 'resources/css/editor-style.css'),
        'admin-flexible-titles': resolve(__dirname, 'resources/js/admin/flexible-titles.ts'),
      },
      output: {
        manualChunks(id) {
          if (
            id.includes('node_modules/alpinejs') ||
            id.includes('node_modules/@alpinejs/collapse')
          ) {
            return 'vendor';
          }
        },
        entryFileNames: 'assets/[name]-[hash].js',
        chunkFileNames: 'assets/[name]-[hash].js',
        assetFileNames: 'assets/[name]-[hash].[ext]',
      },
    },
  },
  // esbuild minify options (drop console/debugger in production only)
  // Must sit at the top level, not under `build` - Vite ignores it there.
  esbuild:
    mode !== 'development'
      ? {
          drop: ['console', 'debugger'],
        }
      : undefined,
  server: {
    origin: process.env.VITE_DEV_SERVER_URL || 'http://localhost:5180',
    // Bind to localhost only by default - Local by Flywheel sites are
    // reached through https://wordpress.local, the dev server only needs
    // to be reachable locally for HMR. Set VITE_HOST=true (or 1/True) to
    // opt in to listening on all interfaces (e.g. testing from another
    // device).
    host: resolveViteHost(process.env.VITE_HOST),
    port: parseInt(process.env.VITE_DEV_SERVER_PORT) || 5180,
    strictPort: true,
    cors: {
      origin: ['https://wordpress.local', 'http://wordpress.local'],
    },
    watch: {
      // Watch Blade templates
      ignored: ['!**/templates/**', '!**/config/**'],
    },
  },
}));
