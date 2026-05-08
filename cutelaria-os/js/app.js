import { initRouter } from './core/router.js';
import { initPWA } from './modules/pwa.js';

window.addEventListener(
  'DOMContentLoaded',
  async () => {

    await initRouter();
    initPWA();

  }
);