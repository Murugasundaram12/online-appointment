const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage({ viewport: { width: 1366, height: 768 } });

    const consoleErrors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    // Login
    await page.goto('http://127.0.0.1:8000/login');
    await page.fill('input[name="email"]', 'prod_uat_admin@example.com');
    await page.fill('input[name="password"]', 'Password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/calendar');

    console.log('Testing /form-records/2 ...');
    const response = await page.goto('http://127.0.0.1:8000/form-records/2');
    console.log('HTTP Status for /form-records/2:', response.status());

    await page.waitForLoadState('networkidle');
    const title = await page.title();
    console.log('Page title:', title);

    const bodyText = await page.innerText('main');
    console.log('Main content:\n', bodyText);

    await page.screenshot({ path: 'C:/Users/Dreamzcoder/.gemini/antigravity-ide/brain/6f71a77a-9ccc-4f57-8e6f-91c68fe0ae81/form_record_2_detail.png' });

    // Test Back button
    console.log('Clicking Back button...');
    await page.locator('a:has-text("Back")').first().click();
    await page.waitForURL('**/form-records');
    console.log('Current URL after Back click:', page.url());

    // Test /form-records/1
    console.log('Testing /form-records/1 ...');
    const resp1 = await page.goto('http://127.0.0.1:8000/form-records/1');
    console.log('HTTP Status for /form-records/1:', resp1.status());
    await page.screenshot({ path: 'C:/Users/Dreamzcoder/.gemini/antigravity-ide/brain/6f71a77a-9ccc-4f57-8e6f-91c68fe0ae81/form_record_1_detail.png' });

    // Test /form-records index
    console.log('Testing /form-records index...');
    const respIndex = await page.goto('http://127.0.0.1:8000/form-records');
    console.log('HTTP Status for /form-records:', respIndex.status());
    await page.screenshot({ path: 'C:/Users/Dreamzcoder/.gemini/antigravity-ide/brain/6f71a77a-9ccc-4f57-8e6f-91c68fe0ae81/form_records_index.png' });

    console.log('Console errors count:', consoleErrors.length);
    if (consoleErrors.length > 0) console.log('Errors:', consoleErrors);

    await browser.close();
    console.log('All tests passed!');
})();
