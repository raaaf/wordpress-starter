import { defineConfig } from 'vite';
import path from 'path';
import legacy from '@vitejs/plugin-legacy';
import viteCompression from 'vite-plugin-compression';
import viteImagemin from 'vite-plugin-imagemin';
import { visualizer } from 'rollup-plugin-visualizer';

export default defineConfig({
  plugins: [
    legacy({
      targets: ['defaults', 'not IE 11'],
    }),
    viteCompression({
      algorithm: 'gzip',
      ext: '.gz',
    }),
    viteCompression({
      algorithm: 'brotliCompress',
      ext: '.br',
    }),
    viteImagemin({
      gifsicle: {
        optimizationLevel: 7,
        interlaced: false,
      },
      optipng: {
        optimizationLevel: 7,
      },
      mozjpeg: {
        quality: 80,
      },
      pngquant: {
        quality: [0.8, 0.9],
        speed: 4,
      },
      svgo: {
        plugins: [
          {
            name: 'removeViewBox',
          },
          {
            name: 'removeEmptyAttrs',
            active: false,
          },
        ],
      },
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
        app: path.resolve(__dirname, 'resources/js/app.ts'),
        styles: path.resolve(__dirname, 'resources/css/app.css'),
        editor: path.resolve(__dirname, 'resources/css/editor-style.css'),
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
      plugins: [
        require('@tailwindcss/postcss')(),
        require('autoprefixer')(),
      ],
    },
  },
});