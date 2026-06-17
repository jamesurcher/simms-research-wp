#!/usr/bin/env node
import { mkdir, readFile, writeFile } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const PRODUCT_INDEX_URL = 'https://simmsresearch.com/products.json?limit=250';
const LAB_RESULTS_URL = 'https://simmsresearch.com/pages/lab-results';
const SHOP_BASE_URL = 'https://simmsresearch.com';

const scriptDir = path.dirname(fileURLToPath(import.meta.url));
const outputDir = path.resolve(
  scriptDir,
  '../wp-content/plugins/simms-lab-results/import/generated',
);

const productHeaders = [
  'shopify_handle',
  'wp_product_id',
  'sku',
  'title',
  'description',
  'status',
  'regular_price',
  'sale_price',
  'categories',
  'tags',
  'image_urls',
  'gallery_image_urls',
  'variant_option_name',
  'variant_option_value',
  'stock_quantity',
  'stock_status',
  'simms_cas',
  'simms_formula',
  'simms_molecular_weight',
  'simms_sequence',
  'simms_form',
  'simms_solubility',
  'simms_storage',
  'simms_purity',
  'simms_dosage_summary',
];

const coaHeaders = [
  'shopify_product_handle',
  'wp_product_id',
  'variant_label',
  'batch_id',
  'purity',
  'avg_purity',
  'vials_tested',
  'labeled_content',
  'net_content',
  'net_content_delta',
  'endotoxins',
  'heavy_metals',
  'sterility',
  'test_type',
  'tested_at',
  'coa_url',
  'coa_file_path',
  'is_current',
];

const specMap = new Map([
  ['cas number', 'simms_cas'],
  ['molecular formula', 'simms_formula'],
  ['molecular weight', 'simms_molecular_weight'],
  ['sequence', 'simms_sequence'],
  ['form', 'simms_form'],
  ['solubility', 'simms_solubility'],
  ['storage', 'simms_storage'],
  ['purity', 'simms_purity'],
  ['dosage summary', 'simms_dosage_summary'],
  ['dosage', 'simms_dosage_summary'],
]);

main().catch((error) => {
  console.error(error instanceof Error ? error.message : String(error));
  process.exitCode = 1;
});

async function main() {
  const options = parseArgs(process.argv.slice(2));
  await mkdir(outputDir, { recursive: true });

  const [productText, labHtml] = await Promise.all([
    loadTextSource(PRODUCT_INDEX_URL, options.productsFile, options.offline),
    loadTextSource(LAB_RESULTS_URL, options.labFile, options.offline),
  ]);
  const productJson = JSON.parse(productText);

  const products = Array.isArray(productJson.products) ? productJson.products : [];
  const specsByHandle = await fetchProductSpecs(products, options);
  const productRows = buildProductRows(products, specsByHandle);
  const coaRows = extractCoaRows(labHtml);

  await writeFile(
    path.join(outputDir, 'products-public.csv'),
    toCsv(productHeaders, productRows),
  );
  await writeFile(
    path.join(outputDir, 'coa-batches-public.csv'),
    toCsv(coaHeaders, coaRows),
  );
  await writeFile(
    path.join(outputDir, 'source-manifest.json'),
    `${JSON.stringify(
      {
        fetched_at: new Date().toISOString(),
        sources: {
          products: PRODUCT_INDEX_URL,
          lab_results: LAB_RESULTS_URL,
        },
        counts: {
          products: products.length,
          product_rows: productRows.length,
          coa_rows: coaRows.length,
        },
      },
      null,
      2,
    )}\n`,
  );

  console.log(`Wrote ${productRows.length} product rows from ${products.length} products.`);
  console.log(`Wrote ${coaRows.length} COA rows.`);
  console.log(outputDir);
}

