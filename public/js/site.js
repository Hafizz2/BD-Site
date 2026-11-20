// Ye Birhan Dana — public site interactions (kept from the original design + multi-page additions)
(() => {
  const nav = document.getElementById('nav'), btn = document.getElementById('menuBtn');

  // Nav: scroll state + mobile menu
  if (nav) {
    const onScroll = () => nav.classList.toggle('scrolled', scrollY > 10);
    onScroll(); addEventListener('scroll', onScroll, { passive: true });
    const setOpen = o => { nav.classList.toggle('open', o); document.body.classList.toggle('menu-open', o); btn && btn.setAttribute('aria-expanded', o) };
    btn && btn.addEventListener('click', () => setOpen(!nav.classList.contains('open')));
    document.querySelectorAll('#navLinks a, #mMenu a').forEach(a => a.addEventListener('click', () => setOpen(false)));
    addEventListener('keydown', e => { if (e.key === 'Escape') setOpen(false) });
    if (location.hash === '#menu') setOpen(true);
  }

  // Reveal on scroll
  const io = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target) } }), { threshold: .12 });
  document.querySelectorAll('.reveal').forEach(el => io.observe(el));

  // Count-up stats
  const co = new IntersectionObserver(es => es.forEach(e => {
    if (!e.isIntersecting) return; const el = e.target, end = +el.dataset.count, suf = el.dataset.suffix || ''; let t0 = null;
    const step = t => { t0 ??= t; const p = Math.min((t - t0) / 1400, 1); el.textContent = Math.round(end * (1 - Math.pow(1 - p, 3))).toLocaleString() + (p === 1 ? suf : ''); if (p < 1) requestAnimationFrame(step) };
    requestAnimationFrame(step); co.unobserve(el)
  }), { threshold: .5 });
  document.querySelectorAll('[data-count]').forEach(el => co.observe(el));

  // Progress bars (goals)
  const po = new IntersectionObserver(es => es.forEach(e => { if (e.isIntersecting) { e.target.style.width = e.target.dataset.progress + '%'; po.unobserve(e.target) } }), { threshold: .4 });
  document.querySelectorAll('[data-progress]').forEach(el => po.observe(el));

  const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const fine = matchMedia('(pointer: fine)').matches;

  // 3D logo: follows pointer on desktop, gentle auto-motion on touch
  const logo = document.getElementById('logo3d'), stage = document.getElementById('stage');
  if (logo && stage && !reduce) {
    if (fine) {
      addEventListener('mousemove', e => {
        const r = stage.getBoundingClientRect(), x = (e.clientX - (r.left + r.width / 2)) / innerWidth, y = (e.clientY - (r.top + r.height / 2)) / innerHeight;
        logo.style.transform = `rotateY(${x * 30}deg) rotateX(${-y * 30}deg)`;
      });
    } else {
      let t = 0; const auto = () => { t += .012; logo.style.transform = `rotateY(${Math.sin(t) * 14}deg) rotateX(${Math.cos(t * .8) * 6}deg)`; requestAnimationFrame(auto) }; auto();
    }
  }

  // 3D tilt cards (pointer devices)
  if (fine && !reduce) {
    document.querySelectorAll('.tilt').forEach(c => {
      c.addEventListener('mousemove', e => {
        const r = c.getBoundingClientRect(), px = (e.clientX - r.left) / r.width, py = (e.clientY - r.top) / r.height;
        c.style.transform = `perspective(800px) rotateX(${(.5 - py) * 10}deg) rotateY(${(px - .5) * 10}deg) translateY(-4px)`;
        c.style.setProperty('--mx', px * 100 + '%'); c.style.setProperty('--my', py * 100 + '%');
      });
      c.addEventListener('mouseleave', () => { c.style.transform = '' });
    });
  }

  // Back to top
  const top = document.getElementById('toTop');
  if (top) {
    addEventListener('scroll', () => top.classList.toggle('show', scrollY > 700), { passive: true });
    top.addEventListener('click', () => scrollTo({ top: 0, behavior: reduce ? 'auto' : 'smooth' }));
  }

  // Copy-to-clipboard (donate accounts)
  document.querySelectorAll('[data-copy]').forEach(b => b.addEventListener('click', async () => {
    try { await navigator.clipboard.writeText(b.dataset.copy); const t = b.textContent; b.textContent = b.dataset.done || '✓'; setTimeout(() => b.textContent = t, 1600) } catch (e) { }
  }));

  // Lightbox (album pages)
  const lb = document.getElementById('lightbox');
  if (lb) {
    const img = lb.querySelector('img'), items = [...document.querySelectorAll('[data-lightbox]')]; let i = 0;
    const show = n => { i = (n + items.length) % items.length; img.src = items[i].href; img.alt = items[i].dataset.alt || ''; lb.classList.add('open'); document.body.style.overflow = 'hidden' };
    const close = () => { lb.classList.remove('open'); document.body.style.overflow = '' };
    items.forEach((a, n) => a.addEventListener('click', e => { e.preventDefault(); show(n) }));
    lb.querySelector('.lb-close').onclick = close;
    lb.querySelector('.lb-prev').onclick = () => show(i - 1);
    lb.querySelector('.lb-next').onclick = () => show(i + 1);
    lb.addEventListener('click', e => { if (e.target === lb) close() });
    addEventListener('keydown', e => { if (!lb.classList.contains('open')) return; if (e.key === 'Escape') close(); if (e.key === 'ArrowLeft') show(i - 1); if (e.key === 'ArrowRight') show(i + 1) });
  }

  // Registration form: batches per program, under-18 guardian section
  const form = document.getElementById('registerForm');
  if (form) {
    const program = form.querySelector('#program'), batch = form.querySelector('#batch_id');
    const all = batch ? [...batch.querySelectorAll('option[data-program]')] : [];
    const filter = () => {
      if (!program || !batch) return;
      const p = program.value; let firstVisible = null;
      all.forEach(o => { const ok = !p || o.dataset.program === p; o.hidden = !ok; o.disabled = !ok; if (ok && !firstVisible) firstVisible = o });
      if (batch.selectedOptions[0] && batch.selectedOptions[0].disabled) batch.value = firstVisible ? firstVisible.value : '';
    };
    program && program.addEventListener('change', filter); filter();

    const dob = form.querySelector('#date_of_birth'), minor = form.querySelector('#is_minor'), box = document.getElementById('guardianBox');
    const isUnder18 = () => {
      if (minor && minor.checked) return true;
      if (!dob || !dob.value) return false;
      const d = new Date(dob.value), now = new Date(); let age = now.getFullYear() - d.getFullYear();
      if (now.getMonth() < d.getMonth() || (now.getMonth() === d.getMonth() && now.getDate() < d.getDate())) age--;
      return age < 18;
    };
    const sync = () => {
      const u = isUnder18(); box && box.classList.toggle('show', u);
      box && box.querySelectorAll('[data-required-minor]').forEach(i => i.required = u);
      if (minor && dob && dob.value) minor.checked = u;
    };
    dob && dob.addEventListener('change', sync); minor && minor.addEventListener('change', sync); sync();
  }
})();
