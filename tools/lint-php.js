#!/usr/bin/env node
/* Lint PHP syntax of all plugin/theme PHP files using php-parser (no PHP needed). */
const fs = require('fs');
const path = require('path');
const Parser = require('php-parser');

const engine = new Parser({
  parser: { extractDoc: true, suppressErrors: false },
  ast: { withPositions: false },
});

const roots = process.argv.slice(2).filter(Boolean);
if (!roots.length) roots.push('gmx-market', 'gmx-theme');

function walk(dir, out) {
  if (!fs.existsSync(dir)) return;
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, entry.name);
    if (entry.isDirectory()) walk(p, out);
    else if (entry.name.endsWith('.php')) out.push(p);
  }
  return out;
}

let failed = false;
const files = roots.flatMap((r) => walk(r, []) || []).filter(Boolean);
for (const file of files) {
  const code = fs.readFileSync(file, 'utf8');
  try {
    engine.parseCode(code, file);
    console.log('OK  ', file);
  } catch (e) {
    failed = true;
    console.log('FAIL', file);
    console.log('     ', e.message.split('\n')[0]);
  }
}
if (!files.length) console.log('(no PHP files found)');
process.exit(failed ? 1 : 0);
