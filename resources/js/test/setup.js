import '@testing-library/jest-dom/vitest';

// `route()` (Ziggy) được inject toàn cục qua <script> trong layout Blade thật,
// không tồn tại trong môi trường test — stub tối giản để component render được.
globalThis.route = (name) => `/${String(name).replace(/\./g, '/')}`;
