// Generate hero.webp for login brand panel: procedural blueprint-themed SVG rasterized with Gaussian blur.
import sharp from 'sharp';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { mkdir } from 'node:fs/promises';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const outDir = path.resolve(__dirname, '../public/images');
await mkdir(outDir, { recursive: true });

const W = 1920;
const H = 1080;

const svg = `
<svg xmlns="http://www.w3.org/2000/svg" width="${W}" height="${H}" viewBox="0 0 ${W} ${H}">
    <defs>
        <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#0d3322"/>
            <stop offset="60%" stop-color="#0a2818"/>
            <stop offset="100%" stop-color="#061a10"/>
        </linearGradient>
        <radialGradient id="glowA" cx="18%" cy="20%" r="42%">
            <stop offset="0%" stop-color="rgba(120, 200, 150, 0.18)"/>
            <stop offset="100%" stop-color="rgba(0,0,0,0)"/>
        </radialGradient>
        <radialGradient id="glowB" cx="82%" cy="78%" r="48%">
            <stop offset="0%" stop-color="rgba(20, 184, 166, 0.14)"/>
            <stop offset="100%" stop-color="rgba(0,0,0,0)"/>
        </radialGradient>
        <pattern id="grid" x="0" y="0" width="64" height="64" patternUnits="userSpaceOnUse">
            <path d="M 64 0 L 0 0 0 64" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="1"/>
        </pattern>
        <pattern id="dots" x="0" y="0" width="32" height="32" patternUnits="userSpaceOnUse">
            <circle cx="2" cy="2" r="1" fill="rgba(255,255,255,0.06)"/>
        </pattern>
    </defs>

    <rect width="${W}" height="${H}" fill="url(#bg)"/>
    <rect width="${W}" height="${H}" fill="url(#grid)"/>
    <rect width="${W}" height="${H}" fill="url(#dots)"/>
    <rect width="${W}" height="${H}" fill="url(#glowA)"/>
    <rect width="${W}" height="${H}" fill="url(#glowB)"/>

    <!-- Isometric blueprint lines -->
    <g stroke="rgba(255,255,255,0.07)" stroke-width="1.2" fill="none">
        <path d="M 200 720 L 960 420 L 1720 720 L 960 1020 Z"/>
        <path d="M 320 760 L 960 520 L 1600 760 L 960 1000 Z"/>
        <path d="M 440 800 L 960 620 L 1480 800 L 960 980 Z"/>
    </g>

    <!-- Floating circles -->
    <g fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="1.5">
        <circle cx="320" cy="280" r="160"/>
        <circle cx="320" cy="280" r="110"/>
        <circle cx="1500" cy="240" r="80"/>
    </g>

    <!-- Subtle accent line -->
    <line x1="0" y1="540" x2="${W}" y2="540" stroke="rgba(255,255,255,0.04)" stroke-width="1"/>
</svg>
`;

const out = path.join(outDir, 'hero.webp');
await sharp(Buffer.from(svg))
    .blur(6)
    .modulate({ brightness: 0.95 })
    .webp({ quality: 82, effort: 4 })
    .toFile(out);

console.log('build:hero →', path.relative(path.resolve(__dirname, '..'), out));
