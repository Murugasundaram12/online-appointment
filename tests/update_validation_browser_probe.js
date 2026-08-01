const { chromium } = require('playwright');
const fs = require('fs');

const baseUrl = 'http://127.0.0.1:8000';
const fixture = JSON.parse(fs.readFileSync('tests/update_validation_browser_fixture.json', 'utf8'));

function assert(condition, message) {
  if (!condition) throw new Error(message);
}

async function login(page) {
  await page.goto(`${baseUrl}/login`, { waitUntil: 'domcontentloaded' });
  await page.fill('#email', 'john@example.com');
  await page.fill('#password', '12345678');
  await Promise.all([
    page.waitForURL(url => !url.toString().includes('/login'), { timeout: 30000 }),
    page.click('#loginButton'),
  ]);
  assert(!page.url().includes('/login'), 'john@example.com login failed');
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({ viewport: { width: 1366, height: 850 } });
  const results = [];
  await login(page);

  await page.goto(`${baseUrl}/clients/${fixture.clientA}/edit`, { waitUntil: 'networkidle' });
  await page.fill('#name', 'UV_BROWSER_Client_A_Renamed');
  await Promise.all([
    page.waitForURL(/\/clients$/, { timeout: 30000 }),
    page.click('main form button[type="submit"]'),
  ]);
  await page.goto(`${baseUrl}/clients`, { waitUntil: 'networkidle' });
  assert((await page.locator('body').innerText()).includes('UV_BROWSER_Client_A_Renamed'), 'client name-only edit failed');
  results.push('PASS browser client name-only edit same email');

  await page.goto(`${baseUrl}/clients/${fixture.clientA}/edit`, { waitUntil: 'networkidle' });
  await page.fill('#name', 'UV_BROWSER_Client_Duplicate_Attempt');
  await page.fill('#email', 'uv_browser_b@example.com');
  await page.click('main form button[type="submit"]');
  await page.waitForLoadState('networkidle').catch(() => {});
  const clientErrorText = await page.locator('body').innerText();
  assert(clientErrorText.includes('already used by another client') || clientErrorText.includes('email has already been taken'), 'client duplicate email error missing');
  assert(await page.inputValue('#name') === 'UV_BROWSER_Client_Duplicate_Attempt', 'client old name not preserved');
  results.push('PASS browser client duplicate email rejected and old values preserved');

  await page.goto(`${baseUrl}/staff/${fixture.staff}/edit`, { waitUntil: 'networkidle' });
  await page.fill('#name', 'UV_BROWSER_Staff_Renamed');
  await page.fill('#password', '');
  await Promise.all([
    page.waitForURL(/\/staff$/, { timeout: 30000 }),
    page.click('main form button[type="submit"]'),
  ]);
  await page.goto(`${baseUrl}/staff`, { waitUntil: 'networkidle' });
  assert((await page.locator('body').innerText()).includes('UV_BROWSER_Staff_Renamed'), 'staff name-only edit failed');
  results.push('PASS browser staff edit blank password');

  await page.goto(`${baseUrl}/locations/${fixture.location}/edit`, { waitUntil: 'networkidle' });
  await page.fill('#name', 'UV_BROWSER_Main_Edited');
  await Promise.all([
    page.waitForURL(/\/locations$/, { timeout: 30000 }),
    page.click('main form button[type="submit"]'),
  ]);
  await page.goto(`${baseUrl}/locations`, { waitUntil: 'networkidle' });
  const locationRows = await page.locator('body').innerText();
  assert(locationRows.includes('UV_BROWSER_Main_Edited'), 'location edit failed');
  results.push('PASS browser location edit no duplicate route');

  await page.goto(`${baseUrl}/services/${fixture.service}/edit`, { waitUntil: 'networkidle' });
  await page.fill('#name', 'UV_BROWSER_Service_Edited');
  await Promise.all([
    page.waitForURL(/\/services$/, { timeout: 30000 }),
    page.click('main form button[type="submit"]'),
  ]);
  await page.goto(`${baseUrl}/services`, { waitUntil: 'networkidle' });
  assert((await page.locator('body').innerText()).includes('UV_BROWSER_Service_Edited'), 'service edit failed');
  results.push('PASS browser service edit');

  await page.goto(`${baseUrl}/packages/${fixture.package}/edit`, { waitUntil: 'networkidle' });
  await page.fill('#name', 'UV_BROWSER_Package_Edited');
  await Promise.all([
    page.waitForURL(/\/packages$/, { timeout: 30000 }),
    page.click('main form button[type="submit"]'),
  ]);
  await page.goto(`${baseUrl}/packages`, { waitUntil: 'networkidle' });
  assert((await page.locator('body').innerText()).includes('UV_BROWSER_Package_Edited'), 'package edit failed');
  results.push('PASS browser package edit');

  await page.goto(`${baseUrl}/subscription`, { waitUntil: 'networkidle' });
  assert((await page.locator('body').innerText()).includes('Activate or change plan'), 'subscription page missing activate form');
  results.push('PASS browser subscription plan activation form available; no plan edit route exists');

  await browser.close();
  console.log(results.join('\n'));
})();
