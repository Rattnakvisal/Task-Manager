import { readFile } from 'node:fs/promises';

const config = JSON.parse(await readFile(new URL('../vercel.json', import.meta.url), 'utf8'));
const base = new URL(process.argv[2] || config.env.APP_URL);
const results = [];

async function check(path, expectedStatus, options = {}) {
    const response = await fetch(new URL(path, base), {
        redirect: 'manual',
        signal: AbortSignal.timeout(30000),
        headers: { Accept: 'application/json', ...options.headers },
        ...options,
    });
    const body = await response.text();
    const allowedStatuses = Array.isArray(expectedStatus) ? expectedStatus : [expectedStatus];
    if (!allowedStatuses.includes(response.status)) {
        throw new Error(`${path}: expected ${allowedStatuses.join(' or ')}, received ${response.status}`);
    }
    results.push({ path, status: response.status });
    return { response, body };
}

try {
    const ping = await check('/api/mobile/ping', 200);
    if (JSON.parse(ping.body).status !== 'ok') throw new Error('Mobile health check failed.');
    await check('/api/mobile/me', 401);
    await check('/api/task-alerts', 401);
    const login = await check('/api/mobile/login', 422, {
        method: 'POST',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
        body: '{}',
    });
    const errors = JSON.parse(login.body).errors;
    if (!errors?.email || !errors?.password || !errors?.device_name) {
        throw new Error('Mobile login validation did not run.');
    }
    const page = await check('/sign-in', 200, { headers: { Accept: 'text/html' } });
    if (!page.body.includes('csrf-token')) throw new Error('Website CSRF token is missing.');
    await check('/sign-up', 200, { headers: { Accept: 'text/html' } });
    const assets = new Set([...page.body.matchAll(/(?:href|src)=["']([^"']*\/build\/[^"']+)["']/g)]
        .map((match) => new URL(match[1], base))
        .filter((url) => /\.(?:css|js)$/.test(url.pathname))
        .map((url) => url.pathname));
    if (assets.size < 2) throw new Error('Built CSS and JavaScript links are missing.');
    for (const asset of assets) {
        const result = await check(asset, 200);
        if (result.response.headers.get('content-type')?.includes('text/html')) {
            throw new Error(`${asset}: received HTML instead of a built asset.`);
        }
    }
    await check('/index.php', [403, 404]);
    await check('/hot', 404);
    console.log(JSON.stringify({ origin: base.origin, passed: true, checks: results }, null, 2));
} catch (error) {
    console.error(JSON.stringify({ origin: base.origin, passed: false, checks: results, error: error.message }, null, 2));
    process.exitCode = 1;
}
