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
  const MAX_FILES = 10;
  const MAX_BYTES = 25 * 1024 * 1024;
  const ALLOWED_TYPES = new Set(['image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png', 'image/apng', 'image/webp', 'image/gif']);
  const fields = [...form.querySelectorAll('[data-completeness-weight]')];
  const input = form.querySelector('[data-reference-input]');
  const dialog = form.querySelector('[data-image-dialog]');
  const openDialog = form.querySelector('[data-image-dialog-open]');
  const closeDialog = form.querySelector('[data-image-dialog-close]');
  const doneDialog = form.querySelector('[data-image-dialog-done]');
  const pasteZone = form.querySelector('[data-image-paste]');
  const preview = form.querySelector('[data-reference-preview]');
  const dialogPreview = form.querySelector('[data-reference-dialog-preview]');
  const referenceCount = form.querySelector('[data-reference-count]');
  const feedback = form.querySelector('[data-upload-feedback]');
  const value = form.querySelector('[data-completeness-value]');
  const bar = form.querySelector('[data-completeness-bar]');
  const temperature = form.querySelector('[data-temperature-label]');
  const existingCount = Number(form.dataset.existingReferenceCount || 0);
  const transfer = new DataTransfer();

  const setFeedback = (message = '', kind = '') => {
    if (!feedback) return;
    feedback.textContent = message;
    feedback.className = 'upload-feedback' + (kind ? ' upload-feedback-' + kind : '');
  };

  const updateCompleteness = () => {
    let total = fields.reduce((sum, field) => sum + (field.value.trim() ? Number(field.dataset.completenessWeight || 0) : 0), 0);
    const hasReferences = existingCount > 0 || transfer.files.length > 0;
    if (hasReferences) total += 10;
    total = Math.min(100, total);
    const label = total >= 75 ? 'quente' : (total >= 45 ? 'morno' : 'frio');
    if (value) value.textContent = total + '%';
    if (bar) bar.style.width = total + '%';
    if (temperature) temperature.textContent = 'Temperatura calculada: ' + label;
  };

  const removeFile = (index) => {
    const next = new DataTransfer();
    [...transfer.files].forEach((candidate, candidateIndex) => {
      if (candidateIndex !== index) next.items.add(candidate);
    });
    transfer.items.clear();
    [...next.files].forEach((candidate) => transfer.items.add(candidate));
    input.files = transfer.files;
    renderFiles();
    updateCompleteness();
  };

  const createPreviewCard = (file, index) => {
    const card = document.createElement('article');
    card.className = 'reference-preview-card';
    const image = document.createElement('img');
    const objectUrl = URL.createObjectURL(file);
    image.src = objectUrl;
    image.alt = file.name;
    image.addEventListener('load', () => URL.revokeObjectURL(objectUrl), {once: true});
    const info = document.createElement('div');
    const name = document.createElement('strong');
    name.textContent = file.name;
    const size = document.createElement('small');
    size.textContent = (file.size / 1048576).toLocaleString('pt-BR', {maximumFractionDigits: 2}) + ' MB';
    info.append(name, size);
    const remove = document.createElement('button');
    remove.type = 'button';
    remove.className = 'reference-remove';
    remove.textContent = 'Remover';
    remove.addEventListener('click', () => removeFile(index));
    card.append(image, info, remove);
    return card;
  };

  const renderInto = (container) => {
    if (!container) return;
    container.replaceChildren();
    [...transfer.files].forEach((file, index) => container.append(createPreviewCard(file, index)));
  };

  const renderFiles = () => {
    renderInto(preview);
    renderInto(dialogPreview);
    const selected = transfer.files.length;
    if (referenceCount) {
      referenceCount.textContent = selected
        ? selected + (selected === 1 ? ' imagem selecionada' : ' imagens selecionadas')
        : 'Nenhuma imagem selecionada';
    }
  };

  const addFiles = (files) => {
    const rejected = [];
    [...files].forEach((file) => {
      const type = (file.type || '').toLowerCase();
      if (type && !ALLOWED_TYPES.has(type)) {
        rejected.push(file.name + ': formato não permitido');
        return;
      }
      if (file.size < 1 || file.size > MAX_BYTES) {
        rejected.push(file.name + ': limite de 25 MB');
        return;
      }
      const duplicate = [...transfer.files].some((current) =>
        current.name === file.name && current.size === file.size && current.lastModified === file.lastModified
      );
      if (duplicate) return;
      if (existingCount + transfer.files.length >= MAX_FILES) {
        rejected.push(file.name + ': limite total de 10 imagens');
        return;
      }
      transfer.items.add(file);
    });

    input.files = transfer.files;
    renderFiles();
    updateCompleteness();
    if (rejected.length) {
      setFeedback('Não adicionadas — ' + rejected.join('; '), 'error');
    } else if (transfer.files.length) {
      setFeedback('Imagens prontas para serem adicionadas ao cadastro.', 'success');
    }
  };

  const pasteImages = (event) => {
    if (!dialog?.open) return;
    const items = [...(event.clipboardData?.items || [])];
    const images = items
      .filter((item) => item.kind === 'file' && item.type.startsWith('image/'))
      .map((item, index) => {
        const file = item.getAsFile();
        if (!file) return null;
        const rawType = (file.type || 'image/png').toLowerCase();
        const extension = rawType.includes('jpeg') ? 'jpg' : (rawType.split('/')[1] || 'png').replace('x-', '');
        return new File([file], 'referencia-colada-' + Date.now() + '-' + index + '.' + extension, {
          type: rawType,
          lastModified: Date.now(),
        });
      })
      .filter(Boolean);

    if (!images.length) {
      setFeedback('A área de transferência não contém uma imagem compatível.', 'error');
      return;
    }
    event.preventDefault();
    addFiles(images);
    pasteZone?.classList.add('paste-success');
    setTimeout(() => pasteZone?.classList.remove('paste-success'), 1200);
  };

  fields.forEach((field) => field.addEventListener('input', updateCompleteness));
  input?.addEventListener('change', (event) => addFiles(event.target.files));

  openDialog?.addEventListener('click', () => {
    setFeedback();
    if (typeof dialog?.showModal === 'function') dialog.showModal();
    else dialog?.setAttribute('open', '');
    requestAnimationFrame(() => pasteZone?.focus());
  });
  closeDialog?.addEventListener('click', () => dialog?.close());
  doneDialog?.addEventListener('click', () => dialog?.close());
  dialog?.addEventListener('click', (event) => {
    if (event.target === dialog) dialog.close();
  });
  pasteZone?.addEventListener('click', () => pasteZone.focus());
  document.addEventListener('paste', pasteImages);

  renderFiles();
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
