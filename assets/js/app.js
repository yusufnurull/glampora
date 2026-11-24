/* ============================================================
   FUNGSI BANTU JAVASCRIPT BERSAMA — Penanggung jawab: FRONTEND 2
   Dipakai oleh semua halaman (user & dashboard). Butuh jQuery.
   ============================================================ */
const BASE_URL = document.body.dataset.baseUrl || '';

/**
 * Panggil API backend dan kembalikan Promise berisi `data` dari response.
 *
 *   api('GET', 'kamar.php').then(daftar => ...)
 *   api('POST', 'bookings.php', { room_type_id: 1, ... })
 *   api('POST', 'kamar.php?id=3', new FormData(form))      // untuk upload file
 *
 * Jika gagal, Promise ditolak dengan Error berisi .message (pesan dari server)
 * dan .errors (per field).
 */
function api(method, endpoint, data) {
    const opsi = { url: BASE_URL + '/api/' + endpoint, method: method, dataType: 'json' };
    if (data instanceof FormData) {
        opsi.data = data;
        opsi.processData = false;
        opsi.contentType = false;
    } else if (method === 'GET') {
        opsi.data = data;
    } else if (data !== undefined) {
        opsi.data = JSON.stringify(data);
        opsi.contentType = 'application/json';
    }
    // jQuery otomatis menambahkan header X-Requested-With yang diwajibkan backend
    return new Promise(function (resolve, reject) {
        $.ajax(opsi)
            .done(function (res) { resolve(res.data); })
            .fail(function (xhr) {
                const res = xhr.responseJSON || {};
                const err = new Error(res.message || 'Tidak dapat terhubung ke server. Coba lagi.');
                err.errors = res.errors || {};
                err.status = xhr.status;
                reject(err);
            });
    });
}

/** Format angka ke Rupiah: 1500000 -> "Rp1.500.000" */
function rupiah(angka) {
    return 'Rp' + Number(angka || 0).toLocaleString('id-ID');
}

/** Escape teks sebelum dimasukkan ke HTML (mencegah XSS). */
function esc(teks) {
    return $('<div>').text(teks == null ? '' : String(teks)).html();
}

/** Label & warna badge untuk setiap status booking / pembatalan. */
const STATUS = {
    PENDING_PAYMENT: ['Menunggu Pembayaran', 'warning'],
    CONFIRMED:       ['Terkonfirmasi', 'success'],
    CHECKED_IN:      ['Sudah Check-in', 'primary'],
    COMPLETED:       ['Selesai', 'secondary'],
    CANCELLED:       ['Dibatalkan', 'danger'],
    PENDING:         ['Menunggu', 'warning'],
    APPROVED:        ['Disetujui', 'success'],
    REJECTED:        ['Ditolak', 'danger'],
};
function badgeStatus(status) {
    const [label, warna] = STATUS[status] || [status, 'secondary'];
    const teksGelap = warna === 'warning' ? ' text-dark' : '';
    return `<span class="badge text-bg-${warna}${teksGelap}">${esc(label)}</span>`;
}

/** Notifikasi kecil di pojok layar. tipe: success | danger | warning | info */
function toast(pesan, tipe = 'success') {
    let wadah = $('#wadah-toast');
    if (!wadah.length) {
        wadah = $('<div id="wadah-toast" class="toast-container position-fixed bottom-0 end-0 p-3" aria-live="polite"></div>').appendTo('body');
    }
    const el = $(`
        <div class="toast align-items-center text-bg-${tipe} border-0" role="status">
            <div class="d-flex">
                <div class="toast-body">${esc(pesan)}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Tutup"></button>
            </div>
        </div>`).appendTo(wadah);
    new bootstrap.Toast(el[0], { delay: 4000 }).show();
    el.on('hidden.bs.toast', () => el.remove());
}

/** Tampilkan error validasi dari server di bawah field yang bersangkutan. */
function tampilkanErrorForm($form, err) {
    bersihkanErrorForm($form);
    let adaField = false;
    $.each(err.errors || {}, function (nama, pesan) {
        const $input = $form.find(`[name="${nama}"]`);
        if ($input.length) {
            adaField = true;
            $input.addClass('is-invalid');
            let $fb = $input.siblings('.invalid-feedback');
            if (!$fb.length) $fb = $('<div class="invalid-feedback"></div>').insertAfter($input);
            $fb.text(pesan);
        }
    });
    const $alert = $form.find('.form-alert');
    if ($alert.length) {
        $alert.removeClass('d-none').text(err.message);
    } else if (!adaField) {
        toast(err.message, 'danger');
    }
}

function bersihkanErrorForm($form) {
    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.form-alert').addClass('d-none').text('');
}

/** Tombol loading: setLoading($btn, true) saat proses, setLoading($btn, false) setelahnya. */
function setLoading($btn, loading) {
    if (loading) {
        $btn.data('teks-asli', $btn.html()).prop('disabled', true)
            .html('<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span> Memproses...');
    } else {
        $btn.prop('disabled', false).html($btn.data('teks-asli'));
    }
}

/** Dialog konfirmasi (pengganti confirm() bawaan browser). Mengembalikan Promise<boolean>. */
function konfirmasi(pesan, teksTombol = 'Ya, lanjutkan', warna = 'danger') {
    return new Promise(function (resolve) {
        const $m = $(`
            <div class="modal fade" tabindex="-1" aria-labelledby="judul-konfirmasi" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
                    <div class="modal-header"><h2 class="modal-title h5" id="judul-konfirmasi">Konfirmasi</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                    <div class="modal-body">${esc(pesan)}</div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="button" class="btn btn-${warna} btn-ya">${esc(teksTombol)}</button>
                    </div>
                </div></div>
            </div>`).appendTo('body');
        const modal = new bootstrap.Modal($m[0]);
        let jawaban = false;
        $m.find('.btn-ya').on('click', function () { jawaban = true; modal.hide(); });
        $m.on('hidden.bs.modal', function () { $m.remove(); resolve(jawaban); });
        modal.show();
    });
}

/** Baris "kosong" / "memuat" untuk tabel. */
function barisInfo(kolom, teks) {
    return `<tr><td colspan="${kolom}" class="text-center text-secondary py-4">${esc(teks)}</td></tr>`;
}

/** Navigasi halaman sederhana. onPilih(halaman) dipanggil saat nomor diklik. */
function renderPaginasi($wadah, halaman, jumlahHalaman, onPilih) {
    $wadah.empty();
    if (jumlahHalaman <= 1) return;
    const $ul = $('<ul class="pagination pagination-sm mb-0"></ul>');
    for (let i = 1; i <= jumlahHalaman; i++) {
        $(`<li class="page-item ${i === halaman ? 'active' : ''}"><button class="page-link">${i}</button></li>`)
            .on('click', () => onPilih(i)).appendTo($ul);
    }
    $wadah.append($('<nav aria-label="Navigasi halaman"></nav>').append($ul));
}
