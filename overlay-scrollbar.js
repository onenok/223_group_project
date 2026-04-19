(function(){
  // Lightweight overlay scrollbar for the document body
  const create = () => {
    const wrapper = document.createElement('div');
    wrapper.className = 'overlay-scrollbar';
    wrapper.innerHTML = '<div class="track"></div><div class="thumb"></div>';
    document.documentElement.appendChild(wrapper);
    return wrapper;
  };

  const clamp = (v, a, b) => Math.max(a, Math.min(b, v));

  const init = () => {
    const el = create();
    const track = el.querySelector('.track');
    const thumb = el.querySelector('.thumb');
    let hideTimer = null;
    let dragging = false;
    let dragOffset = 0;

    const update = () => {
      const vh = window.innerHeight;
      const sh = document.documentElement.scrollHeight;
      const st = window.scrollY || document.documentElement.scrollTop || document.body.scrollTop || 0;
      if (sh <= vh) { el.classList.remove('show'); el.style.display = 'none'; return; }
      el.style.display = 'block';
      el.classList.add('show');
      const trackHeight = vh;
      const thumbH = Math.max(24, (vh / sh) * trackHeight);
      const maxTop = trackHeight - thumbH;
      const top = (st / (sh - vh)) * maxTop;
      thumb.style.height = thumbH + 'px';
      thumb.style.top = top + 'px';
    };

    const showAndFade = () => {
      el.classList.add('show');
      if (hideTimer) clearTimeout(hideTimer);
      hideTimer = setTimeout(() => { if (!dragging) el.classList.remove('show'); }, 900);
    };

    // scroll handlers
    window.addEventListener('scroll', () => { update(); showAndFade(); }, { passive: true });
    window.addEventListener('resize', () => { update(); }, { passive: true });
    document.addEventListener('DOMContentLoaded', update);
    update();

    // dragging
    const onPointerDown = (e) => {
      dragging = true;
      el.classList.add('dragging');
      const rect = thumb.getBoundingClientRect();
      dragOffset = (e.clientY || (e.touches && e.touches[0].clientY)) - rect.top;
      e.preventDefault();
      e.stopPropagation();
    };

    const onPointerMove = (e) => {
      if (!dragging) return;
      const clientY = (e.clientY || (e.touches && e.touches[0].clientY));
      const trackRect = el.getBoundingClientRect();
      const vh = window.innerHeight;
      const sh = document.documentElement.scrollHeight;
      const thumbH = parseFloat(getComputedStyle(thumb).height) || 24;
      const maxTop = vh - thumbH;
      let top = clientY - trackRect.top - dragOffset;
      top = clamp(top, 0, maxTop);
      const ratio = top / (maxTop || 1);
      const targetScroll = ratio * (sh - vh);
      window.scrollTo({ top: targetScroll, behavior: 'auto' });
      update();
    };

    const onPointerUp = () => { dragging = false; el.classList.remove('dragging'); showAndFade(); };

    thumb.addEventListener('mousedown', onPointerDown);
    thumb.addEventListener('touchstart', onPointerDown, {passive:false});
    window.addEventListener('mousemove', onPointerMove);
    window.addEventListener('touchmove', onPointerMove, {passive:false});
    window.addEventListener('mouseup', onPointerUp);
    window.addEventListener('touchend', onPointerUp);

    // track click jumps
    track.addEventListener('click', (e) => {
      if (e.target === thumb) return;
      const rect = el.getBoundingClientRect();
      const vh = window.innerHeight;
      const sh = document.documentElement.scrollHeight;
      const thumbH = parseFloat(getComputedStyle(thumb).height) || 24;
      const clickY = e.clientY - rect.top - thumbH/2;
      const maxTop = vh - thumbH;
      const top = clamp(clickY, 0, maxTop);
      const ratio = top / (maxTop || 1);
      const targetScroll = ratio * (sh - vh);
      window.scrollTo({ top: targetScroll, behavior: 'auto' });
    });

    // initial show then fade
    showAndFade();
  };

  // Run on DOM ready
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