async function fetchProductSpecs(products, options) {
  if (options.offline && !options.productPageDir) {
    return new Map(products.map((product) => [String(product.handle || '').trim(), {}]));
  }

  const pairs = await Promise.all(
    products.map(async (product) => {
      const handle = String(product.handle || '').trim();
      if (!handle) {
        return ['', {}];
      }

      const url = `${SHOP_BASE_URL}/products/${encodeURIComponent(handle)}`;
      const cachedPage = options.productPageDir
        ? path.join(options.productPageDir, `${handle}.html`)
        : '';

      try {
        let html = '';
        if (cachedPage) {
          try {
            html = await readFile(cachedPage, 'utf8');
          } catch (error) {
            if (options.offline) {
              throw error;
            }
          }
        }
        if (!html) {
          html = await loadTextSource(url, '', options.offline);
        }
        return [handle, extractSpecs(html)];
      } catch (error) {
        console.warn(`Warning: could not fetch specs for ${handle}: ${error.message}`);
        return [handle, {}];
      }
    }),
  );

  return new Map(pairs.filter(([handle]) => handle));
}

function buildProductRows(products, specsByHandle) {
  const rows = [];

  for (const product of products) {
    const handle = String(product.handle || '').trim();
    if (!handle) {
      continue;
    }

    const variants = Array.isArray(product.variants) && product.variants.length
      ? product.variants
      : [{ title: 'Default Title', available: true }];
    const hasMultipleVariants = variants.length > 1;
    const optionName = resolveOptionName(product, variants);
    const images = Array.isArray(product.images)
      ? product.images.map((image) => normalizeUrl(image.src || image)).filter(Boolean)
      : [];
    const specs = specsByHandle.get(handle) || {};

    for (const variant of variants) {
      const variantTitle = String(variant.title || '').trim();
      const variantOptionValue = hasMultipleVariants && variantTitle && variantTitle !== 'Default Title'
        ? variantTitle
        : '';

      rows.push({
        shopify_handle: handle,
        wp_product_id: '',
        sku: variant.sku || '',
        title: product.title || '',
        description: cleanDescription(product.body_html || ''),
        status: product.published_at === null ? 'draft' : 'publish',
        regular_price: variant.price || '',
        sale_price: '',
        categories: product.product_type || '',
        tags: Array.isArray(product.tags) ? product.tags.join('|') : '',
        image_urls: images[0] || '',
        gallery_image_urls: images.slice(1).join('|'),
        variant_option_name: variantOptionValue ? optionName : '',
        variant_option_value: variantOptionValue,
        stock_quantity: '',
        stock_status: variant.available === false ? 'outofstock' : 'instock',
        simms_cas: specs.simms_cas || '',
        simms_formula: specs.simms_formula || '',
        simms_molecular_weight: specs.simms_molecular_weight || '',
        simms_sequence: specs.simms_sequence || '',
        simms_form: specs.simms_form || '',
        simms_solubility: specs.simms_solubility || '',
        simms_storage: specs.simms_storage || '',
        simms_purity: specs.simms_purity || '',
        simms_dosage_summary: specs.simms_dosage_summary || '',
      });
    }
  }

  return rows;
}

function resolveOptionName(product, variants) {
  const option = Array.isArray(product.options)
    ? product.options.find((item) => item && item.name && item.name !== 'Title')
    : null;

  if (option && option.name) {
    return option.name;
  }

  return variants.length > 1 ? 'Size' : 'Size';
}

function extractSpecs(html) {
  const specs = {};
  const dl = html.match(/<dl\b[^>]*class="[^"]*product-research-details__spec-grid[^"]*"[^>]*>([\s\S]*?)<\/dl>/i);

  if (!dl) {
    return specs;
  }

  for (const match of dl[1].matchAll(/<div\b[^>]*class="[^"]*product-research-details__spec[^"]*"[^>]*>([\s\S]*?)<\/div>/gi)) {
    const label = stripHtml(firstMatch(match[1], /<dt\b[^>]*>([\s\S]*?)<\/dt>/i)).toLowerCase();
    const value = stripHtml(firstMatch(match[1], /<dd\b[^>]*>([\s\S]*?)<\/dd>/i));
    const key = specMap.get(label);

    if (key && value) {
      specs[key] = value;
    }
  }

  return specs;
}

