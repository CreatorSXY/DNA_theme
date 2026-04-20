(function () {
  const banner = document.querySelector('.dna-shipping-banner');
  if (!banner) return;

  const closeBtn = banner.querySelector('.dna-shipping-banner__close');
  const storageKey = 'dna_shipping_banner_closed';
  const root = document.documentElement;

  function setBannerClosed(closed) {
    if (closed) {
      banner.setAttribute('hidden', '');
      banner.setAttribute('aria-hidden', 'true');
      root.style.setProperty('--dna-banner-h', '0px');
      return;
    }

    banner.removeAttribute('hidden');
    banner.setAttribute('aria-hidden', 'false');
    root.style.removeProperty('--dna-banner-h');
  }

  let isClosed = false;
  try {
    isClosed = window.localStorage.getItem(storageKey) === '1';
  } catch (error) {
    isClosed = false;
  }
  setBannerClosed(isClosed);

  if (!closeBtn) return;
  closeBtn.addEventListener('click', () => {
    setBannerClosed(true);
    try {
      window.localStorage.setItem(storageKey, '1');
    } catch (error) {
      // Ignore storage failures (private mode / blocked storage).
    }
  });
})();

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
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    return;
  }

  const root = document.documentElement;
  root.classList.add('dna-motion');
  window.requestAnimationFrame(() => {
    root.classList.add('dna-motion-in');
  });

  const revealNodes = Array.from(document.querySelectorAll('[data-dna-reveal]'));
  if (!revealNodes.length) return;

  revealNodes.forEach((node, index) => {
    const orderRaw = Number.parseInt(node.getAttribute('data-dna-reveal-order') || '', 10);
    const order = Number.isFinite(orderRaw) ? orderRaw : index + 1;
    node.style.setProperty('--dna-reveal-delay', `${Math.max(0, order - 1) * 70}ms`);
  });

  if (!('IntersectionObserver' in window)) {
    revealNodes.forEach((node) => node.classList.add('is-revealed'));
    return;
  }

  const observer = new window.IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('is-revealed');
      observer.unobserve(entry.target);
    });
  }, {
    rootMargin: '0px 0px -12% 0px',
    threshold: 0.18,
  });

  revealNodes.forEach((node) => observer.observe(node));
})();

(function () {
  if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    return;
  }

  const depthNodes = Array.from(document.querySelectorAll('[data-dna-depth]'));
  if (!depthNodes.length) return;

  let ticking = false;

  function updateDepth() {
    ticking = false;
    const viewportCenter = window.innerHeight * 0.52;

    depthNodes.forEach((node) => {
      const host = node.closest('.home-hero, .b2b-hero, .case-study-hero, .dna-shop__header, .dna-line-term__hero') || node.parentElement;
      if (!host) return;
      const rect = host.getBoundingClientRect();
      const sectionCenter = rect.top + rect.height / 2;
      const ratio = (viewportCenter - sectionCenter) / window.innerHeight;
      const shift = Math.max(-24, Math.min(24, ratio * 42));
      node.style.transform = `translate3d(0, ${shift.toFixed(2)}px, 0)`;
    });
  }

  function onScroll() {
    if (ticking) return;
    ticking = true;
    window.requestAnimationFrame(updateDepth);
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll);
  updateDepth();
})();

