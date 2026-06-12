let activeModal = null;

// Fechar com Esc
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape' && activeModal) closeModal();
});

export function openModal({ title = 'Modal', content = '', size = 'md' }) {
  closeModal();

  const modal = document.createElement('div');
  modal.id = 'globalModal';

  Object.assign(modal.style, {
    position:       'fixed',
    inset:          '0',
    zIndex:         '99999',
    display:        'flex',
    alignItems:     'center',
    justifyContent: 'center',
    background:     'rgba(0,0,0,.72)',
    backdropFilter: 'blur(4px)',
    padding:        '16px',
  });

  const maxW = { sm: '480px', md: '640px', lg: '900px', xl: '1100px' };

  modal.innerHTML = `
    <div style="
      width: 100%;
      max-width: ${maxW[size] || maxW.md};
      background: #04091a;
      border: 1px solid rgba(255,255,255,.1);
      border-radius: 32px;
      box-shadow: 0 24px 60px rgba(0,0,0,.6);
      overflow: hidden;
      animation: modalIn .2s ease;
    ">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid rgba(255,255,255,.06)">
        <h2 style="font-size:20px;font-weight:800;margin:0">${title}</h2>
        <button id="closeModalButton" style="
          width:40px;height:40px;border-radius:14px;border:none;
          background:rgba(255,255,255,.06);color:#94a3b8;
          cursor:pointer;display:flex;align-items:center;justify-content:center;
          transition:background .2s;font-size:18px;
        " aria-label="Fechar">✕</button>
      </div>
      <div style="padding:24px;max-height:80vh;overflow-y:auto">${content}</div>
    </div>
  `;

  document.body.appendChild(modal);
  activeModal = modal;

  document.getElementById('closeModalButton').addEventListener('click', closeModal);
  modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
}

export function closeModal() {
  if (activeModal) {
    activeModal.remove();
    activeModal = null;
  }
}
