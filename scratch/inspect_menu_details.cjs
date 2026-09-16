const { chromium } = require('playwright');

(async () => {
    // Let's inspect what happens to the dropdown menu:
    // 1. Is .dropdown-menu being positioned inside or outside?
    // 2. What CSS is applied to .dropdown-menu, .card, overflow, position?
    // 3. Why did it appear in the screenshot?
    const browser = await chromium.launch({ headless: true });
    const page = await browser.newPage();
    await page.goto('http://127.0.0.1:8000/login');
    await page.fill('input[name="email"]', 'prod_uat_admin@example.com');
    await page.fill('input[name="password"]', 'Password123');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/calendar');

    await page.goto('http://127.0.0.1:8000/staff');
    await page.waitForLoadState('networkidle');

    const info = await page.evaluate(() => {
        const catBtn = Array.from(document.querySelectorAll('button')).find(b => b.innerText.includes('Category'));
        const catDropdown = catBtn.closest('.dropdown');
        const catMenu = catDropdown.querySelector('.dropdown-menu');

        // Computed style of dropdown, menu, card, form
        return {
            dropdown: {
                tagName: catDropdown.tagName,
                className: catDropdown.className,
                position: window.getComputedStyle(catDropdown).position,
                overflow: window.getComputedStyle(catDropdown).overflow
            },
            menu: {
                className: catMenu.className,
                display: window.getComputedStyle(catMenu).display,
                position: window.getComputedStyle(catMenu).position,
                zIndex: window.getComputedStyle(catMenu).zIndex,
                top: window.getComputedStyle(catMenu).top,
                left: window.getComputedStyle(catMenu).left,
                transform: window.getComputedStyle(catMenu).transform,
                overflow: window.getComputedStyle(catMenu).overflow,
                maxHeight: window.getComputedStyle(catMenu).maxHeight,
                border: window.getComputedStyle(catMenu).border,
                background: window.getComputedStyle(catMenu).backgroundColor,
                items: Array.from(catMenu.children).map(c => ({ tag: c.tagName, text: c.innerText.trim(), html: c.outerHTML }))
            },
            card: {
                overflow: window.getComputedStyle(catBtn.closest('.card')).overflow,
                position: window.getComputedStyle(catBtn.closest('.card')).position,
                zIndex: window.getComputedStyle(catBtn.closest('.card')).zIndex
            }
        };
    });

    console.log(JSON.stringify(info, null, 2));

    await browser.close();
})();
