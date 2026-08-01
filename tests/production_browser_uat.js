const { chromium } = require('playwright');
const fs = require('fs');

const fixture = JSON.parse(fs.readFileSync('tests/production_uat_fixture.json', 'utf8'));
const baseUrl = 'http://127.0.0.1:8000';

function assert(condition, message) {
  if (!condition) throw new Error(message);
}

async function login(page, email) {
  await page.goto(`${baseUrl}/login`, { waitUntil: 'domcontentloaded' });
  await page.fill('#email', email);
  await page.fill('#password', fixture.password);
  await Promise.all([
    page.waitForLoadState('networkidle').catch(() => {}),
    page.click('#loginButton'),
  ]);
  assert(!page.url().includes('/login'), `login failed for ${email}`);
}

async function testRole(browser, role, email) {
  const context = await browser.newContext({ viewport: { width: 1366, height: 850 } });
  const page = await context.newPage();
  await login(page, email);

  const commonPaths = ['/', '/calendar', '/clients', '/invoices', '/payment-records', '/schedule'];
  for (const path of commonPaths) {
    const response = await page.goto(`${baseUrl}${path}`, { waitUntil: 'domcontentloaded' });
    assert(response && response.status() === 200, `${role} expected 200 for ${path}, got ${response && response.status()}`);
  }

  const restrictedPaths = ['/staff', '/payroll', '/business-settings', '/subscription'];
  for (const path of restrictedPaths) {
    const response = await page.goto(`${baseUrl}${path}`, { waitUntil: 'domcontentloaded' });
    const expected = ['admin', 'business_owner'].includes(role) ? 200 : 403;
    assert(response && response.status() === expected, `${role} expected ${expected} for ${path}, got ${response && response.status()}`);
  }

  await context.close();
  return `PASS role direct URL access ${role}`;
}

async function selectBookingSlot(page, slotIndex = 0) {
  await page.selectOption('#location_id', String(fixture.location_id));
  await page.selectOption('#service_id', String(fixture.service_id));
  await page.selectOption('#staff_id', String(fixture.staff_id));
  await page.fill('#booking_date', fixture.booking_date);
  await page.dispatchEvent('#booking_date', 'change');
  await page.waitForSelector('#slotButtons button', { timeout: 20000 });
  const buttons = page.locator('#slotButtons button');
  assert(await buttons.count() > slotIndex, `slot ${slotIndex} not available`);
  await buttons.nth(slotIndex).click();
}

async function testOnlineBooking(browser) {
  const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  const page = await context.newPage();
  await page.goto(`${baseUrl}/online-booking`, { waitUntil: 'networkidle' });
  await selectBookingSlot(page, 0);
  const unique = Date.now();
  await page.fill('input[name="client_name"]', `PROD_UAT_Public ${unique}`);
  await page.fill('input[name="client_email"]', 'muruga12062002@gmail.com');
  await page.fill('input[name="client_phone"]', `555${String(unique).slice(-7)}`);
  await Promise.all([
    page.waitForURL(/online-booking\/confirmation\/\d+/, { timeout: 60000 }),
    page.click('button:has-text("Confirm booking")'),
  ]);
  const text = await page.locator('body').innerText();
  assert(text.includes('Your appointment is booked'), 'public booking confirmation missing');
  const match = page.url().match(/confirmation\/(\d+)/);
  assert(match, 'public booking appointment id missing');
  await context.close();
  return `PASS public online booking browser flow appointment ${match[1]}`;
}

async function prepareConcurrentPage(browser, slotIndex, label) {
  const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  const page = await context.newPage();
  await page.goto(`${baseUrl}/online-booking`, { waitUntil: 'networkidle' });
  await selectBookingSlot(page, slotIndex);
  const unique = `${Date.now()}_${label}`;
  await page.fill('input[name="client_name"]', `PROD_UAT_Concurrent ${unique}`);
  await page.fill('input[name="client_email"]', `prod_uat_concurrent_${unique}@example.com`);
  await page.fill('input[name="client_phone"]', `777${String(Date.now()).slice(-7)}`);
  return { context, page };
}

async function submitBooking(page) {
  await page.click('button:has-text("Confirm booking")');
  await page.waitForLoadState('domcontentloaded').catch(() => {});
  await page.waitForTimeout(1500);
  return {
    url: page.url(),
    text: await page.locator('body').innerText(),
  };
}

async function testConcurrentBooking(browser) {
  const first = await prepareConcurrentPage(browser, 0, 'a');
  const second = await prepareConcurrentPage(browser, 0, 'b');
  const [a, b] = await Promise.all([submitBooking(first.page), submitBooking(second.page)]);
  const successes = [a, b].filter(result => /confirmation\/\d+/.test(result.url) && result.text.includes('Your appointment is booked'));
  const failures = [a, b].filter(result => result.text.includes('Selected slot is no longer available') || result.text.includes('Please fix') || result.text.includes('No slots available'));
  await first.context.close();
  await second.context.close();
  assert(successes.length === 1 && failures.length === 1, `concurrent booking expected 1 success/1 failure, got ${successes.length}/${failures.length}`);
  return 'PASS concurrent same-slot booking allows one and rejects one';
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const results = [];
  for (const [role, data] of Object.entries(fixture.roles)) {
    results.push(await testRole(browser, role, data.email));
  }
  results.push(await testOnlineBooking(browser));
  results.push(await testConcurrentBooking(browser));
  await browser.close();
  console.log(results.join('\n'));
})();
