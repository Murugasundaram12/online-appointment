const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage({ viewport: { width: 1366, height: 768 } });

    await page.goto('http://127.0.0.1:8000/login');
    await page.fill('input[name="email"]', 'prod_uat_admin@example.com');
    await page.fill('input[name="password"]', 'Password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/calendar');

    console.log('Navigating to /form-records/create ...');
    const resp = await page.goto('http://127.0.0.1:8000/form-records/create');
    console.log('Status:', resp.status(), 'URL:', page.url());

    await page.screenshot({ path: 'C:/Users/Dreamzcoder/.gemini/antigravity-ide/brain/6f71a77a-9ccc-4f57-8e6f-91c68fe0ae81/create_page_debug.png' });

    const html = await page.content();
    console.log('HTML snippet:', html.substring(0, 500));

    await browser.close();
})();
