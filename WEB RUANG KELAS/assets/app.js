(() => {
  'use strict';

  const form     = document.getElementById('form');
  const hasil    = document.getElementById('hasil');
  const ringkasan = document.getElementById('ringkasan');
  const konteks  = document.getElementById('konteks');
  const statusEl = document.getElementById('status-muat');
  const jamEl    = document.getElementById('jam');
  const autoCek  = document.getElementById('auto');
  const modeEl   = document.getElementById('mode');

  const REFRESH_MS = Number(document.body.dataset.refresh || 30) * 1000;
  let offsetMs     = Number(document.body.dataset.epoch) * 1000 - Date.now();
  let utcOffsetMin = Number(document.body.dataset.utcoffset || 0);

  let timer = null;
  let ctrl = null;
  let debounce = null;

  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => (
    { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
  ));
  const pad = (n) => String(n).padStart(2, '0');

  /* ---------- Jam server berjalan ---------- */
  function jamServer() {
    const d = new Date(Date.now() + offsetMs + utcOffsetMin * 60000);
    return `${pad(d.getUTCHours())}:${pad(d.getUTCMinutes())}:${pad(d.getUTCSeconds())}`;
  }
  setInterval(() => { jamEl.textContent = jamServer(); }, 1000);
  jamEl.textContent = jamServer();

  /* ---------- Mode waktu ---------- */
  function setMode(mode) {
    modeEl.value = mode;
    document.querySelectorAll('.segmen button').forEach((b) => {
      b.setAttribute('aria-pressed', String(b.dataset.mode === mode));
    });
    const now = mode === 'now';
    const pNow = document.getElementById('panel-now');
    const pCus = document.getElementById('panel-custom');
    pNow.hidden = !now;
    pCus.hidden = now;
    pNow.querySelectorAll('select,input').forEach((i) => { i.disabled = !now; });
    pCus.querySelectorAll('select,input').forEach((i) => { i.disabled = now; });
    load();
  }

  document.querySelectorAll('.segmen button').forEach((b) => {
    b.addEventListener('click', () => setMode(b.dataset.mode));
  });

  /* ---------- Ambil & tampilkan data ---------- */
  function params() {
    const p = new URLSearchParams();
    new FormData(form).forEach((v, k) => { if (String(v) !== '') p.append(k, v); });
    return p;
  }

  async function load() {
    if (ctrl) ctrl.abort();
    ctrl = new AbortController();
    statusEl.textContent = 'Memuat…';

    try {
      const res = await fetch('api/search.php?' + params().toString(), {
        signal: ctrl.signal,
        cache: 'no-store',
        headers: { Accept: 'application/json' },
      });
      const data = await res.json();
      if (!res.ok) throw new Error(data.error || 'Gagal memuat data.');

      offsetMs = data.server_epoch * 1000 - Date.now();
      utcOffsetMin = data.utc_offset_menit;
      render(data);
      statusEl.textContent = 'Diperbarui ' + jamServer();
    } catch (err) {
      if (err.name === 'AbortError') return;
      ringkasan.innerHTML = '';
      konteks.textContent = '';
      hasil.innerHTML = `<p class="kosong-msg galat">${esc(err.message || 'Gagal memuat data.')}</p>`;
      statusEl.textContent = 'Gagal memuat';
    }
  }

  function render(d) {
    ringkasan.innerHTML =
      `<span class="pill ok"><b>${d.kosong}</b> kosong</span>` +
      `<span class="pill no"><b>${d.terisi}</b> terisi</span>` +
      `<span class="pill"><b>${d.total}</b> ruang</span>`;

    konteks.textContent = `${d.tanggal_label}, pukul ${d.mulai}–${d.selesai}`;

    if (!d.ruang.length) {
      hasil.innerHTML = '<p class="kosong-msg">Tidak ada ruang yang cocok dengan pencarian ini.</p>';
      return;
    }
    hasil.innerHTML = d.ruang.map(kartu).join('');
  }

  const jenisLabel = { kuliah: 'Kuliah', pinjam: 'Peminjaman' };

  function kartu(r) {
    const tag = r.fasilitas.map((f) => `<li>${esc(f)}</li>`).join('');
    let info;

    if (r.status === 'kosong') {
      info = r.kosong_sampai
        ? `Kosong sampai <b>${esc(r.kosong_sampai)}</b>`
        : 'Kosong hingga akhir hari';
      if (r.berikut) {
        info += `<span class="sub">Berikutnya: ${esc(r.berikut.judul)} (${esc(r.berikut.mulai)}–${esc(r.berikut.selesai)})</span>`;
      }
    } else {
      const s = r.sedang;
      info = `${esc(jenisLabel[s.jenis] || 'Kegiatan')}: <b>${esc(s.judul)}</b>`;
      if (s.sub) info += `<span class="sub">${esc(s.sub)}</span>`;
      info += `<span class="sub">${esc(s.mulai)}–${esc(s.selesai)} · ` +
        (r.kosong_lagi && r.kosong_lagi < '23:59'
          ? `kosong lagi pukul <b>${esc(r.kosong_lagi)}</b>`
          : 'terisi hingga akhir hari') + '</span>';
    }

    return `
      <article class="kartu ${r.status}">
        <header>
          <div>
            <h3>${esc(r.kode)}</h3>
            <p class="nama">${esc(r.nama)}</p>
          </div>
          <span class="badge">${r.status === 'kosong' ? 'Kosong' : 'Terisi'}</span>
        </header>
        <p class="lokasi">${esc(r.gedung)} · Lantai ${r.lantai} · ${r.kapasitas} kursi</p>
        <p class="info">${info}</p>
        ${tag ? `<ul class="tag">${tag}</ul>` : ''}
      </article>`;
  }

  /* ---------- Pembaruan otomatis ---------- */
  function jadwalkan() {
    clearInterval(timer);
    if (autoCek.checked) timer = setInterval(() => { if (!document.hidden) load(); }, REFRESH_MS);
  }

  autoCek.addEventListener('change', jadwalkan);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) load(); });

  form.addEventListener('submit', (e) => { e.preventDefault(); load(); });
  form.addEventListener('change', () => { if (modeEl.value) load(); });
  form.addEventListener('input', (e) => {
    if (e.target.matches('input[type="search"], input[type="number"]')) {
      clearTimeout(debounce);
      debounce = setTimeout(load, 300);
    }
  });

  setMode('now');
  jadwalkan();
})();
