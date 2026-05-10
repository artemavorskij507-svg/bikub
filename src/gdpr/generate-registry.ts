/*
 * Draft generator for Art.30 register from JSDoc/TSDoc annotations.
 * Week 1 scope: deterministic extraction + JSON output validated by schema.
 */

import * as fs from 'node:fs';
import * as path from 'node:path';

type Activity = {
  activity_id: string;
  system_component: string;
  business_owner: string;
  data_controller: string;
  purposes: string[];
  legal_basis: string[];
  data_subject_categories: string[];
  personal_data_categories: string[];
  special_category_data: boolean;
  recipients: string[];
  third_country_transfer: { enabled: boolean; countries?: string[]; safeguards?: string[] };
  retention_policy: string;
  security_measures: string[];
  source_refs: string[];
  last_reviewed_at: string;
};

const TAGS = [
  'processingActivity',
  'purpose',
  'legalBasis',
  'dataSubjects',
  'dataCategories',
  'retention',
  'security',
] as const;

function extractFromText(content: string, fileRef: string): Activity[] {
  const blocks = content.match(/\/\*\*[\s\S]*?\*\//g) ?? [];
  const activities: Activity[] = [];

  for (const block of blocks) {
    const map = new Map<string, string>();

    for (const tag of TAGS) {
      const m = block.match(new RegExp(`@${tag}\\s+([^\\n\\r*]+)`));
      if (m?.[1]) map.set(tag, m[1].trim());
    }

    if (!map.has('processingActivity')) continue;

    activities.push({
      activity_id: map.get('processingActivity')!,
      system_component: 'bikube',
      business_owner: 'GLF Narvik Operations',
      data_controller: 'GLF Narvik',
      purposes: map.get('purpose') ? [map.get('purpose')!] : ['TBD'],
      legal_basis: (map.get('legalBasis') ?? '').split(',').map(v => v.trim()).filter(Boolean),
      data_subject_categories: (map.get('dataSubjects') ?? '').split(',').map(v => v.trim()).filter(Boolean),
      personal_data_categories: (map.get('dataCategories') ?? '').split(',').map(v => v.trim()).filter(Boolean),
      special_category_data: /care|health|vulnerable/i.test(map.get('dataCategories') ?? ''),
      recipients: [],
      third_country_transfer: { enabled: false },
      retention_policy: map.get('retention') ?? 'TBD',
      security_measures: (map.get('security') ?? '').split(',').map(v => v.trim()).filter(Boolean),
      source_refs: [fileRef],
      last_reviewed_at: new Date().toISOString().slice(0, 10),
    });
  }

  return activities;
}

function walkFiles(root: string, exts = ['.ts', '.js', '.php']): string[] {
  const out: string[] = [];
  if (!fs.existsSync(root)) return out;

  for (const entry of fs.readdirSync(root, { withFileTypes: true })) {
    const full = path.join(root, entry.name);
    if (entry.isDirectory()) out.push(...walkFiles(full, exts));
    else if (exts.includes(path.extname(entry.name).toLowerCase())) out.push(full);
  }

  return out;
}

function main(): void {
  const roots = ['app', 'src'];
  const files = roots.flatMap(r => walkFiles(path.resolve(process.cwd(), r)));

  const activities = files.flatMap(file => {
    const rel = path.relative(process.cwd(), file).replace(/\\/g, '/');
    const txt = fs.readFileSync(file, 'utf8');
    return extractFromText(txt, rel);
  });

  const artifact = {
    generated_at: new Date().toISOString(),
    activities,
  };

  const outputPath = path.resolve(process.cwd(), 'docs/compliance/phase-1/art30.registry.json');
  fs.mkdirSync(path.dirname(outputPath), { recursive: true });
  fs.writeFileSync(outputPath, JSON.stringify(artifact, null, 2));

  console.log(`Generated registry with ${activities.length} activities -> ${outputPath}`);
}

main();
