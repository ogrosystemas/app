let toastTimeout = null;

export function showToast(opts) {
  // Aceita tanto showToast('mensagem') quanto showToast({ message, type })
  const message = typeof opts === 'string' ? opts : (opts?.message || 'Operação realizada.');
  const type    = typeof opts === 'object'  ? (opts?.type || 'success') : 'success';

  const existing = document.getElementById('globalToast');
  if (existing) existing.remove();

  const isSuccess = type === 'success';
  const toast = document.createElement('div');
  toast.id = 'globalToast';

  Object.assign(toast.style, {
    position: 'fixed',
    bottom: '100px',
    left: '50%',
    transform: 'translateX(-50%)',
    zIndex: '99999',
    display: 'flex',
    alignItems: 'center',
    gap: '12px',
    padding: '14px 18px',
    borderRadius: '18px',
    minWidth: '260px',
    maxWidth: '90vw',
    boxShadow: '0 8px 32px rgba(0,0,0,.35)',
    backdropFilter: 'blur(16px)',
    border: `1px solid ${isSuccess ? 'rgba(16,185,129,.3)' : 'rgba(239,68,68,.3)'}`,
    background: isSuccess ? 'rgba(5,46,22,.85)' : 'rgba(69,10,10,.85)',
    animation: 'toastIn .25s ease',
  });

  toast.innerHTML = `
    <div style="width:32px;height:32px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:${isSuccess?'rgba(16,185,129,.2)':'rgba(239,68,68,.2)'}">
      <i class="ph ph-${isSuccess?'check':'x'}" style="width:16px;height:16px;color:${isSuccess?'#34d399':'#f87171'}"></i>
    </div>
    <div style="flex:1">
      <strong style="font-size:13px;color:${isSuccess?'#34d399':'#f87171'}">${isSuccess?'Sucesso':'Erro'}</strong>
      <p style="font-size:13px;color:rgba(255,255,255,.85);margin-top:2px">${message}</p>
    </div>
  `;

  document.body.appendChild(toast);

  clearTimeout(toastTimeout);
  toastTimeout = setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(-50%) translateY(12px)';
    toast.style.transition = 'all .25s ease';
    setTimeout(() => toast.remove(), 300);
  }, 2800);
}
