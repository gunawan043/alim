const { chromium } = require('playwright');
(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  await page.goto('http://127.0.0.1:8001/login');
  await page.waitForLoadState('networkidle');
  await page.screenshot({ path: '/tmp/alim_login.png', fullPage: true });

  const inputs = await page.evaluate(() =>
    Array.from(document.querySelectorAll('input')).map(e => ({ type: e.type, name: e.name, id: e.id, placeholder: e.placeholder }))
  );
  console.log('Inputs:', JSON.stringify(inputs));

  await browser.close();
})().catch(e => console.error('Error:', e.message));
