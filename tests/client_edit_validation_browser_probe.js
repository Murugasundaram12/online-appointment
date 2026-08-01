const { chromium } = require('playwright');
const { execFileSync } = require('child_process');

const baseUrl = process.env.APP_URL || 'http://127.0.0.1:8000';
const fixture = JSON.parse(execFileSync('php', ['tests/client_edit_validation_fixture.php'], { encoding: 'utf8' }));

async function login(page) {
  await page.goto(`${baseUrl}/login`, { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="email"]', 'john@example.com');
  await page.fill('input[name="password"]', '12345678');
  await Promise.all([
    page.waitForURL(url => !url.pathname.includes('/login'), { timeout: 15000 }).catch(() => null),
    page.click('button[type="submit"], input[type="submit"]'),
  ]);

  if (page.url().includes('/login')) {
    const bodyText = await page.locator('body').innerText();
    throw new Error(`login failed or stayed on login page: ${bodyText.replace(/\s+/g, ' ').slice(0, 300)}`);
  }
}

async function submitEdit(page, id, values) {
  await page.goto(`${baseUrl}/clients/${id}/edit`, { waitUntil: 'domcontentloaded' });

  for (const [field, value] of Object.entries(values)) {
    await page.fill(`[name="${field}"]`, value);
  }

  await Promise.all([
    page.waitForURL(url => url.pathname !== `/clients/${id}/edit`, { timeout: 15000 }).catch(() => null),
    page.click(`form[action$="/clients/${id}"] button[type="submit"]`),
  ]);
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();

  try {
    await login(page);

    await submitEdit(page, fixture.primary_id, {
      name: 'UAT Client Edit Name Only',
      email: fixture.primary_email,
    });
    if (!page.url().includes('/clients')) {
      throw new Error(`name-only edit did not redirect to clients index: ${page.url()}`);
    }

    await submitEdit(page, fixture.primary_id, {
      name: 'UAT Client Edit Name Only',
      email: fixture.primary_email,
    });
    if (!page.url().includes('/clients')) {
      throw new Error(`unchanged same-email edit did not redirect to clients index: ${page.url()}`);
    }

    await submitEdit(page, fixture.duplicate_a_id, {
      name: 'UAT Client Edit Existing Duplicate Email',
      email: fixture.duplicate_email,
    });
    if (!page.url().includes('/clients')) {
      throw new Error(`existing duplicate-email unchanged edit did not redirect to clients index: ${page.url()}`);
    }

    await submitEdit(page, fixture.primary_id, {
      name: 'UAT Client Edit New Email',
      email: fixture.unused_email,
    });
    if (!page.url().includes('/clients')) {
      throw new Error(`unused-email edit did not redirect to clients index: ${page.url()}`);
    }

    await submitEdit(page, fixture.primary_id, {
      name: 'UAT Client Edit Duplicate Email',
      email: fixture.other_email,
    });

    const bodyText = await page.locator('body').innerText();
    const message = 'This email is already used by another client.';
    const count = bodyText.split(message).length - 1;
    if (count !== 1) {
      throw new Error(`expected duplicate-email message exactly once, found ${count}`);
    }

    const emailValue = await page.inputValue('[name="email"]');
    if (emailValue !== fixture.other_email) {
      throw new Error(`old email value was not preserved after validation failure: ${emailValue}`);
    }

    console.log(JSON.stringify({
      status: 'passed',
      baseUrl,
      primary_id: fixture.primary_id,
      scenarios: [
        'name-only edit with same email',
        'same email unchanged',
        'existing duplicate email unchanged',
        'new unused email',
        'another client email rejected once',
      ],
    }, null, 2));
  } finally {
    await browser.close();
  }
})();
