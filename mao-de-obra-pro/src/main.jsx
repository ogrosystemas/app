import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './App';
import './index.css';

const VERSION_URL = '/version.json';
let currentVersion = null;

// Verifica versão a cada 30 segundos
const checkVersion = async () => {
  try {
    const res = await fetch(VERSION_URL, { cache: 'no-cache' });
    const data = await res.json();
    if (!currentVersion) {
      currentVersion = data.version;
    } else if (currentVersion !== data.version) {
      console.log('Nova versão detectada! Recarregando...');
      if (data.forceReload) {
        window.location.reload(true);
      }
    }
  } catch (e) {
    console.warn('Erro ao verificar versão:', e);
  }
};

// Registrar Service Worker normalmente (não interfere nos dados)
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js')
      .then(reg => console.log('SW registrado'))
      .catch(err => console.log('Erro SW:', err));
  });
}

// Verificar versão a cada 30 segundos
setInterval(checkVersion, 30000);
checkVersion(); // primeira verificação imediata

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);