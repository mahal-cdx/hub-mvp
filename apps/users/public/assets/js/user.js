document.querySelectorAll('[data-password-toggle]').forEach((button) => {
  button.addEventListener('click', () => {
    const input = document.getElementById(button.getAttribute('aria-controls'));
    if (!input) return;
    const visible = input.type === 'text';
    input.type = visible ? 'password' : 'text';
    button.textContent = visible ? 'Mostrar' : 'Ocultar';
    button.setAttribute('aria-pressed', String(!visible));
  });
});

document.querySelectorAll('[data-withdraw-form]').forEach((form) => {
  const points = form.querySelector('[data-withdraw-points]');
  const output = form.querySelector('[data-withdraw-value]');
  const pointValue = Number(form.dataset.pointValue || 0);
  const update = () => {
    const total = Math.max(0, Number(points?.value || 0)) * pointValue;
    if (output) output.textContent = total.toLocaleString('pt-BR', {style: 'currency', currency: 'BRL'});
  };
  points?.addEventListener('input', update);
  update();
});
