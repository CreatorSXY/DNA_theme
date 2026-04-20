(function () {
  const variationForms = document.querySelectorAll('form.variations_form');
  const cartForms = document.querySelectorAll('form.cart');
  if (!variationForms.length && !cartForms.length) return;

  const walletSelectors = [
    '#wc-square-digital-wallet',
    '#wc-square-google-pay',
    '#apple-pay-button',
    '.wc-square-wallet-buttons',
    '.wc-square-wallet-button-with-text'
  ].join(', ');

  function removeWalletButtons(root) {
    if (!root || !root.querySelectorAll) return;
    root.querySelectorAll(walletSelectors).forEach((el) => el.remove());
  }

  removeWalletButtons(document);

  if (document.body && window.MutationObserver) {
    const walletObserver = new MutationObserver((mutations) => {
      let shouldClean = false;
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (shouldClean || node.nodeType !== 1) return;
          const element = node;
          if (element.matches && element.matches(walletSelectors)) {
            shouldClean = true;
            return;
          }
          if (element.querySelector && element.querySelector(walletSelectors)) {
            shouldClean = true;
          }
        });
      });
      if (shouldClean) {
        removeWalletButtons(document);
      }
    });
    walletObserver.observe(document.body, { childList: true, subtree: true });
  }

  function getOptions(select) {
    return Array.from(select.options)
      .filter((opt) => opt.value)
      .map((opt) => ({
        value: opt.value,
        label: opt.textContent.trim(),
        disabled: opt.disabled,
      }));
  }

  function ensureSelectId(select, index) {
    if (!select.id) {
      select.id = 'dna-variation-select-' + index;
    }
    return select.id;
  }

  function syncSwatches(select, wrapper) {
    const options = getOptions(select);
    const current = select.value;
    const buttons = Array.from(wrapper.querySelectorAll('button[data-value]'));
    const needsRebuild =
      buttons.length !== options.length ||
      buttons.some((btn) => !options.find((opt) => opt.value === btn.dataset.value));

    if (needsRebuild) {
      wrapper.innerHTML = '';
      options.forEach((opt) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'dna-variation-swatch';
        btn.dataset.value = opt.value;
        btn.textContent = opt.label;
        btn.setAttribute('aria-pressed', opt.value === current ? 'true' : 'false');
        if (opt.value === current) btn.classList.add('is-active');
        if (opt.disabled) {
          btn.disabled = true;
          btn.classList.add('is-disabled');
        }
        wrapper.appendChild(btn);
      });
      return;
    }

    buttons.forEach((btn) => {
      const opt = options.find((opt) => opt.value === btn.dataset.value);
      const isActive = opt && opt.value === current;
      const isDisabled = opt ? opt.disabled : true;
      btn.classList.toggle('is-active', isActive);
      btn.classList.toggle('is-disabled', isDisabled);
      btn.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      btn.disabled = isDisabled;
    });
  }

  function mountSelect(select, index, form) {
    if (select.dataset.dnaSwatches === 'true') return;

    const selectId = ensureSelectId(select, index);
    const wrapper = document.createElement('div');
    wrapper.className = 'dna-variation-swatches';
    wrapper.dataset.selectId = selectId;

    select.insertAdjacentElement('afterend', wrapper);
    select.dataset.dnaSwatches = 'true';
    form.classList.add('dna-variation-ready');

    syncSwatches(select, wrapper);

    wrapper.addEventListener('click', (event) => {
      const btn = event.target.closest('button[data-value]');
      if (!btn || btn.disabled) return;
      select.value = btn.dataset.value;
      select.dispatchEvent(new Event('change', { bubbles: true }));
      syncSwatches(select, wrapper);
    });

    select.addEventListener('change', () => syncSwatches(select, wrapper));

    const observer = new MutationObserver(() => syncSwatches(select, wrapper));
    observer.observe(select, { attributes: true, childList: true, subtree: true });
  }

  function parseNumber(value, fallback) {
    const num = parseFloat(value);
    return Number.isFinite(num) ? num : fallback;
  }

  function mountQuantity(form) {
    const input = form.querySelector('.quantity input.qty');
    if (!input || input.dataset.dnaQty === 'true') return;

    const wrapper = input.closest('.quantity');
    if (!wrapper) return;

    const minus = document.createElement('button');
    minus.type = 'button';
    minus.className = 'dna-qty-btn dna-qty-btn--minus';
    minus.textContent = '-';
    minus.setAttribute('aria-label', 'Decrease quantity');

    const plus = document.createElement('button');
    plus.type = 'button';
    plus.className = 'dna-qty-btn dna-qty-btn--plus';
    plus.textContent = '+';
    plus.setAttribute('aria-label', 'Increase quantity');

    wrapper.classList.add('dna-qty');
    wrapper.insertBefore(minus, input);
    wrapper.appendChild(plus);
    input.dataset.dnaQty = 'true';
    input.setAttribute('inputmode', 'numeric');

    function getStep() {
      const stepAttr = input.getAttribute('step');
      if (stepAttr === 'any') return 1;
      const step = parseNumber(stepAttr, 1);
      return step > 0 ? step : 1;
    }

    function getMin() {
      return parseNumber(input.getAttribute('min'), 0);
    }

    function getMax() {
      const maxAttr = input.getAttribute('max');
      if (maxAttr === null || maxAttr === '') return null;
      const max = parseNumber(maxAttr, null);
      return Number.isFinite(max) ? max : null;
    }

    function setValue(next) {
      input.value = next;
      input.dispatchEvent(new Event('input', { bubbles: true }));
      input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function updateButtons() {
      const min = getMin();
      const max = getMax();
      const value = parseNumber(input.value, min);
      minus.disabled = Number.isFinite(min) ? value <= min : false;
      plus.disabled = Number.isFinite(max) ? value >= max : false;
    }

    function stepValue(direction) {
      const step = getStep();
      const min = getMin();
      const max = getMax();
      let value = parseNumber(input.value, min);
      value = Number.isFinite(value) ? value : min;
      value += step * direction;
      if (Number.isFinite(min)) value = Math.max(value, min);
      if (Number.isFinite(max)) value = Math.min(value, max);
      setValue(value);
      updateButtons();
    }

    minus.addEventListener('click', () => stepValue(-1));
    plus.addEventListener('click', () => stepValue(1));

    input.addEventListener('input', updateButtons);
    input.addEventListener('change', updateButtons);

    updateButtons();
  }

  variationForms.forEach((form) => {
    const selects = form.querySelectorAll('select');
    selects.forEach((select, index) => mountSelect(select, index, form));

    const reset = form.querySelector('.reset_variations');
    if (reset) {
      reset.addEventListener('click', () => {
        window.setTimeout(() => {
          form.querySelectorAll('select').forEach((select) => {
            const wrapper = select.nextElementSibling;
            if (wrapper && wrapper.classList.contains('dna-variation-swatches')) {
              syncSwatches(select, wrapper);
            }
          });
        }, 0);
      });
    }

    if (window.jQuery && window.jQuery.fn) {
      window.jQuery(form).on('woocommerce_update_variation_values', function () {
        form.querySelectorAll('select').forEach((select) => {
          const wrapper = select.nextElementSibling;
          if (wrapper && wrapper.classList.contains('dna-variation-swatches')) {
            syncSwatches(select, wrapper);
          }
        });
      });
    }
  });

  cartForms.forEach((form) => mountQuantity(form));
})();
