(function () {
  const form = document.querySelector('form.woocommerce-form-register');
  if (!form) return;

  const checkbox = form.querySelector('input[name="dna_privacy_policy"]');
  const submit = form.querySelector('button[type="submit"]');
  if (!checkbox || !submit) return;

  function sync() {
    submit.disabled = !checkbox.checked;
  }

  checkbox.addEventListener('change', sync);
  sync();
})();
