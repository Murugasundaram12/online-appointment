const { chromium } = require('playwright');

const baseUrl = 'http://127.0.0.1:8000';
const credentials = [
  { email: 'john@example.com', password: '12345678' },
  { email: 'john@example.com', password: 'password' },
  { email: 'admin@example.com', password: '12345678' },
  { email: 'admin@example.com', password: 'Admin@123' },
];

function assert(condition, message) {
  if (!condition) throw new Error(message);
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
  const results = [];

  page.on('pageerror', err => results.push(`PAGEERROR ${err.message}`));
  page.on('console', msg => {
    if (msg.type() === 'error') results.push(`CONSOLE ${msg.text()}`);
  });

  let loggedIn = false;
  for (const cred of credentials) {
    await page.goto(`${baseUrl}/login`, { waitUntil: 'domcontentloaded' });
    await page.fill('#email', cred.email);
    await page.fill('#password', cred.password);
    await Promise.all([
      page.waitForLoadState('networkidle').catch(() => {}),
      page.click('#loginButton'),
    ]);
    if (!page.url().includes('/login')) {
      results.push(`PASS login ${cred.email}`);
      loggedIn = true;
      break;
    }
  }
  assert(loggedIn, 'Unable to log in with seeded staff credentials');

  for (const path of ['/', '/clients', '/calendar', '/services', '/invoices', '/payment-records', '/schedule', '/payroll']) {
    await page.goto(`${baseUrl}${path}`, { waitUntil: 'networkidle' });
    assert(!page.url().includes('/login'), `${path} redirected to login`);
    assert(await page.locator('.sidebar-link').count() > 0, `${path} missing sidebar links`);
    results.push(`PASS page ${path}`);
  }

  await page.goto(`${baseUrl}/clients`, { waitUntil: 'networkidle' });
  const firstClientName = (await page.locator('tbody tr td .fw-medium').first().innerText()).trim();
  const compactName = firstClientName.replace(/\s+/g, '');
  assert(compactName.length % 2 !== 0 || compactName.slice(0, compactName.length / 2) !== compactName.slice(compactName.length / 2), `duplicate client name text: ${firstClientName}`);
  results.push(`PASS client name not duplicated: ${firstClientName}`);

  const sidebarLinkCount = Math.min(await page.locator('.sidebar-link').count(), 6);
  for (let i = 0; i < sidebarLinkCount; i++) {
    await page.goto(`${baseUrl}/clients`, { waitUntil: 'networkidle' });
    const link = page.locator('.sidebar-link').nth(i);
    const text = (await link.innerText()).trim();
    const href = await link.getAttribute('href');
    await link.click();
    await page.waitForLoadState('domcontentloaded');
    assert(href && page.url().includes(href.replace(baseUrl, '').replace(/^\//, '')), `sidebar click failed for ${text}`);
  }
  results.push('PASS desktop sidebar navigation clicks');

  await page.goto(`${baseUrl}/clients`, { waitUntil: 'networkidle' });
  const unique = Date.now();
  await page.click('button[data-bs-target="#addClientModal"]');
  await page.fill('#addClientForm input[name="name"]', `UAT Date ${unique}`);
  await page.fill('#addClientForm input[name="email"]', `uat_date_${unique}@example.com`);
  await page.fill('#addClientForm input[name="client_since"]', '2026-08-01');
  await Promise.all([
    page.waitForLoadState('networkidle').catch(() => {}),
    page.click('button[form="addClientForm"]'),
  ]);
  await page.waitForTimeout(1200);
  const bodyText = await page.locator('body').innerText();
  assert(bodyText.includes(`UAT Date ${unique}`), 'created client missing from clients table');
  assert(bodyText.includes('Aug 01, 2026'), 'client_since did not display as Aug 01, 2026');
  assert(await page.locator('.alert:visible').count() === 0, 'visible Bootstrap alert duplicated notification');
  assert(await page.locator('.toast.show').count() <= 1, 'duplicate visible toasts');
  results.push('PASS client date save and single toast');

  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto(`${baseUrl}/clients`, { waitUntil: 'networkidle' });
  await page.click('[data-sidebar-toggle]');
  assert(await page.locator('body.sidebar-open').count() === 1, 'mobile sidebar did not open');
  await page.click('.sidebar-link', { trial: false });
  await page.waitForLoadState('domcontentloaded');
  results.push('PASS mobile sidebar opens and link is clickable');

  await browser.close();
  console.log(results.join('\n'));
})();
