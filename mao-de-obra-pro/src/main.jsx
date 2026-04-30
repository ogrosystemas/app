import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './App';
import './index.css';

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').then(reg => console.log('SW registrado')).catch(err => console.log('SW erro', err));
  });
}

ReactDOM.createRoot(document.getElementById('root')).render(<React.StrictMode><App /></React.StrictMode>);