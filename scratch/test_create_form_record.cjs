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

    console.log('Navigating to /form-records/create ...');
    await page.goto('http://127.0.0.1:8000/form-records/create');
    await page.waitForLoadState('networkidle');

    console.log('Form records create page loaded. Selecting Form and Client...');
    await page.locator('select[name="form_id"]').selectOption({ index: 0 });
    await page.locator('select[name="client_id"]').selectOption({ index: 0 });
    await page.fill('textarea[name="submitted_data[notes]"]', 'Automated test note submission for form record');

    await page.screenshot({ path: 'C:/Users/Dreamzcoder/.gemini/antigravity-ide/brain/6f71a77a-9ccc-4f57-8e6f-91c68fe0ae81/form_record_before_submit.png' });

    console.log('Submitting form...');
    await page.click('button:has-text("Save record")');
    await page.waitForURL('**/form-records');

    console.log('Post-submit URL:', page.url());

    const hasSuccess = await page.locator('.alert-success').innerText();
    console.log('Success alert message:', hasSuccess);

    await page.screenshot({ path: 'C:/Users/Dreamzcoder/.gemini/antigravity-ide/brain/6f71a77a-9ccc-4f57-8e6f-91c68fe0ae81/form_record_after_submit.png' });

    console.log('Console errors count:', consoleErrors.length);

    await browser.close();
    console.log('Test finished successfully!');
})();
