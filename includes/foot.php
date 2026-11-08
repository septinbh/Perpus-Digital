<?php /** includes/foot.php — flash message + JS aplikasi. */ ?>
<div id="toast-wrap"></div>
<script>window.__FLASH = <?= json_encode(get_flashes(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="assets/js/app.js?v=<?= @filemtime(BASE_DIR . '/assets/js/app.js') ?>"></script>
</body>
</html>
