import archiver from 'archiver';
import { createWriteStream, existsSync, mkdirSync } from 'fs';
import { readFile } from 'fs/promises';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const rootDir = join(__dirname, '..');

async function packageTheme() {
  const pkg = JSON.parse(await readFile(join(rootDir, 'package.json'), 'utf-8'));
  const { name: themeName, version } = pkg;

  console.log(`Packaging ${themeName} v${version}...`);

  if (!existsSync(join(rootDir, 'dist/.vite/manifest.json'))) {
    console.error('Error: Build not found. Run "npm run build" first.');
    process.exit(1);
  }

  const outputDir = join(rootDir, 'wp-theme');
  if (!existsSync(outputDir)) {
    mkdirSync(outputDir, { recursive: true });
  }

  const zipPath = join(outputDir, `${themeName}-${version}.zip`);
  const output = createWriteStream(zipPath);
  const archive = archiver('zip', { zlib: { level: 9 } });

  return new Promise((resolve, reject) => {
    output.on('close', () => {
      const sizeMB = (archive.pointer() / 1024 / 1024).toFixed(2);
      console.log(`Created: wp-theme/${themeName}-${version}.zip (${sizeMB} MB)`);
      resolve();
    });

    archive.on('error', reject);
    archive.on('warning', (err) => {
      if (err.code !== 'ENOENT') reject(err);
    });

    archive.pipe(output);

    const rootFiles = [
      'style.css',
      'functions.php',
      'index.php',
      'theme.json',
      'robots.txt',
      'screenshot.png',
      'LICENSE',
      'README.MD',
      'CHANGELOG.md',
      'composer.json',
      'composer.lock',
      '.env.example',
    ];

    for (const file of rootFiles) {
      const filePath = join(rootDir, file);
      if (existsSync(filePath)) {
        archive.file(filePath, { name: `${themeName}/${file}` });
      }
    }

    const directories = [
      'src',
      'config',
      'templates',
      'dist',
      'vendor',
      'resources/css',
      'resources/js',
      'resources/img',
      'resources/icons',
      'resources/fonts',
      'resources/favicons',
      'assets/images',
      'acf-json',
      'languages',
    ];

    for (const dir of directories) {
      const dirPath = join(rootDir, dir);
      if (existsSync(dirPath)) {
        archive.directory(dirPath, `${themeName}/${dir}`);
      }
    }

    archive.finalize();
  });
}

packageTheme().catch((err) => {
  console.error('Packaging failed:', err);
  process.exit(1);
});
