const { chromium } = require('playwright');
const path = require('path');

(async () => {
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1366, height: 768 } });
    const page = await context.newPage();

    // Login
    await page.goto('http://127.0.0.1:8000/login');
    await page.fill('input[name="email"]', 'prod_uat_admin@example.com');
    await page.fill('input[name="password"]', 'Password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/calendar');

    // Go to staff
    await page.goto('http://127.0.0.1:8000/staff');
    await page.waitForLoadState('networkidle');

    // Screenshot of toolbar area and full page
    const screenshotPath = 'C:/Users/Dreamzcoder/.gemini/antigravity-ide/brain/6f71a77a-9ccc-4f57-8e6f-91c68fe0ae81/staff_current_1366x768.png';
    await page.screenshot({ path: screenshotPath, fullPage: false });

    // Inspect elements inside the card containing list-toolbar
    const domInfo = await page.evaluate(() => {
        const toolbarCard = document.querySelector('.card.border-0.shadow-sm.mb-3');
        if (!toolbarCard) return { error: 'Toolbar card not found' };

        const rect = toolbarCard.getBoundingClientRect();
        const allElements = Array.from(toolbarCard.querySelectorAll('*')).map(el => {
            const r = el.getBoundingClientRect();
            const cs = window.getComputedStyle(el);
            return {
                tagName: el.tagName,
                className: el.className,
                id: el.id,
                innerText: (el.innerText || '').trim().substring(0, 50),
                rect: { x: r.x, y: r.y, width: r.width, height: r.height, top: r.top, bottom: r.bottom },
                display: cs.display,
                visibility: cs.visibility,
                opacity: cs.opacity,
                background: cs.backgroundColor,
                position: cs.position,
                border: cs.border,
                boxShadow: cs.boxShadow,
                childrenCount: el.children.length
            };
        });

        // Also check if any element is located directly below Category button
        const categoryBtn = Array.from(toolbarCard.querySelectorAll('button')).find(b => b.innerText.includes('Category'));
        let elementsBelowCategory = [];
        if (categoryBtn) {
            const cr = categoryBtn.getBoundingClientRect();
            // Check elements intersecting or directly under cr.left to cr.right, and cr.bottom to cr.bottom + 100
            elementsBelowCategory = Array.from(document.querySelectorAll('*')).filter(el => {
                if (el === categoryBtn) return false;
                const r = el.getBoundingClientRect();
                return (r.top >= cr.bottom - 5 && r.top <= cr.bottom + 100 && r.left < cr.right && r.right > cr.left && r.width > 10 && r.height > 5);
            }).map(el => {
                const cs = window.getComputedStyle(el);
                return {
                    tagName: el.tagName,
                    className: el.className,
                    id: el.id,
                    innerHTML: el.innerHTML.substring(0, 100),
                    rect: el.getBoundingClientRect(),
                    display: cs.display,
                    visibility: cs.visibility,
                    background: cs.backgroundColor,
                    boxShadow: cs.boxShadow
                };
            });
        }

        return {
            toolbarRect: rect,
            allElementsCount: allElements.length,
            elementsBelowCategory,
            // also look for any dropdown-menu
            dropdownMenus: Array.from(toolbarCard.querySelectorAll('.dropdown-menu')).map(dm => {
                const r = dm.getBoundingClientRect();
                const cs = window.getComputedStyle(dm);
                return {
                    className: dm.className,
                    rect: r,
                    display: cs.display,
                    visibility: cs.visibility,
                    opacity: cs.opacity,
                    itemsCount: dm.querySelectorAll('li, a').length,
                    innerHTML: dm.innerHTML.substring(0, 100)
                };
            })
        };
    });

    console.log(JSON.stringify(domInfo, null, 2));

    await browser.close();
})();
