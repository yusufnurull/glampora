<?php
/**
 * SEED DATA AWAL — Penanggung jawab: BACKEND 1
 *
 * Membuat 3 akun demo WAJIB, kategori, jenis kamar, 3 artikel, dan contoh booking
 * (untuk grafik dashboard & demo check-in).
 *
 * Cara menjalankan (pilih salah satu):
 *   - Browser : buka http://localhost/glampora/database/seed.php
 *   - Terminal: php database/seed.php
 *
 * Keamanan: lewat browser, seed hanya mau berjalan jika tabel users masih kosong.
 * Setelah deploy, HAPUS file ini dari hosting.
 */
require_once __DIR__ . '/../includes/bootstrap.php';

$dari_cli = PHP_SAPI === 'cli';
if (!$dari_cli) header('Content-Type: text/plain; charset=utf-8');

// Seed hanya boleh berjalan di database yang masih kosong (browser maupun terminal),
// supaya data contoh tidak dobel. Untuk mengulang: impor ulang glampora.sql dulu.
$jumlah_user = (int) db_satu($conn, 'SELECT COUNT(*) AS n FROM users')['n'];
if ($jumlah_user > 0) {
    if (!$dari_cli) http_response_code(403);
    exit("Seed ditolak: database sudah berisi data. Impor ulang database/glampora.sql dulu, lalu jalankan seed lagi.\n");
}

