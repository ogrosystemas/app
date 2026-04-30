import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './App';
import './index.css';

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js')
      .then(registration => {
        console.log('SW registrado');
        if (registration.waiting) registration.waiting.postMessage({ type: 'SKIP_WAITING' });
        registration.addEventListener('updatefound', () => {
          const newWorker = registration.installing;
          newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
              window.location.reload();
            }
          });
        });
      })
      .catch(err => console.error('SW erro:', err));
    navigator.serviceWorker.addEventListener('controllerchange', () => window.location.reload());
  });
}

ReactDOM.createRoot(document.getElementById('root')).render(<App />);