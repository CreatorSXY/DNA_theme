/* DNA Global Contact Popup
   - Panel toggle (no overlay)
   - Lift above footer/manifesto when they enter viewport bottom
*/
(function(){
  function computeLift(){
    var blockers = [];
    // Footer variants (home uses .site-footer-home too)
    var footer = document.querySelector('footer.site-footer');
    if (footer) blockers.push(footer);
    var footerHome = document.querySelector('footer.site-footer-home, .site-footer-home');
    if (footerHome && footerHome !== footer) blockers.push(footerHome);
    var manifesto = document.querySelector('.manifesto');
    if (manifesto) blockers.push(manifesto);

    var vh = window.innerHeight || document.documentElement.clientHeight;
    var lift = 0;

    blockers.forEach(function(el){
      var r = el.getBoundingClientRect();
      // overlap with bottom edge of viewport
      // if element top is below bottom (r.top < vh) and element bottom is below bottom edge region
      if (r.top < vh && r.bottom > 0){
        var overlap = vh - Math.max(r.top, 0);
        if (overlap > lift) lift = overlap;
      }
    });

    // add a small breathing gap
    lift = Math.max(0, Math.round(lift + 10));
    document.documentElement.style.setProperty('--dna-contact-lift', lift + 'px');
  }

  function init(){
    var trigger = document.querySelector('button.dna-contact-trigger');
    var popup = document.getElementById('dnaContactPopup');
    if (!trigger || !popup) return;

    var closeBtn = popup.querySelector('.dna-contact-close');

    function open(){
      popup.classList.add('is-open');
      trigger.setAttribute('aria-expanded', 'true');
      popup.setAttribute('aria-hidden', 'false');
      // Ensure lift is correct immediately after opening.
      requestAnimationFrame(computeLift);
    }
    function close(){
      popup.classList.remove('is-open');
      trigger.setAttribute('aria-expanded', 'false');
      popup.setAttribute('aria-hidden', 'true');
      requestAnimationFrame(computeLift);
    }

    trigger.addEventListener('click', function(){
      if (popup.classList.contains('is-open')) close();
      else open();
    });

    if (closeBtn){
      closeBtn.addEventListener('click', close);
    }

    // click outside the panel closes
    document.addEventListener('click', function(e){
      if (!popup.classList.contains('is-open')) return;
      if (e.target === trigger) return;
      if (popup.contains(e.target)) return;
      close();
    });

    document.addEventListener('keydown', function(e){
      if (e.key === 'Escape' && popup.classList.contains('is-open')) close();
    });

    // prevent accidental submits during QA (no backend yet)
    var form = popup.querySelector('form.dna-contact-form');
    if (form){
      form.addEventListener('submit', function(e){
        e.preventDefault();
        close();
      });
    }

    computeLift();
    window.addEventListener('resize', computeLift, {passive:true});
    window.addEventListener('scroll', computeLift, {passive:true});
  }

  if (document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
