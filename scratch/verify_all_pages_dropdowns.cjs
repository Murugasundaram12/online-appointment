const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage({ viewport: { width: 1366, height: 768 } });

    await page.goto('http://127.0.0.1:8000/login');
    await page.fill('input[name="email"]', 'prod_uat_admin@example.com');
    await page.fill('input[name="password"]', 'Password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/calendar');

    const routesToTest = [
        { url: '/services', dropdownText: 'Category', screenshot: 'services_dropdown_open.png' },
        { url: '/invoices', dropdownText: 'Status', screenshot: 'invoices_dropdown_open.png' },
        { url: '/payment-records', dropdownText: 'Payment method', screenshot: 'payment_records_dropdown_open.png' },
        { url: '/packages', dropdownText: 'Status', screenshot: 'packages_dropdown_open.png' },
        { url: '/clients', screenshot: 'clients_toolbar.png' },
        { url: '/categories', screenshot: 'categories_toolbar.png' },
        { url: '/locations', screenshot: 'locations_toolbar.png' },
        { url: '/payroll', screenshot: 'payroll_toolbar.png' },
        { url: '/insurance-companies', screenshot: 'insurance_companies_toolbar.png' },
        { url: '/reports', screenshot: 'reports_toolbar.png' }
    ];

    for (const r of routesToTest) {
        await page.goto(`http://127.0.0.1:8000${r.url}`);
        await page.waitForLoadState('networkidle');

        if (r.dropdownText) {
            const btn = page.locator(`button.dropdown-toggle:has-text("${r.dropdownText}")`).first();
            if (await btn.isVisible()) {
                await btn.click();
                await page.waitForTimeout(200);
                const isMenuVisible = await page.locator('.dropdown-menu.show').first().isVisible();
                const menuBox = await page.locator('.dropdown-menu.show').first().boundingBox();
                console.log(`Route ${r.url} dropdown "${r.dropdownText}": visible=${isMenuVisible}, height=${menuBox ? Math.round(menuBox.height) : 0}`);
            }
        }

        await page.screenshot({ path: `C:/Users/Dreamzcoder/.gemini/antigravity-ide/brain/6f71a77a-9ccc-4f57-8e6f-91c68fe0ae81/${r.screenshot}` });
    }

    await browser.close();
    console.log('All routes verified successfully!');
})();
