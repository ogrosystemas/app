import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App'
import './index.css'

// Registro do Service Worker com controle de versão e força de atualização
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js')
      .then(registration => {
        console.log('Service Worker registrado com sucesso:', registration);

        // Verificar se há uma nova versão do SW aguardando
        if (registration.waiting) {
          // Envia mensagem para o SW ativar imediatamente
          registration.waiting.postMessage({ type: 'SKIP_WAITING' });
        }

        // Escuta por atualizações
        registration.addEventListener('updatefound', () => {
          const newWorker = registration.installing;
          if (newWorker) {
            newWorker.addEventListener('statechange', () => {
              if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                // Notifica o usuário que uma nova versão está disponível
                if (confirm('Nova versão disponível! Recarregar para atualizar?')) {
                  window.location.reload();
                }
              }
            });
          }
        });
      })
      .catch(error => console.error('Erro ao registrar Service Worker:', error));

    // Garante que a página seja recarregada quando o SW atualizado tomar controle
    navigator.serviceWorker.addEventListener('controllerchange', () => {
      window.location.reload();
    });
  });
}

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);