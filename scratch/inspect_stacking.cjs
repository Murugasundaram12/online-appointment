const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage({ viewport: { width: 1366, height: 768 } });

    await page.goto('http://127.0.0.1:8000/login');
    await page.fill('input[name="email"]', 'prod_uat_admin@example.com');
    await page.fill('input[name="password"]', 'Password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/calendar');

    await page.goto('http://127.0.0.1:8000/staff');
    await page.waitForLoadState('networkidle');

    const stacking = await page.evaluate(() => {
        const toolbar = document.querySelector('.card.border-0.shadow-sm.mb-3');
        const tableCard = document.querySelector('.card.shadow-sm.border-0.rounded');
        const catBtn = Array.from(document.querySelectorAll('button')).find(b => b.innerText.includes('Category'));
        const menu = catBtn.closest('.dropdown').querySelector('.dropdown-menu');

        return {
            toolbar: {
                zIndex: window.getComputedStyle(toolbar).zIndex,
                position: window.getComputedStyle(toolbar).position,
                overflow: window.getComputedStyle(toolbar).overflow
            },
            tableCard: {
                zIndex: window.getComputedStyle(tableCard).zIndex,
                position: window.getComputedStyle(tableCard).position,
                overflow: window.getComputedStyle(tableCard).overflow
            },
            dropdown: {
                zIndex: window.getComputedStyle(catBtn.closest('.dropdown')).zIndex,
                position: window.getComputedStyle(catBtn.closest('.dropdown')).position
            },
            menu: {
                zIndex: window.getComputedStyle(menu).zIndex,
                position: window.getComputedStyle(menu).position
            }
        };
    });

    console.log(JSON.stringify(stacking, null, 2));

    await browser.close();
})();