function extractCoaRows(html) {
  const rows = [];
  const sections = [];
  const sectionRegex = /<section\b(?=[^>]*\bdata-lab-detail\b)[^>]*\bdata-product-handle="([^"]+)"[^>]*>/gi;

  for (const match of html.matchAll(sectionRegex)) {
    sections.push({
      handle: match[1],
      start: match.index,
    });
  }

  for (let index = 0; index < sections.length; index += 1) {
    const section = sections[index];
    const nextSection = sections[index + 1];
    const sectionHtml = html.slice(section.start, nextSection ? nextSection.start : html.length);

    for (const rowMatch of sectionHtml.matchAll(/<tr\b[^>]*>([\s\S]*?)<\/tr>/gi)) {
      const rowHtml = rowMatch[1];
      if (!tdByLabel(rowHtml, 'Batch')) {
        continue;
      }

      const batchCell = tdByLabel(rowHtml, 'Batch');
      const netContent = parseNetContent(tdByLabel(rowHtml, 'Net Content'));
      const labeledContent = textByLabel(rowHtml, 'Labeled Content');
      const purity = textByLabel(rowHtml, 'Purity');
      const testedAt = parseDate(textByLabel(rowHtml, 'Test Date'));
      const coaUrl = normalizeUrl(attr(rowHtml, 'data-coa-url'));
      const batchId = stripHtml(removePills(batchCell)).replace(/^#/, '').replace(/\s+/g, '');

      if (!batchId) {
        continue;
      }

      rows.push({
        shopify_product_handle: section.handle,
        wp_product_id: '',
        variant_label: labeledContent,
        batch_id: batchId,
        purity,
        avg_purity: purity,
        vials_tested: textByLabel(rowHtml, 'Vials Tested'),
        labeled_content: labeledContent,
        net_content: netContent.value,
        net_content_delta: netContent.delta,
        endotoxins: textByLabel(rowHtml, 'Endotoxins'),
        heavy_metals: textByLabel(rowHtml, 'Heavy Metals'),
        sterility: textByLabel(rowHtml, 'Sterility'),
        test_type: textByLabel(rowHtml, 'Confirmation Method'),
        tested_at: testedAt,
        coa_url: coaUrl,
        coa_file_path: '',
        is_current: /\blab-results__pill\b/i.test(batchCell) ? '1' : '0',
      });
    }
  }

  return rows;
}

function tdByLabel(rowHtml, label) {
  const pattern = new RegExp(
    `<td\\b[^>]*\\bdata-label=["']${escapeRegExp(label)}["'][^>]*>([\\s\\S]*?)<\\/td>`,
    'i',
  );
  return firstMatch(rowHtml, pattern);
}

function textByLabel(rowHtml, label) {
  return stripHtml(removePills(tdByLabel(rowHtml, label)));
}

function parseNetContent(cellHtml) {
  const deltaMatch = cellHtml.match(/<span\b[^>]*class="[^"]*\blab-results__delta\b[^"]*"[^>]*>([\s\S]*?)<\/span>/i);
  const delta = deltaMatch ? stripHtml(deltaMatch[1]).replace(/^\((.*)\)$/u, '$1') : '';
  const withoutDelta = deltaMatch ? cellHtml.replace(deltaMatch[0], '') : cellHtml;

  return {
    value: stripHtml(withoutDelta),
    delta,
  };
}

function parseDate(value) {
  const normalized = value.trim();
  const match = normalized.match(/^([A-Za-z]+)\s+(\d{1,2}),\s+(\d{4})$/);
  if (!match) {
    return normalized;
  }

  const months = {
    jan: '01',
    feb: '02',
    mar: '03',
    apr: '04',
    may: '05',
    jun: '06',
    jul: '07',
    aug: '08',
    sep: '09',
    oct: '10',
    nov: '11',
    dec: '12',
  };
  const month = months[match[1].slice(0, 3).toLowerCase()];
  if (!month) {
    return normalized;
  }

  return `${match[3]}-${month}-${match[2].padStart(2, '0')}`;
}

