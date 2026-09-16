const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1366, height: 768 } });
    const page = await context.newPage();

    // Login
    await page.goto('http://127.0.0.1:8000/login');
    console.log('Login page URL:', page.url());
    await page.fill('input[name="email"]', 'udhayakumarN@gmail.com');
    await page.fill('input[name="password"]', '12345678');
    await page.click('button[type="submit"]');
    await page.waitForLoadState('networkidle');
    console.log('Post-login URL:', page.url());

    // Go to staff
    await page.goto('http://127.0.0.1:8000/staff');
    await page.waitForLoadState('networkidle');
    console.log('Staff page URL:', page.url());

    const htmlSnippet = await page.evaluate(() => {
        return {
            title: document.title,
            bodyClass: document.body.className,
            cards: Array.from(document.querySelectorAll('.card')).map(c => ({
                className: c.className,
                text: c.innerText.substring(0, 50)
            })),
            buttons: Array.from(document.querySelectorAll('button')).map(b => b.innerText.trim())
        };
    });
    console.log(JSON.stringify(htmlSnippet, null, 2));

    await browser.close();
})();
