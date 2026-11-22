import { readFile } from 'node:fs/promises';
import { access } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import Ajv2020 from 'ajv/dist/2020.js';
import addFormats from 'ajv-formats';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const season = JSON.parse(await readFile(path.join(root, 'config/season.json'), 'utf8'));
const schema = JSON.parse(await readFile(path.join(root, 'config/season.schema.json'), 'utf8'));
const archive = JSON.parse(await readFile(path.join(root, 'config/archive-seasons.json'), 'utf8'));
const archiveSchema = JSON.parse(await readFile(path.join(root, 'config/archive-seasons.schema.json'), 'utf8'));
const ajv = new Ajv2020({ allErrors: true, strict: false });
addFormats(ajv);

const validateSeason = ajv.compile(schema);
const validateArchive = ajv.compile(archiveSchema);

if (!validateSeason(season)) {
  console.error(ajv.errorsText(validateSeason.errors, { separator: '\n' }));
  process.exit(1);
}

if (!validateArchive(archive)) {
  console.error(ajv.errorsText(validateArchive.errors, { separator: '\n' }));
  process.exit(1);
}

const years = archive.seasons.map(({ year }) => year);
const youtubeIds = archive.seasons.map(({ youtubeId }) => youtubeId);
if (new Set(years).size !== years.length || new Set(youtubeIds).size !== youtubeIds.length) {
  console.error('Archive years and YouTube IDs must be unique.');
  process.exit(1);
}
if (!years.every((year, index) => index === 0 || year < years[index - 1])) {
  console.error('Archive seasons must be ordered newest first.');
  process.exit(1);
}

if (season.ageRange && season.ageRange.min > season.ageRange.max) {
  console.error('ageRange.min must not be greater than ageRange.max.');
  process.exit(1);
}

if (season.startDate && season.endDate && season.startDate > season.endDate) {
  console.error('startDate must not be after endDate.');
  process.exit(1);
}

const hero = 'resources/assets/season/hero.jpg';
try {
  await access(path.join(root, hero));
} catch {
  console.error(`Season hero illustration does not exist: ${hero}`);
  process.exit(1);
}

console.log(`Season ${season.year} (${season.slug}) and ${archive.seasons.length} archive seasons are valid.`);
