/* ==========================================================
   app.js — interaksi UI Perpus Digital (tanpa library tambahan)
   ========================================================== */
(function () {
  'use strict';
  const $  = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));

  /* ---------------- TOAST ---------------- */
  const ICON = { success: 'fa-circle-check', error: 'fa-circle-xmark', warning: 'fa-triangle-exclamation', info: 'fa-circle-info' };
  function toast(type, msg, ms = 4500) {
    let wrap = $('#toast-wrap');
    if (!wrap) { wrap = document.createElement('div'); wrap.id = 'toast-wrap'; document.body.appendChild(wrap); }
    const el = document.createElement('div');
    el.className = 'toast toast-' + (ICON[type] ? type : 'info');
    el.innerHTML = '<i class="fa-solid ' + (ICON[type] || ICON.info) + ' mt-0.5 text-lg"></i>' +
      '<div class="flex-1 leading-snug"></div><button class="text-gray-400 hover:text-gray-700" aria-label="Tutup">&times;</button>';
    el.querySelector('div').textContent = msg;
    const close = () => { el.classList.add('out'); setTimeout(() => el.remove(), 300); };
    el.querySelector('button').onclick = close;
    wrap.appendChild(el);
    setTimeout(close, ms);
  }
  window.toast = toast;

  /* ---------------- MODAL ---------------- */
  function openModal(id) {
    const m = document.getElementById(id);
    if (!m) return;
    m.classList.add('open');
    document.body.style.overflow = 'hidden';
    setTimeout(() => { const f = m.querySelector('input:not([type=hidden]),select,textarea'); f && f.focus(); }, 150);
  }
  function closeModal(m) {
    if (!m) return;
    m.classList.remove('open');
    if (!$('.modal.open')) document.body.style.overflow = '';
  }
  window.openModal = openModal;
  window.closeModal = (id) => closeModal(document.getElementById(id));

  document.addEventListener('click', (ev) => {
    const opener = ev.target.closest('[data-modal-open]');
    if (opener) {
      ev.preventDefault();
      const id = opener.dataset.modalOpen;
      if (opener.dataset.fill) fillForm(document.getElementById(id), JSON.parse(opener.dataset.fill));
      if (opener.dataset.book) fillBook(document.getElementById(id), JSON.parse(opener.dataset.book));
      openModal(id);
      return;
    }
    if (ev.target.closest('[data-modal-close]')) { closeModal(ev.target.closest('.modal')); return; }
    if (ev.target.classList.contains('modal')) closeModal(ev.target);
  });
  document.addEventListener('keydown', (ev) => { if (ev.key === 'Escape') $$('.modal.open').forEach(closeModal); });

  /** Isi form di dalam modal: {name: value}. Elemen [data-text="key"] diisi teks. */
  function fillForm(modal, data) {
    if (!modal) return;
    $$('input[type=file]', modal).forEach((f) => { f.value = ''; });
    $$('input[type=checkbox][data-reset]', modal).forEach((c) => { c.checked = false; });
    Object.entries(data).forEach(([k, v]) => {
      $$('[name="' + k + '"]', modal).forEach((el) => {
        if (el.type === 'checkbox' || el.type === 'radio') el.checked = String(el.value) === String(v);
        else el.value = v ?? '';
      });
      $$('[data-text="' + k + '"]', modal).forEach((el) => { el.textContent = v ?? '-'; });
      $$('[data-src="' + k + '"]', modal).forEach((el) => {
        if (v) { el.src = v; el.classList.remove('hidden'); } else { el.removeAttribute('src'); el.classList.add('hidden'); }
      });
    });
    modal.dispatchEvent(new CustomEvent('modal:filled', { detail: data }));
  }
  window.fillForm = fillForm;

  /** Modal detail buku (siswa). */
  function fillBook(modal, b) {
    fillForm(modal, b);
    const img = $('[data-book-img]', modal), ph = $('[data-book-ph]', modal);
    if (img && ph) {
      if (b.cover) { img.src = b.cover; img.classList.remove('hidden'); ph.classList.add('hidden'); }
      else { img.classList.add('hidden'); ph.classList.remove('hidden'); }
    }
    const btn = $('[data-book-submit]', modal);
    if (btn) {
      btn.disabled = !(b.stok > 0) || !!b.blok;
      btn.innerHTML = b.blok ? '<i class="fa-solid fa-ban"></i> ' + b.blok
        : (b.stok > 0 ? '<i class="fa-solid fa-hand-holding-heart"></i> Ajukan Peminjaman' : '<i class="fa-solid fa-ban"></i> Stok Habis');
    }
  }

  /* ---------------- KONFIRMASI CANTIK ---------------- */
  function confirmBox(msg, { danger = false, okText = 'Ya, lanjutkan' } = {}) {
    return new Promise((resolve) => {
      const m = document.createElement('div');
      m.className = 'modal';
      m.innerHTML =
        '<div class="modal-box max-w-sm p-6 text-center">' +
        '<div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full ' + (danger ? 'bg-red-100 text-red-500' : 'bg-gold/25 text-gold-dark') + '">' +
        '<i class="fa-solid ' + (danger ? 'fa-trash-can' : 'fa-circle-question') + ' text-2xl"></i></div>' +
        '<p class="mb-6 text-sm text-ink" data-msg></p>' +
        '<div class="flex justify-center gap-3"><button class="btn btn-ghost" data-no>Batal</button>' +
        '<button class="btn ' + (danger ? 'btn-red' : 'btn-gold') + '" data-yes></button></div></div>';
      m.querySelector('[data-msg]').textContent = msg;
      m.querySelector('[data-yes]').textContent = okText;
      document.body.appendChild(m);
      requestAnimationFrame(() => m.classList.add('open'));
      const done = (v) => { m.classList.remove('open'); setTimeout(() => m.remove(), 200); resolve(v); };
      m.querySelector('[data-yes]').onclick = () => done(true);
      m.querySelector('[data-no]').onclick = () => done(false);
      m.onclick = (e) => { if (e.target === m) done(false); };
      m.querySelector('[data-yes]').focus();
    });
  }
  window.confirmBox = confirmBox;

  document.addEventListener('submit', async (ev) => {
    const form = ev.target;
    if (form.dataset.confirm && !form.dataset.confirmed) {
      ev.preventDefault();
      const ok = await confirmBox(form.dataset.confirm, { danger: form.dataset.danger !== undefined, okText: form.dataset.ok || 'Ya, lanjutkan' });
      if (!ok) return;
      form.dataset.confirmed = '1';
      if (ev.submitter && ev.submitter.name) {
        const h = document.createElement('input'); h.type = 'hidden'; h.name = ev.submitter.name; h.value = ev.submitter.value; form.appendChild(h);
      }
      form.requestSubmit ? form.requestSubmit() : form.submit();
      return;
    }
    // validasi konfirmasi password
    const p1 = form.querySelector('[data-pass-new]'), p2 = form.querySelector('[data-pass-confirm]');
    if (p1 && p2 && p1.value !== p2.value) {
      ev.preventDefault(); toast('error', 'Konfirmasi password tidak sama.'); p2.focus(); return;
    }
    // status loading pada tombol submit
    const btn = ev.submitter || form.querySelector('[type=submit]');
    if (btn && !btn.dataset.noLoading) {
      setTimeout(() => { btn.disabled = true; btn.dataset.html = btn.innerHTML; btn.innerHTML = '<span class="spin"></span> Memproses...'; }, 0);
    }
  });

  /* ---------------- PASSWORD: tampil/sembunyi, caps lock, kekuatan ---------------- */
  $$('[data-toggle-password]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const input = document.getElementById(btn.dataset.togglePassword);
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.innerHTML = '<i class="fa-regular ' + (show ? 'fa-eye-slash' : 'fa-eye') + '"></i>';
      btn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
    });
  });
  $$('input[type=password]').forEach((input) => {
    const warn = document.querySelector('[data-capslock="' + input.id + '"]');
    if (!warn) return;
    const check = (e) => warn.classList.toggle('hidden', !(e.getModifierState && e.getModifierState('CapsLock')));
    input.addEventListener('keyup', check); input.addEventListener('keydown', check);
    input.addEventListener('blur', () => warn.classList.add('hidden'));
  });
  $$('[data-strength]').forEach((meter) => {
    const input = document.getElementById(meter.dataset.strength);
    const bar = meter.querySelector('span'), label = document.querySelector('[data-strength-label="' + input.id + '"]');
    input.addEventListener('input', () => {
      const v = input.value; let s = 0;
      if (v.length >= 6) s++; if (v.length >= 10) s++;
      if (/[A-Za-z]/.test(v) && /\d/.test(v)) s++;
      if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++;
      if (/[^A-Za-z0-9]/.test(v)) s++;
      const lv = [['0%', '#eee', ''], ['25%', '#EF4444', 'Lemah'], ['45%', '#f97316', 'Cukup'], ['65%', '#DAA520', 'Sedang'], ['85%', '#43B481', 'Kuat'], ['100%', '#2f9e6b', 'Sangat kuat']][v ? Math.max(1, s) : 0];
      bar.style.width = lv[0]; bar.style.background = lv[1];
      if (label) label.textContent = lv[2];
    });
  });

  /* ---------------- SIDEBAR MOBILE ---------------- */
  const sidebar = $('#sidebar'), overlay = $('#sidebar-overlay');
  const toggleSidebar = (open) => {
    if (!sidebar) return;
    sidebar.classList.toggle('-translate-x-full', !open);
    overlay && overlay.classList.toggle('hidden', !open);
  };
  $$('[data-sidebar-toggle]').forEach((b) => b.addEventListener('click', () => toggleSidebar(sidebar.classList.contains('-translate-x-full'))));
  overlay && overlay.addEventListener('click', () => toggleSidebar(false));

  /* ---------------- DROPDOWN ---------------- */
  document.addEventListener('click', (ev) => {
    const t = ev.target.closest('[data-dropdown]');
    $$('[data-dropdown-menu]').forEach((m) => { if (!t || m !== document.getElementById(t.dataset.dropdown)) m.classList.add('hidden'); });
    if (t) document.getElementById(t.dataset.dropdown).classList.toggle('hidden');
  });

  /* ---------------- FILTER CEPAT (tabel / grid) ---------------- */
  $$('[data-filter]').forEach((input) => {
    const items = () => $$(input.dataset.filter);
    const empty = input.dataset.empty ? $(input.dataset.empty) : null;
    const run = () => {
      const q = input.value.trim().toLowerCase(); let n = 0;
      items().forEach((el) => {
        const hit = !q || (el.dataset.search || el.textContent).toLowerCase().includes(q);
        el.classList.toggle('is-hidden', !hit); if (hit) n++;
      });
      if (empty) empty.classList.toggle('hidden', n > 0);
    };
    input.addEventListener('input', run);
  });

  /* ---------------- TABS ---------------- */
  $$('[data-tabs]').forEach((wrap) => {
    const btns = $$('[data-tab]', wrap);
    const show = (name) => {
      btns.forEach((b) => {
        const on = b.dataset.tab === name;
        b.classList.toggle('bg-gold', on); b.classList.toggle('text-black', on); b.classList.toggle('shadow-gold', on);
        b.classList.toggle('text-soft', !on);
      });
      $$('[data-tab-panel]', wrap).forEach((p) => p.classList.toggle('hidden', p.dataset.tabPanel !== name));
    };
    btns.forEach((b) => b.addEventListener('click', () => { show(b.dataset.tab); history.replaceState(null, '', '#' + b.dataset.tab); }));
    const h = location.hash.slice(1);
    show(btns.some((b) => b.dataset.tab === h) ? h : btns[0]?.dataset.tab);
  });

  /* ---------------- ANGKA BERHITUNG ---------------- */
  $$('[data-count]').forEach((el) => {
    const target = parseInt(el.dataset.count, 10) || 0, rp = el.dataset.rupiah !== undefined;
    const fmt = (n) => rp ? 'Rp ' + n.toLocaleString('id-ID') : n.toLocaleString('id-ID');
    if (target === 0) { el.textContent = fmt(0); return; }
    const t0 = performance.now(), dur = 900;
    const step = (t) => {
      const p = Math.min(1, (t - t0) / dur), v = Math.round(target * (1 - Math.pow(1 - p, 3)));
      el.textContent = fmt(v); if (p < 1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  });

  /* ---------------- PREVIEW GAMBAR UPLOAD ---------------- */
  $$('input[type=file][data-preview]').forEach((input) => {
    input.addEventListener('change', () => {
      const img = $(input.dataset.preview), f = input.files[0];
      if (!img || !f) return;
      if (f.size > 2 * 1024 * 1024) { toast('error', 'Ukuran gambar maksimal 2 MB.'); input.value = ''; return; }
      img.src = URL.createObjectURL(f); img.classList.remove('hidden');
    });
  });

  /* ---------------- JAM DIGITAL ---------------- */
  const clock = $('[data-clock]');
  if (clock) {
    const tick = () => {
      clock.textContent = new Date().toLocaleString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    };
    tick(); setInterval(tick, 30000);
  }

  /* ---------------- ANIMASI SAAT SCROLL ---------------- */
  const io = 'IntersectionObserver' in window ? new IntersectionObserver((es) => es.forEach((e) => {
    if (e.isIntersecting) { e.target.classList.add('show'); io.unobserve(e.target); }
  }), { threshold: 0.08 }) : null;
  $$('.reveal').forEach((el, i) => {
    el.style.transitionDelay = Math.min(i % 8, 7) * 50 + 'ms';
    io ? io.observe(el) : el.classList.add('show');
  });

  /* ---------------- BAR CHART ---------------- */
  $$('[data-bar]').forEach((b) => requestAnimationFrame(() => setTimeout(() => { b.style.height = b.dataset.bar + '%'; }, 100)));

  /* ---------------- HITUNG DENDA OTOMATIS (form pengembalian) ---------------- */
  $$('[data-denda-form]').forEach((form) => {
    const out = $('[data-denda-total]', form), detail = $('[data-denda-detail]', form);
    const calc = () => {
      const perHari = +form.dataset.perHari, rusak = +form.dataset.rusak, hilang = +form.dataset.hilang;
      const tempo = form.querySelector('[name=jatuh_tempo]').value;
      const kembali = form.querySelector('[name=tanggal_kembali]').value;
      const kondisi = (form.querySelector('[name=kondisi]:checked') || {}).value || 'baik';
      let telat = 0;
      if (tempo && kembali) telat = Math.max(0, Math.round((new Date(kembali) - new Date(tempo)) / 86400000));
      const extra = kondisi === 'rusak' ? rusak : kondisi === 'hilang' ? hilang : 0;
      const total = telat * perHari + extra;
      out.textContent = 'Rp ' + total.toLocaleString('id-ID');
      detail.textContent = 'Terlambat ' + telat + ' hari × Rp ' + perHari.toLocaleString('id-ID') + (extra ? ' + denda ' + kondisi + ' Rp ' + extra.toLocaleString('id-ID') : '');
    };
    form.addEventListener('input', calc); form.addEventListener('change', calc);
    form.closest('.modal')?.addEventListener('modal:filled', calc);
    calc();
  });

  /* ---------------- FLASH DARI SERVER ---------------- */
  (window.__FLASH || []).forEach((f, i) => setTimeout(() => toast(f.type, f.msg), 150 + i * 200));
})();