(function () {
  const carousel = document.querySelector('[data-case-hero-carousel]');
  if (!carousel) return;

  const viewport = carousel.querySelector('[data-case-hero-viewport]');
  const slides = Array.from(carousel.querySelectorAll('[data-case-hero-slide]'));
  const prevButton = carousel.querySelector('[data-case-hero-prev]');
  const nextButton = carousel.querySelector('[data-case-hero-next]');
  if (!viewport || !slides.length) return;

  const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const autoplayDelay = 4000;
  let activeIndex = 0;
  let timer = null;
  let paused = false;
  let pointerActive = false;
  let pointerStartX = 0;
  let pointerDeltaX = 0;

  const normalizeIndex = (value) => {
    const len = slides.length;
    if (!len) return 0;
    return ((value % len) + len) % len;
  };

  const relativeDelta = (index) => {
    const len = slides.length;
    let delta = index - activeIndex;
    if (delta > len / 2) delta -= len;
    if (delta < -len / 2) delta += len;
    return delta;
  };

  const applyState = () => {
    slides.forEach((slide, index) => {
      const delta = relativeDelta(index);
      slide.classList.remove('is-center', 'is-side', 'is-side-left', 'is-side-right', 'is-hidden');

      if (delta === 0) {
        slide.classList.add('is-center');
        slide.setAttribute('aria-hidden', 'false');
        return;
      }

      if (delta === -1) {
        slide.classList.add('is-side', 'is-side-left');
        slide.setAttribute('aria-hidden', 'true');
        return;
      }

      if (delta === 1) {
        slide.classList.add('is-side', 'is-side-right');
        slide.setAttribute('aria-hidden', 'true');
        return;
      }

      slide.classList.add('is-hidden');
      slide.setAttribute('aria-hidden', 'true');
    });
  };

  const stopAutoplay = () => {
    if (!timer) return;
    window.clearTimeout(timer);
    timer = null;
  };

  const startAutoplay = () => {
    if (prefersReducedMotion || paused || slides.length <= 1) return;
    stopAutoplay();
    timer = window.setTimeout(() => {
      activeIndex = normalizeIndex(activeIndex + 1);
      applyState();
      startAutoplay();
    }, autoplayDelay);
  };

  const restartAutoplay = () => {
    stopAutoplay();
    startAutoplay();
  };

  const goTo = (index, userInitiated) => {
    activeIndex = normalizeIndex(index);
    applyState();
    if (userInitiated) {
      restartAutoplay();
    }
  };

  const next = () => goTo(activeIndex + 1, true);
  const prev = () => goTo(activeIndex - 1, true);

  const setPaused = (value) => {
    paused = value;
    if (paused) {
      stopAutoplay();
      return;
    }
    startAutoplay();
  };

  const beginPointer = (event) => {
    if (!event || typeof event.clientX !== 'number') return;
    pointerActive = true;
    pointerStartX = event.clientX;
    pointerDeltaX = 0;
    setPaused(true);
  };

  const movePointer = (event) => {
    if (!pointerActive || !event || typeof event.clientX !== 'number') return;
    pointerDeltaX = event.clientX - pointerStartX;
  };

  const endPointer = () => {
    if (!pointerActive) return;
    const threshold = 42;
    if (pointerDeltaX <= -threshold) {
      next();
    } else if (pointerDeltaX >= threshold) {
      prev();
    } else {
      startAutoplay();
    }
    pointerActive = false;
    pointerDeltaX = 0;
  };

  if (prevButton) {
    prevButton.addEventListener('click', prev);
  }
  if (nextButton) {
    nextButton.addEventListener('click', next);
  }

  carousel.addEventListener('mouseenter', () => setPaused(true));
  carousel.addEventListener('mouseleave', () => setPaused(false));
  carousel.addEventListener('focusin', () => setPaused(true));
  carousel.addEventListener('focusout', (event) => {
    if (carousel.contains(event.relatedTarget)) return;
    setPaused(false);
  });
  carousel.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') {
      event.preventDefault();
      prev();
    } else if (event.key === 'ArrowRight') {
      event.preventDefault();
      next();
    }
  });

  viewport.addEventListener('pointerdown', beginPointer);
  viewport.addEventListener('pointermove', movePointer);
  viewport.addEventListener('pointerup', endPointer);
  viewport.addEventListener('pointercancel', endPointer);
  viewport.addEventListener('pointerleave', () => {
    if (!pointerActive) return;
    endPointer();
  });

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      stopAutoplay();
      return;
    }
    startAutoplay();
  });

  applyState();
  startAutoplay();
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

