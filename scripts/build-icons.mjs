import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const sharpDependency = 'sharp';

let sharp;
try {
    sharp = (await import(sharpDependency)).default;
} catch {
    console.warn('build:icons — modul sharp tidak tersedia, ikon PNG dilewati.');
    console.warn('Aktifkan dengan: npm install && npm run build:icons');
    process.exit(0);
}

const src = path.resolve(__dirname, '../resources/icons/icon.svg');
const outDir = path.resolve(__dirname, '../public/icons');

const svg = readFileSync(src);

await (async () => {
    const fs = await import('node:fs/promises');
    await fs.mkdir(outDir, { recursive: true });
    for (const size of [192, 512]) {
        const out = path.join(outDir, `icon-${size}.png`);
        await sharp(svg, { density: 384 })
            .resize(size, size, { fit: 'cover', position: 'center' })
            .png()
            .toFile(out);
        console.log('build:icons →', path.relative(path.resolve(__dirname, '..'), out));
    }
})();