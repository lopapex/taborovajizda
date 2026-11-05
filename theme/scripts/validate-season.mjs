import { readFile } from 'node:fs/promises';
import { access } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import Ajv2020 from 'ajv/dist/2020.js';
import addFormats from 'ajv-formats';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const season = JSON.parse(await readFile(path.join(root, 'config/season.json'), 'utf8'));
const schema = JSON.parse(await readFile(path.join(root, 'config/season.schema.json'), 'utf8'));
const ajv = new Ajv2020({ allErrors: true, strict: false });
addFormats(ajv);

if (!ajv.validate(schema, season)) {
  console.error(ajv.errorsText(ajv.errors, { separator: '\n' }));
  process.exit(1);
}

if (season.startDate && season.endDate && season.startDate > season.endDate) {
  console.error('startDate must not be after endDate.');
  process.exit(1);
}

for (const [name, asset] of Object.entries({
  hero: 'resources/assets/season/hero.jpg',
  background: 'resources/assets/season/background.jpg',
})) {
  try {
    await access(path.join(root, asset));
  } catch {
    console.error(`Season asset ${name} does not exist: ${asset}`);
    process.exit(1);
  }
}

console.log(`Season ${season.year} (${season.slug}) is valid.`);
