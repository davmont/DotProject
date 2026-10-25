// Fill and save the main form of each add/edit page, as one user.
//
//   node forms.js <user> <password> <pages-file> <report.jsonl>
//
// Empty text fields get sample values (numbers for amounts, an address for
// e-mail fields), empty selects their first real option; date fields are left
// alone. The save button is clicked as a user would (validation alerts are
// accepted). A page fails when the result shows an SQL error or PHP message or
// a script error is thrown; the PHP log is checked by the caller.
const fs = require('fs');
const { BASE, launch, loggedIn, readList, bodyErrors } = require('./common');
const [,, user, password, pagesFile, report] = process.argv;

const SAVE = /^(save|submit|add|update|ok|salvar|guardar|enviar|create|store|aceptar)\b/i;

(async () => {
	const browser = await launch();
	const page = await loggedIn(browser, user, password, { width: 1366, height: 900 });
	const dialogs = [], scriptErrors = [];
	page.on('dialog', d => { dialogs.push(d.message().slice(0, 80)); d.accept().catch(() => {}); });
	page.on('pageerror', e => scriptErrors.push(String(e).slice(0, 160)));
	const out = fs.createWriteStream(report);
	let failed = 0, saved = 0;
	for (const url of readList(pagesFile)) {
		dialogs.length = 0; scriptErrors.length = 0;
		let result = '', html = '';
		try {
			await page.waitForLoadState('load').catch(() => {});
			await page.goto(`${BASE}/index.php?${url}`, { waitUntil: 'load', timeout: 30000 })
				.catch(async () => { await page.waitForTimeout(1500); await page.goto(`${BASE}/index.php?${url}`, { waitUntil: 'load', timeout: 30000 }); });
			const button = await page.evaluate(SAVE_SRC => {
				const SAVE = new RegExp(SAVE_SRC, 'i');
				const visible = e => { const s = getComputedStyle(e), r = e.getBoundingClientRect(); return s.display !== 'none' && s.visibility !== 'hidden' && r.width > 0; };
				for (const form of document.forms) {
					if ((form.method || '').toLowerCase() !== 'post' || /frmHeaderSearch|frm_new|searchfilter/i.test(form.name || '')) continue;
					const btn = [...document.querySelectorAll('input[type=submit],input[type=button],button')]
						.find(b => (b.form === form || form.contains(b)) && visible(b) && SAVE.test((b.value || b.textContent || '').trim()));
					if (!btn) continue;
					let n = 0;
					for (const e of form.elements) {
						if (!visible(e) || e.disabled || e.readOnly) continue;
						const name = (e.name || '').toLowerCase();
						if (e.tagName === 'SELECT' && !e.multiple) {
							if ((!e.value || e.value === '0' || e.value === '-1') && e.options.length > 1) { e.selectedIndex = 1; e.dispatchEvent(new Event('change', { bubbles: true })); }
						} else if (e.tagName === 'TEXTAREA' && !e.value) {
							e.value = 'Audit text ' + name;
						} else if (e.tagName === 'INPUT' && /^(text|email|number|tel|url|)$/i.test(e.type) && !e.value && !/date|time|hour|minute/.test(name)) {
							e.value = /mail/.test(name) ? 'audit@example.com'
								: /url|web/.test(name) ? 'https://example.com'
								: /cost|value|price|rate|amount|qty|quant|percent|duration|budget|hours|size|effort|number|phone|km|mile|tax/.test(name) ? '1'
								: `Audit ${name} ${n++}`;
						}
					}
					btn.setAttribute('data-audit-save', '1');
					return (btn.value || btn.textContent || '').trim().slice(0, 20);
				}
				return null;
			}, SAVE.source);
			if (button === null) {
				result = 'no form';
			} else {
				const nav = page.waitForNavigation({ timeout: 8000 }).catch(() => null);
				await page.click('[data-audit-save]', { timeout: 5000 }).catch(e => { result = 'click failed: ' + String(e).split('\n')[0].slice(0, 60); });
				const moved = await nav;
				await page.waitForLoadState('load').catch(() => {});
				await page.waitForTimeout(500);
				result = result || (moved ? `saved (${button})` : `stayed (${button})`);
				if (moved) saved++;
			}
			html = await page.content().catch(async () => { await page.waitForTimeout(1500); return page.content(); });
		} catch (e) {
			result = 'error: ' + String(e).split('\n')[0].slice(0, 100);
		}
		const errors = bodyErrors(html).concat(scriptErrors.map(e => 'script: ' + e));
		if (errors.length || result.startsWith('error')) {
			failed++;
			console.log(`FAIL  ${url}: ${result}; ${errors.join(' | ').slice(0, 300)}`);
		}
		out.write(JSON.stringify({ url, result, to: page.url().replace(BASE, ''), dialogs: [...dialogs], errors }) + '\n');
	}
	out.end();
	await browser.close();
	console.log(`${failed ? 'FAIL' : 'PASS'}  forms ${user}: ${saved} saved, ${failed} with errors`);
	process.exit(failed ? 1 : 0);
})().catch(e => { console.log('FAIL  forms: ' + e); process.exit(1); });
