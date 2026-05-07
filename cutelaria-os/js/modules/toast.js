export function showToast(
  message,
  type = 'success'
) {

  const toast =
    document.createElement('div');

  toast.className =
    `toast toast-${type}`;

  toast.innerHTML = `
    <div class="toast-content">

      <span class="toast-icon">

        ${
          type === 'success'
            ? '✅'
            : type === 'error'
              ? '❌'
              : '⚠️'
        }

      </span>

      <span>
        ${message}
      </span>

    </div>
  `;

  document.body.appendChild(toast);

  setTimeout(() => {

    toast.classList.add('show');

  }, 50);

  setTimeout(() => {

    toast.classList.remove('show');

    setTimeout(() => {

      toast.remove();

    }, 400);

  }, 3000);

}