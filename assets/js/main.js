(function(){
  const btn = document.querySelector('.menu-toggle');
  const drawer = document.getElementById('nav-drawer');
  if(!btn || !drawer) return;

  function setOpen(open){
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    if(open){ drawer.removeAttribute('hidden'); }
    else{ drawer.setAttribute('hidden',''); }
  }

  btn.addEventListener('click', () => {
    const open = btn.getAttribute('aria-expanded') === 'true';
    setOpen(!open);
  });

  document.addEventListener('keydown', (e) => {
    if(e.key === 'Escape') setOpen(false);
  });

  document.addEventListener('click', (e) => {
    if(drawer.hasAttribute('hidden')) return;
    const inside = drawer.contains(e.target) || btn.contains(e.target);
    if(!inside) setOpen(false);
  });
})();

(function () {
  const btn = document.querySelector('.dna-contact-trigger');
  const footer = document.querySelector('.manifesto') 
  	|| document.querySelector('footer, .site-footer, #colophon'); // 兜底
  if (!btn || !footer) return;

  let ticking = false;

  function update() {
    ticking = false;

    const gap = 24; // 距离 footer 的固定间距
    const fr = footer.getBoundingClientRect();

    // footer 顶部进入视窗后，会产生“重叠高度”
    // overlap = 视窗底部 - footer顶部（>0 表示 footer 已进入视窗）
    const overlap = Math.max(0, window.innerHeight - fr.top);

    btn.style.bottom = (gap + overlap) + 'px';
  }

  function onScroll() {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(update);
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll);
  update();
})();

(function () {
  const wrapper = document.querySelector('.woocommerce-notices-wrapper');
  if (!wrapper) return;

  function normalizeNotice(notice) {
    if (!notice) return;
    notice.removeAttribute('tabindex');
    if (typeof notice.blur === 'function') notice.blur();
  }

  function addCloseButton(notice) {
    if (!notice) return;
    if (notice.querySelector('.dna-notice-close')) return;
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'dna-notice-close';
    btn.setAttribute('aria-label', 'Close notice');
    btn.textContent = '×';
    btn.addEventListener('click', () => {
      notice.remove();
    });
    notice.appendChild(btn);
    normalizeNotice(notice);
  }

  wrapper.querySelectorAll('.woocommerce-message, .woocommerce-info, .woocommerce-error').forEach(addCloseButton);
  wrapper.querySelectorAll('.woocommerce-message, .woocommerce-info, .woocommerce-error').forEach(normalizeNotice);

  const observer = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => {
      mutation.addedNodes.forEach((node) => {
        if (!node || node.nodeType !== 1) return;
        if (node.matches && node.matches('.woocommerce-message, .woocommerce-info, .woocommerce-error')) {
          addCloseButton(node);
          normalizeNotice(node);
          return;
        }
        if (node.querySelectorAll) {
          node.querySelectorAll('.woocommerce-message, .woocommerce-info, .woocommerce-error').forEach(addCloseButton);
          node.querySelectorAll('.woocommerce-message, .woocommerce-info, .woocommerce-error').forEach(normalizeNotice);
        }
      });
    });
  });

  observer.observe(wrapper, { childList: true, subtree: true });
})();
