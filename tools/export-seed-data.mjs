/**
 * Exports the data the image generator draws on, into tools/seed-data.json.
 *
 * It reads the public API rather than the database on purpose: the specs printed on a card are
 * then formatted by `config/hardware.php` through the API resources — "2.000 GB", "65 W", "AM5" —
 * exactly as the React UI formats them. Reading `products.specs` straight from MySQL would give
 * raw JSON values and a second place where units and labels are decided.
 *
 * Usage (with the local stack running: docker compose up -d):
 *   node tools/export-seed-data.mjs
 *   node tools/export-seed-data.mjs --api https://pcbuild-api-2mwk.onrender.com/api
 *
 * Then redraw the cards: node tools/generate-seed-images.mjs
 */
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const OUT = path.join(ROOT, 'tools', 'seed-data.json');

const argApi = process.argv.indexOf('--api');
const API = argApi > -1 ? process.argv[argApi + 1] : 'http://localhost:8000/api';

/** The components endpoint caps per_page at 50, so every listing is paged. */
const PER_PAGE = 50;

async function get(url) {
  const res = await fetch(url);
  const body = await res.json();
  if (!res.ok || !body.success) {
    throw new Error(`${url} → ${res.status} ${JSON.stringify(body).slice(0, 200)}`);
  }
  return body;
}

async function all(url) {
  const rows = [];
  for (let page = 1; ; page++) {
    const sep = url.includes('?') ? '&' : '?';
    const body = await get(`${url}${sep}per_page=${PER_PAGE}&page=${page}`);
    rows.push(...body.data);
    if (page >= (body.meta?.last_page ?? 1)) return rows;
  }
}

const highlights = (specs) => (specs ?? []).filter((s) => s.highlight).map((s) => s.display);

const categories = await all(`${API}/categories`);

const products = [];
for (const category of categories) {
  const rows = await all(`${API}/components?category=${encodeURIComponent(category.slug)}`);
  rows.sort((a, b) => a.slug.localeCompare(b.slug));
  for (const p of rows) {
    products.push({
      slug: p.slug,
      name: p.name,
      brand: p.brand,
      category: p.category,
      category_name: p.category_name,
      price: p.price,
      highlights: highlights(p.specs),
    });
  }
}

const builds = [];
for (const summary of await all(`${API}/builds`)) {
  const { data: build } = await get(`${API}/builds/${summary.slug}`);
  // The card subtitle names the parts a reader recognises a build by.
  const named = (slug) => build.items.find((i) => i.category === slug)?.product?.name ?? null;
  builds.push({
    slug: build.slug,
    name: build.name,
    purpose: build.purpose_label,
    total: build.total_price,
    cpu: named('cpu'),
    gpu: named('gpu'),
    ram: named('ram'),
  });
}

fs.writeFileSync(OUT, JSON.stringify({ products, builds }, null, 1) + '\n', 'utf8');
console.log(`Wrote ${products.length} products and ${builds.length} builds to ${path.relative(ROOT, OUT)}`);
