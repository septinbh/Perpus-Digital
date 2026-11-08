<?php /** includes/password_fields.php — input password lama, baru, konfirmasi (+ indikator kekuatan). */ ?>
<?php foreach ([['password_lama', 'Password Lama', 'current-password'], ['password_baru', 'Password Baru', 'new-password'], ['konfirmasi', 'Ulangi Password Baru', 'new-password']] as [$nm, $lb, $ac]): ?>
<div>
  <label class="label" for="<?= $nm ?>"><?= $lb ?></label>
  <div class="relative">
    <input id="<?= $nm ?>" name="<?= $nm ?>" type="password" required autocomplete="<?= $ac ?>" class="input pr-11"
      <?= $nm === 'password_baru' ? 'data-pass-new minlength="6"' : '' ?> <?= $nm === 'konfirmasi' ? 'data-pass-confirm' : '' ?>>
    <button type="button" data-toggle-password="<?= $nm ?>" class="absolute right-1.5 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100"><i class="fa-regular fa-eye"></i></button>
  </div>
  <p data-capslock="<?= $nm ?>" class="mt-1 hidden text-xs text-amber-600"><i class="fa-solid fa-triangle-exclamation"></i> Caps Lock aktif</p>
  <?php if ($nm === 'password_baru'): ?>
    <div class="mt-2 flex items-center gap-3"><div class="strength flex-1" data-strength="password_baru"><span></span></div><span class="w-20 text-right text-xs text-soft" data-strength-label="password_baru"></span></div>
    <p class="mt-1 text-xs text-soft">Minimal 6 karakter, kombinasi huruf &amp; angka.</p>
  <?php endif; ?>
</div>
<?php endforeach; ?>
