import { ZipArchive } from 'archiver';
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
  const archive = new ZipArchive({ zlib: { level: 9 } });

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
      'resources/img',
      'resources/icons',
      'resources/fonts',
      'resources/favicons',
      'assets/images',
      'acf-json',
      'languages',
    ];

    // Generated per-site by bin/setup.php; acf-options.php also carries real client
    // contact data (company name, address, phone, email). Never ship these in the zip.
    const generatedConfigFiles = [
      'setup-options.php',
      'plugins-to-install.php',
      'acf-options.php',
      'acf-options.php.processed',
    ];

    for (const dir of directories) {
      const dirPath = join(rootDir, dir);
      if (existsSync(dirPath)) {
        archive.directory(dirPath, `${themeName}/${dir}`, (entryData) => {
          // macOS-Muell gehoert in kein ausgeliefertes Zip.
          if (entryData.name.split('/').pop() === '.DS_Store') {
            return false;
          }

          return dir === 'config' && generatedConfigFiles.includes(entryData.name)
            ? false
            : entryData;
        });
      }
    }

    archive.finalize();
  });
}

packageTheme().catch((err) => {
  console.error('Packaging failed:', err);
  process.exit(1);
});