mysqli_begin_transaction($conn);
try {
    // ---------- Akun demo (wajib sesuai ketentuan tugas) ----------
    $akun = [
        ['Admin Demo', 'admin@demo.test', 'Admin#2026', 'ADMIN', '081200000001'],
        ['Staff Demo', 'staff@demo.test', 'Staff#2026', 'STAFF', '081200000002'],
        ['User Demo',  'user@demo.test',  'User#2026',  'USER',  '081200000003'],
    ];
    $id_user = [];
    foreach ($akun as [$nama, $email, $password, $role, $telp]) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        db_jalankan($conn,
            'INSERT INTO users (nama, email, telepon, password_hash, role) VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = VALUES(role), is_active = 1',
            'sssss', [$nama, $email, $telp, $hash, $role]);
        $id_user[$role] = (int) db_satu($conn, 'SELECT id FROM users WHERE email = ?', 's', [$email])['id'];
    }
    echo "✔ Akun demo dibuat\n";

    // ---------- Kategori & jenis kamar ----------
    $kategori = ['Tenda Safari', 'Dome', 'Cabin'];
    $id_kategori = [];
    foreach ($kategori as $k) {
        db_jalankan($conn, 'INSERT IGNORE INTO categories (nama) VALUES (?)', 's', [$k]);
        $id_kategori[$k] = (int) db_satu($conn, 'SELECT id FROM categories WHERE nama = ?', 's', [$k])['id'];
    }

    $kamar = [
        ['Tenda Safari', 'Safari Tent Deluxe', 'Tenda safari luas dengan kasur queen, teras pribadi, dan pemandangan lembah.', 2, 5, 850000, 'Kasur queen, Kamar mandi dalam, Teras pribadi, Sarapan'],
        ['Tenda Safari', 'Family Safari Tent', 'Tenda keluarga dengan dua kasur dan area duduk, cocok untuk liburan bersama anak.', 4, 3, 1250000, '2 kasur, Kamar mandi dalam, Api unggun, Sarapan'],
        ['Dome', 'Bubble Dome', 'Dome transparan untuk menikmati langit malam langsung dari kasur.', 2, 4, 1100000, 'Atap transparan, AC, Kamar mandi dalam, Sarapan'],
        ['Cabin', 'Forest Cabin', 'Kabin kayu di tengah hutan pinus, nyaman untuk menginap beberapa malam.', 3, 2, 1400000, 'Dapur kecil, Water heater, Balkon, Sarapan'],
    ];
    $id_kamar = [];
    foreach ($kamar as [$kat, $nama, $desk, $kap, $stok, $tarif, $fas]) {
        db_jalankan($conn,
            'INSERT INTO room_types (category_id, nama, deskripsi, kapasitas, total_stok, tarif_per_malam, fasilitas) VALUES (?, ?, ?, ?, ?, ?, ?)',
            'issiiis', [$id_kategori[$kat], $nama, $desk, $kap, $stok, $tarif, $fas]);
        $id_kamar[] = ['id' => mysqli_insert_id($conn), 'tarif' => $tarif];
    }
    echo "✔ Kategori & jenis kamar dibuat\n";

    // ---------- Artikel (minimal 3) ----------
    $artikel = [
        ['Glamping Minimalis: Liburan Alam Tanpa Repot',
         'Cara menikmati glamping dengan barang bawaan seminimal mungkin.',
         "Glamping memberi kenyamanan hotel di tengah alam. Karena tenda sudah dilengkapi kasur, kamar mandi, dan perlengkapan dasar, kamu tidak perlu membawa banyak barang.\n\nCukup bawa pakaian berlapis untuk malam yang dingin, perlengkapan mandi pribadi, obat-obatan, dan satu tas kecil untuk jalan-jalan di sekitar area.\n\nPrinsipnya sederhana: bawa yang benar-benar dipakai, sisanya sudah tersedia. Dengan begitu kamu bisa fokus menikmati udara segar, api unggun, dan langit berbintang."],
        ['Checklist Barang Bawaan Saat Glamping',
         'Daftar barang wajib agar liburan glamping tetap nyaman.',
         "Sebelum berangkat, cek daftar berikut:\n\n1. Jaket hangat dan kaus kaki tebal untuk malam hari\n2. Sandal dan sepatu yang nyaman untuk jalan di tanah\n3. Senter kecil atau headlamp\n4. Lotion anti nyamuk dan tabir surya\n5. Power bank dan kabel pengisi daya\n6. Botol minum isi ulang\n7. Obat pribadi dan kotak P3K kecil\n8. Kantong plastik untuk pakaian kotor\n\nDengan checklist ini, kamu tidak perlu khawatir ada barang penting yang tertinggal."],
        ['Tips Memilih Tipe Kamar Glamping yang Tepat',
         'Safari tent, dome, atau cabin? Sesuaikan dengan rombongan dan gaya liburanmu.',
         "Setiap tipe kamar glamping punya keunggulan sendiri.\n\nBubble dome cocok untuk pasangan yang ingin melihat bintang langsung dari kasur. Safari tent memberi suasana petualangan dengan ruang yang lega. Family safari tent pas untuk rombongan keluarga dengan anak-anak. Forest cabin lebih tertutup dan hangat, ideal untuk menginap beberapa malam.\n\nPerhatikan kapasitas tamu per unit, fasilitas kamar mandi, dan ketersediaan tanggal sebelum memesan."],
    ];
    foreach ($artikel as [$judul, $ringkas, $isi]) {
        db_jalankan($conn,
            'INSERT INTO articles (judul, slug, ringkasan, isi, author_id) VALUES (?, ?, ?, ?, ?)',
            'ssssi', [$judul, slugify($judul), $ringkas, $isi, $id_user['STAFF']]);
    }
    echo "✔ Artikel dibuat\n";

    // ---------- Contoh booking (untuk grafik dashboard & demo) ----------
    $buat_booking = function (int $kamar_idx, string $ci, int $malam, int $unit, string $status, ?int $user_id, bool $asuransi, string $dibuat)
        use ($conn, $id_kamar): int {
        $kamar = $id_kamar[$kamar_idx];
        $co = date('Y-m-d', strtotime("$ci +$malam day"));
        $subtotal = $kamar['tarif'] * $malam * $unit;
        $biaya_asuransi = $asuransi ? (int) round($subtotal * 0.10) : 0;
        $kode = 'GLP-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        db_jalankan($conn,
            'INSERT INTO bookings (kode, user_id, room_type_id, check_in, check_out, jumlah_unit, nama_tamu, email_tamu, telepon_tamu,
                                   dengan_asuransi, biaya_asuransi, subtotal, total, status, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            'siississsiiiiss',
            [$kode, $user_id, $kamar['id'], $ci, $co, $unit, 'Tamu Contoh', 'tamu@contoh.test', '081234567890',
             (int) $asuransi, $biaya_asuransi, $subtotal, $subtotal + $biaya_asuransi, $status, $dibuat]);
        $booking_id = mysqli_insert_id($conn);
        if ($status !== 'PENDING_PAYMENT') {
            db_jalankan($conn,
                "INSERT INTO payments (booking_id, jumlah, status, referensi, paid_at) VALUES (?, ?, 'PAID', ?, ?)",
                'iiss', [$booking_id, $subtotal + $biaya_asuransi, 'SIM-SEED' . $booking_id, $dibuat]);
        }
        return $booking_id;
    };

    // Riwayat 5 bulan terakhir (sudah selesai) agar grafik terisi
    mt_srand(2026);
    for ($i = 0; $i < 40; $i++) {
        $hari_lalu = mt_rand(10, 150);
        $ci = date('Y-m-d', strtotime("-$hari_lalu day"));
        $status = mt_rand(1, 10) <= 2 ? 'CANCELLED' : 'COMPLETED';
        $id = $buat_booking(mt_rand(0, 3), $ci, mt_rand(1, 3), mt_rand(1, 2), $status, null, (bool) mt_rand(0, 1),
            date('Y-m-d H:i:s', strtotime("$ci -" . mt_rand(3, 20) . ' day')));
        if ($status === 'CANCELLED') {
            db_jalankan($conn, "UPDATE payments SET status = 'REFUNDED' WHERE booking_id = ?", 'i', [$id]);
            db_jalankan($conn,
                "INSERT INTO cancellations (booking_id, alasan, status, persen_refund, jumlah_refund, catatan_admin, diproses_oleh, diproses_at, created_at)
                 SELECT id, 'Perubahan rencana perjalanan', 'APPROVED', 50, ROUND(subtotal * 0.5), 'Refund ditransfer (data contoh)', ?,
                        check_in - INTERVAL 1 DAY, check_in - INTERVAL 2 DAY
                 FROM bookings WHERE id = ?", 'ii', [$id_user['STAFF'], $id]);
        }
    }

    // Milik user demo: menginap HARI INI (untuk demo check-in)
    $hari_ini = date('Y-m-d');
    $b1 = $buat_booking(0, $hari_ini, 2, 1, 'CONFIRMED', $id_user['USER'], true, date('Y-m-d H:i:s', strtotime('-7 day')));
    // Milik user demo: 10 hari lagi, sudah mengajukan pembatalan (untuk demo approval staff)
    $b2 = $buat_booking(2, date('Y-m-d', strtotime('+10 day')), 2, 1, 'CONFIRMED', $id_user['USER'], true, date('Y-m-d H:i:s', strtotime('-2 day')));
    db_jalankan($conn,
        "INSERT INTO cancellations (booking_id, user_id, alasan) VALUES (?, ?, 'Ada acara keluarga mendadak di tanggal tersebut.')",
        'ii', [$b2, $id_user['USER']]);
    // Milik user demo: 20 hari lagi, aktif
    $buat_booking(3, date('Y-m-d', strtotime('+20 day')), 3, 1, 'CONFIRMED', $id_user['USER'], false, date('Y-m-d H:i:s'));

    // Isi data tamu booking user demo dengan data user demo
    db_jalankan($conn,
        "UPDATE bookings SET nama_tamu = 'User Demo', email_tamu = 'user@demo.test', telepon_tamu = '081200000003' WHERE user_id = ?",
        'i', [$id_user['USER']]);
    $kode_hari_ini = db_satu($conn, 'SELECT kode FROM bookings WHERE id = ?', 'i', [$b1])['kode'];
    echo "✔ Contoh booking dibuat (kode check-in hari ini: $kode_hari_ini, kontak: user@demo.test)\n";

    mysqli_commit($conn);
    echo "\nSelesai. Login: admin@demo.test / Admin#2026, staff@demo.test / Staff#2026, user@demo.test / User#2026\n";
} catch (Throwable $e) {
    mysqli_rollback($conn);
    if (!$dari_cli) http_response_code(500);
    echo 'Seed gagal: ' . $e->getMessage() . "\n";
}
