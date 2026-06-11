export function showLoading() {

  let loading =
    document.getElementById('loadingOverlay');

  if (loading) return;

  loading =
    document.createElement('div');

  loading.id = 'loadingOverlay';

  loading.innerHTML = `

    <div class="loading-box">

      <div class="spinner"></div>

      <p>
        Processando...
      </p>

    </div>

  `;

  document.body.appendChild(loading);

}

export function hideLoading() {

  const loading =
    document.getElementById('loadingOverlay');

  if (!loading) return;

  loading.remove();

}