import { initRouter } from './modules/router.js';

document.addEventListener(
  'DOMContentLoaded',
  async () => {

    await initRouter();

    // LUCIDE ICONS

    if (window.lucide) {

      lucide.createIcons();

    }

  }
);