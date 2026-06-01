import { chromium } from 'playwright';

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage();

const userId = '00fd1b03-96a5-4cf7-a7c6-39ffd7d09d66';
const url = `http://127.0.0.1:8001/${userId}/ats/interviews`;

console.log('Navigating to:', url);
await page.goto(url, { waitUntil: 'networkidle', timeout: 15000 });

const title = await page.title();
const content = await page.content();
const hasCards = content.includes('Total Kandidat') || content.includes('stat');
const hasTable = content.includes('Tes Tulis') || content.includes('nilai');
const hasFilter = content.includes('Semua Posisi') || content.includes('search');

console.log('Title:', title);
console.log('Has stat cards:', hasCards);
console.log('Has results table:', hasTable);
console.log('Has filter:', hasFilter);
console.log('Page loaded OK');

await browser.close();