(function () {
  const faqRoot = document.querySelector('.b2b-page .b2b-faq-content');
  if (!faqRoot) return;

  const faqItems = faqRoot.querySelectorAll('.wp-block-rank-math-faq-block .rank-math-list-item, .rank-math-list-item');
  if (!faqItems.length) return;

  faqItems.forEach((item, index) => {
    const question = item.querySelector('.rank-math-question');
    const answer = item.querySelector('.rank-math-answer');
    if (!question || !answer) return;

    const answerId = answer.id || `dnaFaqAnswer${index + 1}`;
    answer.id = answerId;

    item.classList.add('dna-faq-enhanced');
    item.classList.remove('is-open');

    question.setAttribute('role', 'button');
    question.setAttribute('tabindex', '0');
    question.setAttribute('aria-controls', answerId);
    question.setAttribute('aria-expanded', 'false');
    answer.setAttribute('aria-hidden', 'true');

    const setOpen = (open) => {
      if (open) {
        const nextHeight = Math.ceil(answer.scrollHeight + 16);
        item.style.setProperty('--dna-faq-answer-h', `${nextHeight}px`);
      }
      item.classList.toggle('is-open', open);
      question.setAttribute('aria-expanded', open ? 'true' : 'false');
      answer.setAttribute('aria-hidden', open ? 'false' : 'true');
    };

    setOpen(false);

    const toggle = () => {
      setOpen(!item.classList.contains('is-open'));
    };

    question.addEventListener('click', (event) => {
      if (event.target.closest('a, button, input, textarea, select, label')) return;
      toggle();
    });

    question.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter' && event.key !== ' ' && event.key !== 'Spacebar') return;
      event.preventDefault();
      toggle();
    });
  });

  window.addEventListener('resize', () => {
    faqItems.forEach((item) => {
      if (!item.classList.contains('is-open')) return;
      const answer = item.querySelector('.rank-math-answer');
      if (!answer) return;
      const nextHeight = Math.ceil(answer.scrollHeight + 16);
      item.style.setProperty('--dna-faq-answer-h', `${nextHeight}px`);
    });
  });
})();

