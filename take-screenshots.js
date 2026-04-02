const { chromium } = require('playwright');

const BASE = 'https://elitedivat.hu/ugyvitel';
const EMAIL = 'vorosmarty@elitedivat.hu';
const PASS = '3lit3Fashion2026!';
const OUT = './public/docs/screenshots';

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({
        viewport: { width: 1440, height: 900 },
        locale: 'hu-HU',
    });
    const page = await context.newPage();

    // Bejelentkezés
    console.log('Bejelentkezés...');
    await page.goto(BASE + '/login', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1000);
    await page.screenshot({ path: OUT + '/login.png' });

    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="password"]', PASS);
    await page.screenshot({ path: OUT + '/login-filled.png' });

    await page.click('button[type="submit"]');
    await page.waitForURL('**/ugyvitel/**', { timeout: 15000 });
    await page.waitForTimeout(2000);

    // Kezdőlap
    console.log('Kezdőlap...');
    await page.screenshot({ path: OUT + '/dashboard.png' });

    // Sidebar közeli kép
    await page.evaluate(() => { document.getElementById('sidebar')?.classList.remove('-translate-x-full'); });
    await page.waitForTimeout(500);
    await page.screenshot({ path: OUT + '/sidebar.png', clip: { x: 0, y: 0, width: 280, height: 900 } });

    // Könyvelés
    console.log('Könyvelés...');
    await page.goto(BASE + '/finance', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.screenshot({ path: OUT + '/finance.png' });

    // Könyvelés - Új tétel form
    await page.goto(BASE + '/finance/create', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.screenshot({ path: OUT + '/finance-create.png' });

    // Fizetések
    console.log('Fizetések...');
    await page.goto(BASE + '/salary', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.screenshot({ path: OUT + '/salary.png' });

    // Fizetés - Új
    await page.goto(BASE + '/salary/create', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.screenshot({ path: OUT + '/salary-create.png' });

    // Értékelések
    console.log('Értékelések...');
    await page.goto(BASE + '/evaluations', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.screenshot({ path: OUT + '/evaluations.png' });

    await page.goto(BASE + '/evaluations/create', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.screenshot({ path: OUT + '/evaluations-create.png' });

    // Szabadság
    console.log('Szabadság...');
    await page.goto(BASE + '/vacation', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.screenshot({ path: OUT + '/vacation.png' });

    await page.goto(BASE + '/vacation/create', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.screenshot({ path: OUT + '/vacation-create.png' });

    // Beosztás
    console.log('Beosztás...');
    await page.goto(BASE + '/schedule', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2000);
    await page.screenshot({ path: OUT + '/schedule.png' });

    // Számlák
    console.log('Számlák...');
    await page.goto(BASE + '/invoices', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.screenshot({ path: OUT + '/invoices.png' });

    // Selejt
    console.log('Selejt...');
    await page.goto(BASE + '/defects', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.screenshot({ path: OUT + '/defects.png' });

    // Selejt - szkenner rész közeli
    try {
        const scannerEl = page.locator('.lg\\:col-span-1').first();
        await scannerEl.waitFor({ timeout: 3000 });
        const scannerBox = await scannerEl.boundingBox();
        if (scannerBox) {
            await page.screenshot({ path: OUT + '/defects-scanner.png', clip: { x: scannerBox.x, y: scannerBox.y, width: scannerBox.width, height: Math.min(scannerBox.height, 900) } });
        }
    } catch(e) { console.log('Scanner clip kihagyva'); }

    // Chat
    console.log('Chat...');
    await page.goto(BASE + '/chat', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2500);
    await page.screenshot({ path: OUT + '/chat.png' });

    // Mobil nézetek
    console.log('Mobil nézetek...');
    await page.setViewportSize({ width: 390, height: 844 });

    await page.goto(BASE + '/', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2000);
    await page.screenshot({ path: OUT + '/mobile-dashboard.png' });

    // Mobil menü
    const hamburger = page.locator('#mobile-menu-btn, [onclick*="sidebar"], button.lg\\:hidden').first();
    try {
        await hamburger.click({ timeout: 3000 });
        await page.waitForTimeout(500);
        await page.screenshot({ path: OUT + '/mobile-menu.png' });
        // Menü bezárás
        await page.click('body', { position: { x: 350, y: 400 } });
        await page.waitForTimeout(300);
    } catch(e) { console.log('Mobil menü gomb nem található, kihagyva'); }

    await page.goto(BASE + '/finance', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.screenshot({ path: OUT + '/mobile-finance.png' });

    await page.goto(BASE + '/defects', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(1500);
    await page.screenshot({ path: OUT + '/mobile-defects.png' });

    await page.goto(BASE + '/chat', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2000);
    await page.screenshot({ path: OUT + '/mobile-chat.png' });

    await page.goto(BASE + '/schedule', { waitUntil: 'domcontentloaded' });
    await page.waitForTimeout(2000);
    await page.screenshot({ path: OUT + '/mobile-schedule.png' });

    await browser.close();
    console.log('Kész! Képernyőképek elmentve: ' + OUT);
})();