function cleanDescription(html) {
  return html
    .replace(/<meta\b[^>]*>/gi, '')
    .replace(/\u00a0/g, ' ')
    .trim();
}

function stripHtml(html) {
  return decodeHtml(
    String(html || '')
      .replace(/<script\b[\s\S]*?<\/script>/gi, '')
      .replace(/<style\b[\s\S]*?<\/style>/gi, '')
      .replace(/<br\s*\/?>/gi, '\n')
      .replace(/<[^>]+>/g, '')
      .replace(/\u00a0/g, ' '),
  )
    .replace(/\s+/g, ' ')
    .trim();
}

function removePills(html) {
  return String(html || '').replace(
    /<span\b[^>]*class="[^"]*\b(?:lab-results__pill|lab-card__pill)\b[^"]*"[^>]*>[\s\S]*?<\/span>/gi,
    '',
  );
}

function normalizeUrl(value) {
  const url = decodeHtml(String(value || '').trim());
  if (!url) {
    return '';
  }
  if (url.startsWith('//')) {
    return `https:${url}`;
  }
  if (url.startsWith('/')) {
    return `${SHOP_BASE_URL}${url}`;
  }
  return url;
}

function attr(html, name) {
  return firstMatch(
    html,
    new RegExp(`\\b${escapeRegExp(name)}=(["'])(.*?)\\1`, 'i'),
    2,
  );
}

function firstMatch(value, pattern, group = 1) {
  const match = String(value || '').match(pattern);
  return match ? match[group] || '' : '';
}

function decodeHtml(value) {
  return String(value || '')
    .replace(/&#x([0-9a-f]+);/gi, (_, code) => String.fromCodePoint(parseInt(code, 16)))
    .replace(/&#(\d+);/g, (_, code) => String.fromCodePoint(parseInt(code, 10)))
    .replace(/&nbsp;/g, ' ')
    .replace(/&amp;/g, '&')
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, "'")
    .replace(/&apos;/g, "'")
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>');
}

function escapeRegExp(value) {
  return String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function toCsv(headers, rows) {
  return `${[
    headers.join(','),
    ...rows.map((row) => headers.map((header) => csvValue(row[header])).join(',')),
  ].join('\n')}\n`;
}

function csvValue(value) {
  const normalized = value === null || value === undefined ? '' : String(value);

  if (!/[",\r\n]/.test(normalized)) {
    return normalized;
  }

  return `"${normalized.replace(/"/g, '""')}"`;
}

async function loadTextSource(url, filePath, offline = false) {
  if (filePath) {
    return readFile(filePath, 'utf8');
  }

  if (offline) {
    throw new Error(`Missing local source file for ${url}`);
  }

  return fetchText(url);
}

async function fetchText(url) {
  const response = await fetch(url, {
    headers: {
      accept: 'text/html,application/json;q=0.9,*/*;q=0.8',
      'user-agent': 'SimmsResearchWordPressMigration/1.0',
    },
  });

  if (!response.ok) {
    throw new Error(`Request failed (${response.status}) for ${url}`);
  }

  return response.text();
}

function parseArgs(args) {
  const options = {
    offline: false,
    productsFile: '',
    labFile: '',
    productPageDir: '',
  };

  for (let index = 0; index < args.length; index += 1) {
    const arg = args[index];
    if (!arg.startsWith('--')) {
      continue;
    }

    const key = arg.slice(2).replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());
    const next = args[index + 1] || '';
    if (!next || next.startsWith('--')) {
      options[key] = true;
      continue;
    }

    options[key] = next;
    index += 1;
  }

  return options;
}
