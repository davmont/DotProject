// Shared helpers for the browser checks (playwright-core + an installed Chrome).
const { chromium } = require('playwright-core');
const fs = require('fs');

const BASE = process.env.BASE || 'http://127.0.0.1:8089';
const CHROME = process.env.CHROME || ['/usr/bin/google-chrome', '/usr/bin/google-chrome-stable', '/usr/bin/chromium']
	.find(p => fs.existsSync(p));

async function launch() {
	if (!CHROME) throw new Error('No Chrome found: set CHROME to the browser executable');
	return chromium.launch({ executablePath: CHROME });
}

// A logged-in page. Login is by Enter: some themes hide the submit button.
async function loggedIn(browser, user, password, viewport = { width: 1366, height: 768 }) {
	const page = await (await browser.newContext({ viewport })).newPage();
	await page.goto(BASE + '/index.php');
	await page.fill('input[name=username]', user);
	await page.fill('input[name=password]', password);
	await Promise.all([page.waitForNavigation().catch(() => {}), page.press('input[name=password]', 'Enter')]);
	if (!/logout/i.test(await page.content())) throw new Error(`login as ${user} failed`);
	return page;
}

function readList(file) {
	return fs.readFileSync(file, 'utf8').split('\n').map(s => s.trim()).filter(s => s && !s.startsWith('#'));
}

// SQL errors and PHP messages shown in a page.
function bodyErrors(html) {
	const out = new Set();
	for (const m of html.matchAll(/error was: ([^\n<]{0,120})|Error executing: ([^\n<]{0,120})|<b>(Fatal error|Warning|Notice|Deprecated)<\/b>:\s*([^\n<]{0,120})/g)) {
		out.add((m[1] || m[2] || (m[3] + ': ' + m[4])).trim());
	}
	return [...out];
}

module.exports = { BASE, launch, loggedIn, readList, bodyErrors };
