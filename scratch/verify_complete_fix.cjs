const { chromium } = require('playwright');
const fs = require('fs');

(async () => {
    const browser = await chromium.launch({ headless: true });
    
    // Test 1366x768 in-depth
    console.log('--- Testing 1366x768 Staff Page In-Depth ---');
    const page = await browser.newPage({ viewport: { width: 1366, height: 768 } });
    
    const consoleErrors = [];
    page.on('console', msg => {
        if (msg.type() === 'error') consoleErrors.push(msg.text());
    });

    await page.goto('http://127.0.0.1:8000/login');
    await page.fill('input[name="email"]', 'prod_uat_admin@example.com');
    await page.fill('input[name="password"]', 'Password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/calendar');

    await page.goto('http://127.0.0.1:8000/staff');
    await page.waitForLoadState('networkidle');

    // 1. Check toolbar and Category button closed state
    const toolbar = page.locator('.list-toolbar-card').first();
    const catBtn = page.locator('button.dropdown-toggle:has-text("Category")').first();
    const accessBtn = page.locator('button.dropdown-toggle:has-text("Access level")').first();

    await page.screenshot({ path: 'C:/Users/Dreamzcoder/.gemini/antigravity-ide/brain/6f71a77a-9ccc-4f57-8e6f-91c68fe0ae81/staff_1366_closed.png' });

    // Inspect elements under Category button in closed state
    const closedCheck = await page.evaluate(() => {
        const catBtn = Array.from(document.querySelectorAll('button')).find(b => b.innerText.includes('Category'));
        const cr = catBtn.getBoundingClientRect();
        // Check for any rogue white/visible element directly underneath Category (y: cr.bottom to cr.bottom + 50)
        const elementsUnder = Array.from(document.querySelectorAll('*')).filter(el => {
            if (el === catBtn || el.contains(catBtn) || catBtn.contains(el)) return false;
            const r = el.getBoundingClientRect();
            // within Category's horizontal span, and between cr.bottom and cr.bottom + 40
            return (r.left < cr.right && r.right > cr.left && r.top >= cr.bottom && r.top < cr.bottom + 40 && r.height > 2 && r.width > 10);
        }).map(el => ({
            tag: el.tagName,
            class: el.className,
            rect: el.getBoundingClientRect(),
            display: window.getComputedStyle(el).display,
            visibility: window.getComputedStyle(el).visibility,
            bg: window.getComputedStyle(el).backgroundColor,
            html: el.innerHTML.substring(0, 50)
        }));
        return elementsUnder;
    });
    console.log('Elements directly under Category when closed (should be none from toolbar):', JSON.stringify(closedCheck, null, 2));

    // 2. Open Category dropdown
    console.log('Opening Category dropdown...');
    await catBtn.click();
    await page.waitForTimeout(300);

    const catMenu = page.locator('.dropdown-menu.show').first();
    const catMenuBox = await catMenu.boundingBox();
    console.log('Category menu bounding box:', catMenuBox);
    await page.screenshot({ path: 'C:/Users/Dreamzcoder/.gemini/antigravity-ide/brain/6f71a77a-9ccc-4f57-8e6f-91c68fe0ae81/staff_1366_category_open.png' });

    // Verify Category menu items are visible and not clipped
    const isDoctorVisible = await page.locator('.dropdown-menu.show a:has-text("Doctor")').isVisible();
    const isReceptionistVisible = await page.locator('.dropdown-menu.show a:has-text("Receptionist")').isVisible();
    console.log('Category items visible: Doctor =', isDoctorVisible, ', Receptionist =', isReceptionistVisible);

    // 3. Select Category "Doctor"
    console.log('Selecting Category Doctor...');
    await page.locator('.dropdown-menu.show a:has-text("Doctor")').click();
    await page.waitForURL('**category=Doctor**');
    console.log('Filtered URL:', page.url());
    await page.screenshot({ path: 'C:/Users/Dreamzcoder/.gemini/antigravity-ide/brain/6f71a77a-9ccc-4f57-8e6f-91c68fe0ae81/staff_1366_filtered_doctor.png' });

    // 4. Test Clear button
    const clearBtn = page.locator('a:has-text("Clear")').first();
    console.log('Clear button visible:', await clearBtn.isVisible());
    await clearBtn.click();
    await page.waitForURL('http://127.0.0.1:8000/staff?**');
    console.log('After Clear URL:', page.url());

    // 5. Open Access Level dropdown
    console.log('Opening Access Level dropdown...');
    await page.locator('button.dropdown-toggle:has-text("Access level")').first().click();
    await page.waitForTimeout(300);
    const accessMenu = page.locator('.dropdown-menu.show').first();
    const accessMenuBox = await accessMenu.boundingBox();
    console.log('Access level menu bounding box:', accessMenuBox);
    await page.screenshot({ path: 'C:/Users/Dreamzcoder/.gemini/antigravity-ide/brain/6f71a77a-9ccc-4f57-8e6f-91c68fe0ae81/staff_1366_access_open.png' });

    // Close dropdown
    await page.click('body', { position: { x: 50, y: 50 } });
    await page.waitForTimeout(200);

    // 6. Test Search input
    console.log('Testing Search input...');
    const searchInput = page.locator('.list-toolbar-card input[name="search"]').first();
    await searchInput.fill('Admin');
    await searchInput.press('Enter');
    await page.waitForURL('**/staff?**search=Admin**');
    console.log('Search URL:', page.url());
    await page.screenshot({ path: 'C:/Users/Dreamzcoder/.gemini/antigravity-ide/brain/6f71a77a-9ccc-4f57-8e6f-91c68fe0ae81/staff_1366_search_admin.png' });

    // Clear search
    await page.locator('.list-toolbar-card a:has-text("Clear")').first().click();
    await page.waitForURL('http://127.0.0.1:8000/staff?**');

    // 7. Test Add Staff button modal
    console.log('Testing Add Staff modal...');
    await page.locator('button:has-text("Add Staff")').first().click();
    await page.waitForTimeout(300);
    const modalVisible = await page.locator('#addStaffModal').isVisible();
    console.log('Add Staff modal visible:', modalVisible);
    await page.screenshot({ path: 'C:/Users/Dreamzcoder/.gemini/antigravity-ide/brain/6f71a77a-9ccc-4f57-8e6f-91c68fe0ae81/staff_1366_modal_open.png' });
    // Close modal
    await page.locator('#addStaffModal .btn-close, #addStaffModal button:has-text("Cancel")').first().click();
    await page.waitForTimeout(300);

    await page.close();

    // 8. Test all viewports
    const viewports = [
        { w: 1920, h: 1080, name: '1920x1080' },
        { w: 1366, h: 768, name: '1366x768' },
        { w: 1280, h: 720, name: '1280x720' },
        { w: 1150, h: 720, name: '1150x720' },
        { w: 768, h: 1024, name: '768x1024' },
        { w: 375, h: 667, name: '375x667' },
    ];

    console.log('\n--- Testing All Required Viewports ---');
    for (const vp of viewports) {
        const vpPage = await browser.newPage({ viewport: { width: vp.w, height: vp.h } });
        await vpPage.goto('http://127.0.0.1:8000/staff');
        await vpPage.waitForLoadState('networkidle');

        const metrics = await vpPage.evaluate(() => {
            const toolbar = document.querySelector('.list-toolbar-card');
            const tbBox = toolbar ? toolbar.getBoundingClientRect() : null;
            return {
                tbBox,
                scrollWidth: document.documentElement.scrollWidth,
                clientWidth: document.documentElement.clientWidth,
                hasHScroll: document.documentElement.scrollWidth > document.documentElement.clientWidth
            };
        });

        console.log(`Viewport ${vp.name}: Toolbar Height = ${metrics.tbBox ? Math.round(metrics.tbBox.height) : 'N/A'}px, Has HScroll = ${metrics.hasHScroll}`);
        await vpPage.screenshot({ path: `C:/Users/Dreamzcoder/.gemini/antigravity-ide/brain/6f71a77a-9ccc-4f57-8e6f-91c68fe0ae81/staff_vp_${vp.name}.png` });
        await vpPage.close();
    }

    console.log('\n--- Console Errors Count:', consoleErrors.length);

    await browser.close();
    console.log('Complete verification script finished successfully!');
})();
