// ============================================================
// router.js — SPA router baseado em hash (#/rota)
// ============================================================

const routes = {};
let currentRoute = null;
let beforeEach = null;

export function route(path, handler) {
  routes[path] = handler;
}

export function beforeRoute(fn) {
  beforeEach = fn;
}

export function navigate(path, params = {}) {
  const query = Object.keys(params).length
    ? '?' + new URLSearchParams(params).toString()
    : '';
  window.location.hash = path + query;
}

export function getParams() {
  const hash = window.location.hash.slice(1); // remove #
  const [, qs] = hash.split('?');
  if (!qs) return {};
  return Object.fromEntries(new URLSearchParams(qs));
}

export function getCurrentPath() {
  const hash = window.location.hash.slice(1);
  return hash.split('?')[0] || '/';
}

function dispatch() {
  const path = getCurrentPath();
  const handler = routes[path] || routes['*'];
  if (!handler) return;

  if (beforeEach) {
    beforeEach(path, () => {
      currentRoute = path;
      handler(getParams());
    });
  } else {
    currentRoute = path;
    handler(getParams());
  }
}

export function startRouter() {
  window.addEventListener('hashchange', dispatch);
  dispatch(); // rota inicial
}
