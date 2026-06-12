export function emptyState({
  icon = 'package',
  title = 'Nenhum registro encontrado',
  description = 'Comece adicionando informações ao sistema.',
  buttonText = '',
  buttonId = ''
}) {
  return `
    <div class="card" style="display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:56px 24px">
      <div style="width:96px;height:96px;border-radius:28px;display:flex;align-items:center;justify-content:center;background:rgba(249,115,22,.1);border:1px solid rgba(249,115,22,.2);margin-bottom:24px">
        <i class="ph ph-${icon}" style="font-size:48px;color:#fb923c"></i>
      </div>
      <h2 style="font-size:24px;font-weight:900;margin-bottom:12px">${title}</h2>
      <p style="color:#94a3b8;max-width:400px;line-height:1.6;margin-bottom:${buttonText ? '32px' : '0'}">${description}</p>
      ${buttonText ? `<button id="${buttonId}" class="primary-button" style="width:auto;display:inline-flex">${buttonText}</button>` : ''}
    </div>
  `;
}
