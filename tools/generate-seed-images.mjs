/**
 * Generates the placeholder images for products and template builds.
 *
 * Why generated rather than photographed: the demo data names specific parts
 * ("Sapphire PULSE Radeon RX 7700 XT 12GB"). A stock photo of some other card would show the
 * viewer the wrong hardware on a site whose whole job is telling you what you are looking at.
 * A card carrying the real model name and the real highlight specs is both accurate and free of
 * anyone else's copyright.
 *
 * Renders with headless Chrome because the PHP image extensions are not installed in the API
 * image and adding them (plus a Unicode font) would grow a 262 MB production image for a task
 * that runs once. The rendered files are committed instead, so `app:import-images` needs nothing
 * but PHP and runs from the Render shell, where there is no browser.
 *
 * Usage:
 *   node tools/generate-seed-images.mjs                 # reads tools/seed-data.json
 *   node tools/generate-seed-images.mjs --port 9400     # if 9337 is busy
 *
 * Regenerating the data file is documented in docs/DEPLOYMENT.md.
 */
import fs from 'node:fs';
import path from 'node:path';
import { spawn } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const DATA = path.join(ROOT, 'tools', 'seed-data.json');
const OUT = path.join(ROOT, 'backend', 'resources', 'seed-images');

const argPort = process.argv.indexOf('--port');
const PORT = argPort > -1 ? Number(process.argv[argPort + 1]) : 9337;

const WIDTH = 1000;
const HEIGHT = 750;

/**
 * JPEG, not PNG: the card is a smooth gradient, which PNG stores almost losslessly and badly
 * (the same 60 cards came to 25 MB as PNG against ~2 MB here). Cloudinary re-encodes to WebP or
 * AVIF through `f_auto` on delivery anyway, so the stored format only decides repository weight.
 */
const QUALITY = 86;

/** Per-category accent, matching the category colours used in the React UI. */
const ACCENT = {
  cpu: ['#0ea5e9', '#0369a1'],
  motherboard: ['#10b981', '#047857'],
  ram: ['#8b5cf6', '#6d28d9'],
  gpu: ['#ef4444', '#b91c1c'],
  storage: ['#f59e0b', '#b45309'],
  psu: ['#84cc16', '#4d7c0f'],
  case: ['#64748b', '#334155'],
  cooler: ['#06b6d4', '#0e7490'],
  build: ['#2563eb', '#1e3a8a'],
};

const vnd = (n) => new Intl.NumberFormat('vi-VN').format(n) + ' ₫';
const esc = (s) =>
  String(s ?? '').replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c]);

function cardHtml({ kind, eyebrow, title, subtitle, chips, price }) {
  const [from, to] = ACCENT[kind] ?? ACCENT.build;
  return `<!doctype html><html lang="vi"><head><meta charset="utf-8">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  *{margin:0;padding:0;box-sizing:border-box}
  html,body{width:${WIDTH}px;height:${HEIGHT}px}
  body{font-family:'Be Vietnam Pro',system-ui,sans-serif;background:linear-gradient(135deg,${from},${to});
       color:#fff;display:flex;flex-direction:column;justify-content:space-between;padding:72px 76px;position:relative;overflow:hidden}
  .grid{position:absolute;inset:0;opacity:.13;
        background-image:linear-gradient(#fff 1px,transparent 1px),linear-gradient(90deg,#fff 1px,transparent 1px);
        background-size:64px 64px}
  .orb{position:absolute;right:-140px;bottom:-190px;width:620px;height:620px;border-radius:50%;
       background:radial-gradient(circle at 30% 30%,rgba(255,255,255,.26),rgba(255,255,255,0) 62%)}
  .top,.bottom{position:relative;z-index:1}
  .eyebrow{font-size:26px;font-weight:600;letter-spacing:.20em;text-transform:uppercase;opacity:.85;margin-bottom:26px}
  h1{font-size:${title.length > 44 ? 52 : title.length > 30 ? 62 : 72}px;font-weight:700;line-height:1.14;
     letter-spacing:-.015em;text-wrap:balance}
  .sub{margin-top:20px;font-size:30px;font-weight:400;opacity:.88;line-height:1.35}
  .chips{display:flex;flex-wrap:wrap;gap:14px;margin-bottom:26px}
  .chip{font-size:25px;font-weight:600;padding:11px 22px;border-radius:999px;
        background:rgba(255,255,255,.19);border:1px solid rgba(255,255,255,.3)}
  .price{font-size:44px;font-weight:700;letter-spacing:-.01em}
</style></head><body>
  <div class="grid"></div><div class="orb"></div>
  <div class="top">
    <div class="eyebrow">${esc(eyebrow)}</div>
    <h1>${esc(title)}</h1>
    ${subtitle ? `<div class="sub">${esc(subtitle)}</div>` : ''}
  </div>
  <div class="bottom">
    ${chips?.length ? `<div class="chips">${chips.map((c) => `<span class="chip">${esc(c)}</span>`).join('')}</div>` : ''}
    ${price ? `<div class="price">${esc(price)}</div>` : ''}
  </div>
</body></html>`;
}

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

