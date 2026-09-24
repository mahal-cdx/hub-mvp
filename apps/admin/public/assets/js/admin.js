document.querySelector('[data-password-toggle]')?.addEventListener('click', (event) => {
  const button = event.currentTarget;
  const input = document.querySelector('#password');
  if (!(input instanceof HTMLInputElement) || !(button instanceof HTMLButtonElement)) return;

  const visible = input.type === 'text';
  input.type = visible ? 'password' : 'text';
  button.textContent = visible ? 'Mostrar' : 'Ocultar';
  button.setAttribute('aria-pressed', String(!visible));
  input.focus();
});
