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


document.querySelectorAll('[data-lead-form]').forEach((form) => {
  const fields = [...form.querySelectorAll('[data-completeness-weight]')];
  const input = form.querySelector('[data-reference-input]');
  const pasteZone = form.querySelector('[data-image-paste]');
  const preview = form.querySelector('[data-reference-preview]');
  const value = form.querySelector('[data-completeness-value]');
  const bar = form.querySelector('[data-completeness-bar]');
  const temperature = form.querySelector('[data-temperature-label]');
  const transfer = new DataTransfer();

  const updateCompleteness = () => {
    let total = fields.reduce((sum, field) => sum + (field.value.trim() ? Number(field.dataset.completenessWeight || 0) : 0), 0);
    const hasReferences = form.dataset.existingReferences === '1' || transfer.files.length > 0;
    if (hasReferences) total += 10;
    total = Math.min(100, total);
    const label = total >= 75 ? 'quente' : (total >= 45 ? 'morno' : 'frio');
    if (value) value.textContent = total + '%';
    if (bar) bar.style.width = total + '%';
    if (temperature) temperature.textContent = 'Temperatura calculada: ' + label;
  };

  const renderFiles = () => {
    if (!preview) return;
    preview.replaceChildren();
    [...transfer.files].forEach((file, index) => {
      const card = document.createElement('article');
      card.className = 'reference-preview-card';
      const image = document.createElement('img');
      image.src = URL.createObjectURL(file);
      image.alt = file.name;
      image.addEventListener('load', () => URL.revokeObjectURL(image.src), {once: true});
      const name = document.createElement('span');
      name.textContent = file.name;
      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'reference-remove';
      remove.textContent = 'Remover';
      remove.addEventListener('click', () => {
        const next = new DataTransfer();
        [...transfer.files].forEach((candidate, candidateIndex) => {
          if (candidateIndex !== index) next.items.add(candidate);
        });
        transfer.items.clear();
        [...next.files].forEach((candidate) => transfer.items.add(candidate));
        input.files = transfer.files;
        renderFiles();
        updateCompleteness();
      });
      card.append(image, name, remove);
      preview.append(card);
    });
  };

  const addFiles = (files) => {
    [...files].forEach((file) => {
      if (!file.type.startsWith('image/')) return;
      const duplicate = [...transfer.files].some((current) =>
        current.name === file.name && current.size === file.size && current.lastModified === file.lastModified
      );
      if (!duplicate && transfer.files.length < 12) transfer.items.add(file);
    });
    input.files = transfer.files;
    renderFiles();
    updateCompleteness();
  };

  fields.forEach((field) => field.addEventListener('input', updateCompleteness));
  input?.addEventListener('change', (event) => addFiles(event.target.files));

  pasteZone?.addEventListener('paste', (event) => {
    const images = [...(event.clipboardData?.items || [])]
      .filter((item) => item.kind === 'file' && item.type.startsWith('image/'))
      .map((item, index) => {
        const file = item.getAsFile();
        if (!file) return null;
        return new File([file], 'referencia-colada-' + Date.now() + '-' + index + '.' + (file.type.split('/')[1] || 'png'), {type: file.type});
      })
      .filter(Boolean);
    if (images.length) {
      event.preventDefault();
      addFiles(images);
      pasteZone.classList.add('paste-success');
      setTimeout(() => pasteZone.classList.remove('paste-success'), 1200);
    }
  });

  updateCompleteness();
});

document.querySelectorAll('[data-copy-image]').forEach((button) => {
  button.addEventListener('click', async () => {
    const original = button.textContent;
    try {
      if (!window.ClipboardItem || !navigator.clipboard?.write) {
        throw new Error('Clipboard API indisponível');
      }
      const response = await fetch(button.dataset.copyImage, {credentials: 'same-origin'});
      if (!response.ok) throw new Error('Imagem indisponível');
      const blob = await response.blob();
      await navigator.clipboard.write([new ClipboardItem({[blob.type]: blob})]);
      button.textContent = 'Imagem copiada';
    } catch (error) {
      button.textContent = 'Use baixar';
    } finally {
      setTimeout(() => { button.textContent = original; }, 1800);
    }
  });
});
