(() => {
  const form = document.querySelector('.registration-form');
  if (!form) return;
  const steps = [...form.querySelectorAll('.form-step')];
  const progress = [...document.querySelectorAll('[data-progress]')];
  const title = document.getElementById('step-title');
  const description = document.getElementById('step-description');
  const content = {
    1: ['Tell us about yourself', 'Enter the contact details we need to create your profile.'],
    2: ['Secure your account', 'Choose a strong password and review the account terms.']
  };
  function show(step) {
    steps.forEach(panel => {
      const active = Number(panel.dataset.step) === step;
      panel.hidden = !active;
      panel.classList.toggle('active', active);
    });
    progress.forEach(item => item.classList.toggle('active', Number(item.dataset.progress) <= step));
    title.textContent = content[step][0];
    description.textContent = content[step][1];
    document.getElementById('registration').scrollIntoView({behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center'});
    const first = steps[step - 1].querySelector('input,textarea');
    if (first) first.focus({preventScroll: true});
  }
  form.querySelector('[data-next]').addEventListener('click', () => {
    const controls = [...steps[0].querySelectorAll('input,textarea,select')];
    const invalid = controls.find(control => !control.checkValidity());
    if (invalid) { invalid.reportValidity(); invalid.focus(); return; }
    show(2);
  });
  form.querySelector('[data-back]').addEventListener('click', () => show(1));
})();