(function () {
  const listing = document.querySelector('[data-dna-archive-listing]');
  if (!listing) return;

  const onArchive =
    document.body.classList.contains('post-type-archive-product') ||
    document.body.classList.contains('tax-product_cat') ||
    document.body.classList.contains('tax-product_tag');
  if (!onArchive) return;

  const root = document.documentElement;
  const swapDurationMs = 260;
  const prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let activeRequestId = 0;
  let activeController = null;

  const parseRootPx = (varName) => {
    const value = window.getComputedStyle(root).getPropertyValue(varName).trim();
    const parsed = Number.parseFloat(value);
    return Number.isFinite(parsed) ? parsed : 0;
  };

  const getArchiveOffset = () => {
    return parseRootPx('--hdr-h') + parseRootPx('--dna-banner-h') + parseRootPx('--dna-adminbar-h') + 14;
  };

  const getCurrentListing = () => document.querySelector('[data-dna-archive-listing]');

  const setBusy = (node, busy) => {
    if (!node) return;
    if (busy) {
      node.classList.add('is-loading');
      node.setAttribute('aria-busy', 'true');
      return;
    }
    node.classList.remove('is-loading');
    node.removeAttribute('aria-busy');
  };

  const markListingRevealed = (node) => {
    if (!node) return;
    node.querySelectorAll('[data-dna-reveal]').forEach((revealNode) => {
      revealNode.classList.add('is-revealed');
    });
  };

  const getPageTitle = (doc) => {
    if (!doc) return '';
    const title = doc.querySelector('title');
    return title && title.textContent ? title.textContent.trim() : '';
  };

  const fetchListing = async (url) => {
    const requestId = ++activeRequestId;
    if (activeController) {
      activeController.abort();
    }

    const controller = new window.AbortController();
    activeController = controller;
    const timeoutId = window.setTimeout(() => controller.abort(), 12000);

    try {
      const response = await window.fetch(url, {
        credentials: 'same-origin',
        signal: controller.signal,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
      });
      if (!response.ok) {
        throw new Error(`Archive request failed: ${response.status}`);
      }

      const html = await response.text();
      const parser = new window.DOMParser();
      const doc = parser.parseFromString(html, 'text/html');
      const nextListing = doc.querySelector('[data-dna-archive-listing]');
      if (!nextListing) {
        throw new Error('Archive listing container not found in response.');
      }

      return {
        stale: requestId !== activeRequestId,
        title: getPageTitle(doc),
        listing: nextListing,
      };
    } finally {
      window.clearTimeout(timeoutId);
      if (activeController === controller) {
        activeController = null;
      }
    }
  };

  const scrollToListingTop = () => {
    const current = getCurrentListing();
    if (!current) return;
    const targetTop = window.pageYOffset + current.getBoundingClientRect().top - getArchiveOffset();
    window.scrollTo({
      top: Math.max(0, targetTop),
      behavior: prefersReducedMotion ? 'auto' : 'smooth',
    });
  };

  const swapListing = (nextListing) => {
    const current = getCurrentListing();
    if (!current || !nextListing) return false;

    markListingRevealed(nextListing);
    current.replaceWith(nextListing);

    const inserted = getCurrentListing();
    if (!inserted) return false;
    inserted.classList.add('is-swapping');
    window.setTimeout(() => {
      const fresh = getCurrentListing();
      if (fresh) fresh.classList.remove('is-swapping');
    }, prefersReducedMotion ? 0 : swapDurationMs);
    return true;
  };

  const loadArchivePage = async (url, mode) => {
    const current = getCurrentListing();
    if (!current) return false;

    setBusy(current, true);
    let result = null;
    try {
      result = await fetchListing(url);
      if (!result || result.stale) return false;

      const didSwap = swapListing(result.listing);
      if (!didSwap) return false;

      const updatedListing = getCurrentListing();
      setBusy(updatedListing, false);

      if (result.title) {
        document.title = result.title;
      }

      if (mode === 'push') {
        window.history.pushState({ dnaArchivePage: true }, '', url);
      }

      scrollToListingTop();
      return true;
    } finally {
      setBusy(getCurrentListing(), false);
    }
  };

  if (!window.history.state || !window.history.state.dnaArchivePage) {
    window.history.replaceState(
      Object.assign({}, window.history.state || {}, { dnaArchivePage: true }),
      '',
      window.location.href
    );
  }

  document.addEventListener('click', (event) => {
    if (event.defaultPrevented) return;
    if (event.button !== 0) return;
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

    const link = event.target.closest('[data-dna-archive-pagination] a');
    if (!link) return;
    const liveListing = getCurrentListing();
    if (liveListing && liveListing.classList.contains('is-loading')) return;
    if (link.target && link.target !== '_self') return;
    if (link.hasAttribute('download')) return;

    let nextUrl = '';
    try {
      const parsed = new URL(link.href, window.location.href);
      if (parsed.origin !== window.location.origin) return;
      nextUrl = parsed.toString();
    } catch (error) {
      return;
    }
    if (!nextUrl || nextUrl === window.location.href) return;

    event.preventDefault();
    loadArchivePage(nextUrl, 'push').catch((error) => {
      if (error && error.name === 'AbortError') return;
      window.location.href = nextUrl;
    });
  });

  window.addEventListener('popstate', () => {
    const targetUrl = window.location.href;
    loadArchivePage(targetUrl, 'pop').catch((error) => {
      if (error && error.name === 'AbortError') return;
      window.location.href = targetUrl;
    });
  });
})();
