(() => {
  const form = document.getElementById('loginForm');
  const error = document.getElementById('loginError');
  if (!form) return;

  form.addEventListener('submit', async e => {
    e.preventDefault();
    error.hidden = true;
    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;
    button.querySelector('span').textContent = 'Signing in…';
    try {
      const fields = new FormData(form);
      await Schofestream.api('/api/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username: fields.get('username'), password: fields.get('password'), csrf: Schofestream.csrf })
      });
      window.location.href = '/';
    } catch (err) {
      error.textContent = err.message;
      error.hidden = false;
      button.disabled = false;
      button.querySelector('span').textContent = 'Sign in';
    }
  });
})();
