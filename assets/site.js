(() => {
  const toggle = document.querySelector('.mobile-toggle');
  const nav = document.querySelector('.nav');
  if (toggle && nav) toggle.addEventListener('click', () => nav.classList.toggle('open'));

  document.querySelectorAll('form[data-request-form]').forEach(form => {
    form.addEventListener('submit', async event => {
      event.preventDefault();
      const button = form.querySelector('button[type="submit"]');
      const status = form.querySelector('.status');
      const oldText = button.textContent;
      button.disabled = true;
      button.textContent = 'Отправляем…';
      if (status) status.className = 'status';

      try {
        const response = await fetch(form.action, {
          method: 'POST',
          body: new FormData(form),
          headers: {'Accept': 'application/json'}
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.ok) throw new Error(data.error || 'Не удалось отправить заявку');
        form.reset();
        if (status) {
          status.textContent = 'Заявка отправлена. Мы свяжемся с вами.';
          status.classList.add('show', 'ok');
        }
      } catch (error) {
        if (status) {
          status.textContent = error.message || 'Ошибка отправки. Используйте телефон или e-mail.';
          status.classList.add('show', 'err');
        }
      } finally {
        button.disabled = false;
        button.textContent = oldText;
      }
    });
  });
})();