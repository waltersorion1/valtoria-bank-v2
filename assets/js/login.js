(() => {
  const button = document.querySelector('[data-password-toggle]');
  const input = document.getElementById('password');
  if (!button || !input) return;
  button.addEventListener('click', () => {
    const showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    button.textContent = showing ? 'Show' : 'Hide';
    button.setAttribute('aria-pressed', showing ? 'false' : 'true');
    input.focus({preventScroll: true});
  });
})();
