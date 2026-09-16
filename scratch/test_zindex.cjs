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

    // Apply zIndex: 10 and overflow: visible to toolbar card
    await page.evaluate(() => {
        const toolbar = document.querySelector('.card.border-0.shadow-sm.mb-3');
        toolbar.style.overflow = 'visible';
        toolbar.style.position = 'relative';
        toolbar.style.zIndex = '10';
    });

    const catBtn = page.locator('button.dropdown-toggle:has-text("Category")').first();
    await catBtn.click();
    await page.waitForTimeout(200);
    await page.screenshot({ path: 'C:/Users/Dreamzcoder/.gemini/antigravity-ide/brain/6f71a77a-9ccc-4f57-8e6f-91c68fe0ae81/staff_category_zindex10.png' });

    // Test Access level too
    const accessBtn = page.locator('button.dropdown-toggle:has-text("Access level")').first();
    await accessBtn.click();
    await page.waitForTimeout(200);
    await page.screenshot({ path: 'C:/Users/Dreamzcoder/.gemini/antigravity-ide/brain/6f71a77a-9ccc-4f57-8e6f-91c68fe0ae81/staff_access_level_open.png' });

    // Close dropdowns
    await page.click('body', { position: { x: 10, y: 10 } });
    await page.waitForTimeout(200);
    await page.screenshot({ path: 'C:/Users/Dreamzcoder/.gemini/antigravity-ide/brain/6f71a77a-9ccc-4f57-8e6f-91c68fe0ae81/staff_toolbar_closed_zindex10.png' });

    await browser.close();
})();
