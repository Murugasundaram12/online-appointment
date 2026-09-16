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

    // Before fix screenshot
    const catBtn = page.locator('button.dropdown-toggle:has-text("Category")').first();
    await catBtn.click();
    await page.waitForTimeout(200);
    await page.screenshot({ path: 'C:/Users/Dreamzcoder/.gemini/antigravity-ide/brain/6f71a77a-9ccc-4f57-8e6f-91c68fe0ae81/staff_category_before_fix.png' });

    // Apply overflow: visible to toolbar card
    await page.evaluate(() => {
        const catBtn = Array.from(document.querySelectorAll('button')).find(b => b.innerText.includes('Category'));
        const card = catBtn.closest('.card');
        card.style.overflow = 'visible';
    });
    await page.waitForTimeout(200);
    await page.screenshot({ path: 'C:/Users/Dreamzcoder/.gemini/antigravity-ide/brain/6f71a77a-9ccc-4f57-8e6f-91c68fe0ae81/staff_category_after_overflow_visible.png' });

    // Also test closing the dropdown
    await page.click('body', { position: { x: 10, y: 10 } });
    await page.waitForTimeout(200);
    await page.screenshot({ path: 'C:/Users/Dreamzcoder/.gemini/antigravity-ide/brain/6f71a77a-9ccc-4f57-8e6f-91c68fe0ae81/staff_category_closed.png' });

    await browser.close();
})();
