const fs = require('fs');

// Let's write a script that opens the page at 1920x945 (the exact viewport of the user's active browser!)
// and inspects the exact pixels and DOM at 1920x945 and 1366x768.

const { chromium } = require('playwright');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1920, height: 945 } });
    const page = await context.newPage();

    await page.goto('http://127.0.0.1:8000/login');
    await page.fill('input[name="email"]', 'prod_uat_admin@example.com');
    await page.fill('input[name="password"]', 'Password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/calendar');

    await page.goto('http://127.0.0.1:8000/staff');
    await page.waitForLoadState('networkidle');

    // Screenshot of Category button and surrounding 200px
    const categoryBtn = page.locator('button.dropdown-toggle:has-text("Category")').first();
    const box = await categoryBtn.boundingBox();
    console.log('Category button bounding box at 1920x945:', box);

    // Take a screenshot of the toolbar
    const toolbar = page.locator('.card.border-0.shadow-sm.mb-3').first();
    await toolbar.screenshot({ path: 'C:/Users/Dreamzcoder/.gemini/antigravity-ide/brain/6f71a77a-9ccc-4f57-8e6f-91c68fe0ae81/toolbar_1920.png' });

    // Now let's test clicking on Category button
    console.log('Clicking Category button...');
    await categoryBtn.click();
    await page.waitForTimeout(300);

    const dropdownMenu = page.locator('.dropdown-menu.show').first();
    const dmBox = await dropdownMenu.boundingBox();
    console.log('Category dropdown menu bounding box when opened:', dmBox);

    await toolbar.screenshot({ path: 'C:/Users/Dreamzcoder/.gemini/antigravity-ide/brain/6f71a77a-9ccc-4f57-8e6f-91c68fe0ae81/toolbar_category_open.png' });

    await browser.close();
})();
