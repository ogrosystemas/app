// ============================================================
// app.js — bootstrap, estado global, helpers de UI
// ============================================================

import { openDB, seedIfEmpty, getConfig, getAllConfig, setConfig, getAll } from './db.js';
import { startRouter, route, navigate, beforeRoute } from './router.js';
import { calcularValorMinuto } from './calculadora.js';

// ── Estado global ────────────────────────────────────────────

export const State = {
  config: {},
  profissoesAtivas: [],   // objetos completos das profissões ativas
  ready: false,
};

// ── Toast ────────────────────────────────────────────────────

export function toast(msg, tipo = 'success', duracao = 3000) {
  const container = document.getElementById('toast-container');
  if (!container) return;

  const el = document.createElement('div');
  el.className = `toast align-items-center text-bg-${tipo} border-0 show mb-2`;
  el.setAttribute('role', 'alert');
  el.innerHTML = `
    <div class="d-flex">
      <div class="toast-body fw-semibold">${msg}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>`;
  container.appendChild(el);
  setTimeout(() => el.remove(), duracao);
}

// ── Modal helpers ─────────────────────────────────────────────

export function showModal(id) {
  const el = document.getElementById(id);
  if (!el) return;
  bootstrap.Modal.getOrCreateInstance(el).show();
}

export function hideModal(id) {
  const el = document.getElementById(id);
  if (!el) return;
  bootstrap.Modal.getOrCreateInstance(el).hide();
}

// ── Loader ───────────────────────────────────────────────────

export function showLoader(msg = 'Carregando...') {
  document.getElementById('app-loader-msg').textContent = msg;
  document.getElementById('app-loader').classList.remove('d-none');
}

export function hideLoader() {
  document.getElementById('app-loader').classList.add('d-none');
}

// ── Render helper ────────────────────────────────────────────

export function render(html) {
  document.getElementById('page-content').innerHTML = html;
}

// ── Nav ──────────────────────────────────────────────────────

export function setActiveNav(path) {
  document.querySelectorAll('.nav-link[data-route]').forEach(el => {
    el.classList.toggle('active', el.dataset.route === path);
  });
}

// ── Reload config global ─────────────────────────────────────

export async function reloadConfig() {
  const cfg = await getAllConfig();
  const profissoesAtivasIds = cfg.profissoesAtivas || [];
  const todasProfs = await getAll('profissoes');

  State.config = {
    metaSalarial:      cfg.metaSalarial      || 5000,
    horasTrabalhadas:  cfg.horasTrabalhadas  || 160,
    margemReserva:     cfg.margemReserva     || 0.2,
    taxaDeslocamento:  cfg.taxaDeslocamento  || 50,
    validadePadrao:    cfg.validadePadrao    || 30,
    setupConcluido:    cfg.setupConcluido    || 0,
  };

  State.profissoesAtivas = todasProfs.filter(p =>
    profissoesAtivasIds.includes(p.id)
  );

  // Valor minuto por profissão (lookup rápido)
  State.valorMinutoPorProfissao = {};
  for (const p of State.profissoesAtivas) {
    State.valorMinutoPorProfissao[p.id] = calcularValorMinuto(
      State.config.metaSalarial,
      State.config.horasTrabalhadas,
      p.riscoBase
    );
  }
}

// ── Bootstrap da aplicação ───────────────────────────────────

async function boot() {
  showLoader('Inicializando...');
  try {
    await openDB();
    await seedIfEmpty();
    await reloadConfig();
    State.ready = true;
  } catch (err) {
    console.error('Boot error:', err);
    document.getElementById('app-loader-msg').textContent = 'Erro ao iniciar. Recarregue a página.';
    return;
  }
  hideLoader();

  // Guard de setup
  beforeRoute(async (path, next) => {
    await reloadConfig();
    if (!State.config.setupConcluido && path !== '/setup') {
      navigate('/setup');
      return;
    }
    if (State.config.setupConcluido && path === '/setup') {
      navigate('/');
      return;
    }
    next();
  });

  // Registrar rotas
  const pages = [
    ['/',             () => import('../pages/dashboard.js')],
    ['/clientes',     () => import('../pages/clientes.js')],
    ['/catalogo',     () => import('../pages/catalogo.js')],
    ['/orcamento',    () => import('../pages/orcamento.js')],
    ['/visualizar',   () => import('../pages/visualizar.js')],
    ['/configuracoes',() => import('../pages/configuracoes.js')],
    ['/setup',        () => import('../pages/setup.js')],
    ['*',             () => navigate('/')],
  ];

  for (const [path, loader] of pages) {
    route(path, async (params) => {
      const mod = await loader();
      const isSetup = path === '/setup';
      document.getElementById('main-nav').classList.toggle('d-none', isSetup);
      document.getElementById('fab-novo').classList.toggle('d-none', isSetup || path === '/orcamento');
      setActiveNav(path);
      await mod.default(params);
    });
  }

  // Expõe navigate do router no window para uso nos botões inline do HTML
  window.navigate = navigate;

  startRouter();
}

document.addEventListener('DOMContentLoaded', boot);
