<?php
if (!defined('DAILYIN_ADMIN')) {
    http_response_code(403);
    exit;
}

//jika tombol ganti password diklik
if (isset($_POST['ganti_password'])) {
    verify_csrf();

    $lama = $_POST['password_lama'] ?? '';
    $baru = $_POST['password_baru'] ?? '';
    $ulang = $_POST['password_ulang'] ?? '';

    $user = db_one("SELECT id, password FROM " . USER_TABLE . " WHERE username = ?", [$_SESSION['username']]);

    //password lama bisa masih md5 (sebelum login pertama setelah update) atau sudah bcrypt
    $cocok = $user && (password_verify($lama, $user['password'])
        || (preg_match('/^[a-f0-9]{32}$/i', $user['password']) && hash_equals(strtolower($user['password']), md5($lama))));

    if (!$cocok) {
        alert_redirect('Password lama salah', 'admin.php?page=password');
    } elseif (strlen($baru) < 8) {
        alert_redirect('Password baru minimal 8 karakter', 'admin.php?page=password');
    } elseif ($baru !== $ulang) {
        alert_redirect('Konfirmasi password baru tidak sama', 'admin.php?page=password');
    } else {
        $hash = password_hash($baru, PASSWORD_DEFAULT);
        $simpan = db_run("UPDATE " . USER_TABLE . " SET password = ? WHERE id = ?", [$hash, $user['id']]);

        alert_redirect($simpan ? 'Password berhasil diganti' : 'Password gagal diganti', 'admin.php?page=password');
    }
}
?>
<div class="row justify-content-center pt-4">
    <div class="col-12 col-md-6">
        <form method="post" action="" class="card border-0 shadow-sm p-4">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label small text-muted" for="password_lama">PASSWORD LAMA</label>
                <input type="password" class="form-control" name="password_lama" id="password_lama" autocomplete="current-password" required>
            </div>
            <div class="mb-3">
                <label class="form-label small text-muted" for="password_baru">PASSWORD BARU (min. 8 karakter)</label>
                <input type="password" class="form-control" name="password_baru" id="password_baru" minlength="8" autocomplete="new-password" required>
            </div>
            <div class="mb-4">
                <label class="form-label small text-muted" for="password_ulang">ULANGI PASSWORD BARU</label>
                <input type="password" class="form-control" name="password_ulang" id="password_ulang" minlength="8" autocomplete="new-password" required>
            </div>
            <input type="submit" value="Ganti Password" name="ganti_password" class="btn btn-primary">
        </form>
    </div>
</div>
