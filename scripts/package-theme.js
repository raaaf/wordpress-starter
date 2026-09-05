/**
 * Before running this script, install production-only dependencies:
 *   composer install --no-dev --no-scripts
 * A dev install (with phpunit/phpstan present) ships test tooling and dev
 * autoload maps inside the release zip, which is why this script refuses to
 * archive vendor/ when it detects one.
 */
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

  const composerJson = JSON.parse(await readFile(join(rootDir, 'composer.json'), 'utf-8'));
  const devPackages = Object.keys(composerJson['require-dev'] || {});
  const installedDevPackages = devPackages.filter((name) =>
    existsSync(join(rootDir, 'vendor', name))
  );

  if (installedDevPackages.length > 0) {
    console.error(
      `Error: vendor/ contains dev dependencies (${installedDevPackages.join(', ')}). ` +
        'Run "composer install --no-dev --no-scripts" before packaging.'
    );
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
          const baseName = entryData.name.split('/').pop();

          // macOS-Muell gehoert in kein ausgeliefertes Zip.
          if (baseName === '.DS_Store') {
            return false;
          }

          // Source maps leak build paths and are never needed at runtime.
          if (baseName.endsWith('.map')) {
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