function chromePath() {
  const candidates = [
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
    '/usr/bin/google-chrome',
    '/usr/bin/chromium',
    '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  ];
  const found = candidates.find((p) => fs.existsSync(p));
  if (!found) throw new Error('Chrome not found. Install Chrome or edit chromePath().');
  return found;
}

async function connect() {
  for (let i = 0; i < 40; i++) {
    try {
      const list = await (await fetch(`http://127.0.0.1:${PORT}/json/list`)).json();
      const page = list.find((t) => t.type === 'page');
      if (page) {
        return await new Promise((res, rej) => {
          const ws = new WebSocket(page.webSocketDebuggerUrl);
          ws.onopen = () => res(ws);
          ws.onerror = rej;
        });
      }
    } catch {
      /* not up yet */
    }
    await sleep(500);
  }
  throw new Error('Could not reach Chrome on port ' + PORT);
}

let msgId = 0;
const send = (ws, method, params = {}) => {
  const id = ++msgId;
  return new Promise((resolve, reject) => {
    const onMsg = (e) => {
      const d = JSON.parse(e.data);
      if (d.id === id) {
        ws.removeEventListener('message', onMsg);
        d.error ? reject(new Error(method + ': ' + JSON.stringify(d.error))) : resolve(d.result);
      }
    };
    ws.addEventListener('message', onMsg);
    ws.send(JSON.stringify({ id, method, params }));
  });
};

const data = JSON.parse(fs.readFileSync(DATA, 'utf8'));

const jobs = [
  ...data.products.map((p) => ({
    file: path.join(OUT, 'products', `${p.slug}.jpg`),
    html: cardHtml({
      kind: p.category,
      eyebrow: p.category_name,
      title: p.name,
      subtitle: null,
      chips: [...(p.highlights ?? []), p.brand].filter(Boolean),
      price: vnd(p.price),
    }),
  })),
  ...data.builds.map((b) => ({
    file: path.join(OUT, 'builds', `${b.slug}.jpg`),
    html: cardHtml({
      kind: 'build',
      eyebrow: b.purpose ?? 'Cấu hình mẫu',
      title: b.name,
      subtitle: [b.cpu, b.gpu].filter(Boolean).join('  ·  '),
      chips: [],
      price: vnd(b.total),
    }),
  })),
];

fs.mkdirSync(path.join(OUT, 'products'), { recursive: true });
fs.mkdirSync(path.join(OUT, 'builds'), { recursive: true });

const profile = path.join(OUT, '.chrome-profile');
const chrome = spawn(
  chromePath(),
  [
    '--headless=new',
    '--disable-gpu',
    '--hide-scrollbars',
    `--remote-debugging-port=${PORT}`,
    `--user-data-dir=${profile}`,
    '--no-first-run',
    '--no-default-browser-check',
    'about:blank',
  ],
  { stdio: 'ignore', detached: false },
);

let ws;
try {
  ws = await connect();
  await send(ws, 'Page.enable');
  await send(ws, 'Emulation.setDeviceMetricsOverride', {
    width: WIDTH,
    height: HEIGHT,
    deviceScaleFactor: 1,
    mobile: false,
  });

  let done = 0;
  for (const job of jobs) {
    await send(ws, 'Page.navigate', {
      url: 'data:text/html;charset=utf-8,' + encodeURIComponent(job.html),
    });
    // The webfont must be laid out before the capture, or the text is measured with a fallback.
    await sleep(450);
    await send(ws, 'Runtime.evaluate', { expression: 'document.fonts.ready', awaitPromise: true });
    const { data: img } = await send(ws, 'Page.captureScreenshot', { format: 'jpeg', quality: QUALITY });
    fs.writeFileSync(job.file, Buffer.from(img, 'base64'));
    done++;
    if (done % 10 === 0 || done === jobs.length) console.log(`  ${done}/${jobs.length}`);
  }
  console.log(`\nWrote ${done} images to ${path.relative(ROOT, OUT)}`);
} finally {
  try {
    ws?.close();
  } catch {
    /* already closed */
  }
  chrome.kill();
  await sleep(600);
  fs.rmSync(profile, { recursive: true, force: true });
}
