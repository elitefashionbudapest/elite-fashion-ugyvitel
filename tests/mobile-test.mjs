import { chromium } from 'playwright';

const BASE = 'http://localhost:8000';
const SCREENSHOT_DIR = './tests/screenshots/';

const pages = [
    { name: '01-login', url: '/login' },
    { name: '02-dashboard', url: '/' },
    { name: '03-finance', url: '/finance' },
    { name: '04-finance-create', url: '/finance/create' },
    { name: '05-salary', url: '/salary' },
    { name: '06-evaluations', url: '/evaluations' },
    { name: '07-vacation', url: '/vacation' },
    { name: '08-schedule', url: '/schedule' },
    { name: '09-invoices', url: '/invoices' },
    { name: '10-defects', url: '/defects' },
    { name: '11-banks', url: '/banks' },
    { name: '12-bank-transactions', url: '/bank-transactions' },
    { name: '13-summary', url: '/finance/summary' },
    { name: '14-settings', url: '/settings/permissions' },
    { name: '15-accounting', url: '/accounting' },
    { name: '16-backup', url: '/backup' },
];

(async () => {
    const browser = await chromium.launch();

    const context = await browser.newContext({
        viewport: { width: 375, height: 812 },
        deviceScaleFactor: 3,
        isMobile: true,
        userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X)',
    });

    const page = await context.newPage();
    const overflowPages = [];

    // Login
    console.log('Bejelentkezés...');
    await page.goto(BASE + '/login', { waitUntil: 'networkidle', timeout: 10000 });
    await page.screenshot({ path: SCREENSHOT_DIR + '01-login.png', fullPage: true });

    await page.fill('input[name="email"]', 'adam@elitedivat.hu');
    await page.fill('input[name="password"]', 'aQW4xy&4*W9r');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);

    if (page.url().includes('/login')) {
        console.log('❌ Login sikertelen! URL:', page.url());
        await browser.close();
        return;
    }

    console.log('✅ Bejelentkezve, URL:', page.url());

    for (const p of pages) {
        if (p.url === '/login') continue;
        try {
            console.log(`${p.name} (${p.url})`);
            await page.goto(BASE + p.url, { waitUntil: 'networkidle', timeout: 15000 });
            await page.waitForTimeout(500);

            if (page.url().includes('/login')) {
                console.log(`  ⚠ Visszadobott loginra!`);
                continue;
            }

            await page.screenshot({ path: SCREENSHOT_DIR + p.name + '.png', fullPage: true });

            const overflow = await page.evaluate(() => {
                const sw = document.documentElement.scrollWidth;
                const cw = document.documentElement.clientWidth;
                return { hasOverflow: sw > cw, scrollW: sw, clientW: cw, diff: sw - cw };
            });

            if (overflow.hasOverflow) {
                console.log(`  ❌ KILÓG ${overflow.diff}px (${overflow.scrollW} > ${overflow.clientW})`);
                overflowPages.push({ ...p, ...overflow });
            } else {
                console.log(`  ✅ OK`);
            }
        } catch (e) {
            console.log(`  ❌ HIBA: ${e.message.substring(0, 80)}`);
        }
    }

    await browser.close();

    console.log('\n========== ÖSSZESÍTÉS ==========');
    if (overflowPages.length === 0) {
        console.log('✅ Minden oldal rendben, nincs kilógás!');
    } else {
        console.log(`❌ ${overflowPages.length} oldal lóg ki:`);
        overflowPages.forEach(p => console.log(`  - ${p.name}: ${p.diff}px`));
    }
    console.log('Screenshotok:', SCREENSHOT_DIR);
})();
