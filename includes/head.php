<?php
/** includes/head.php — <head> bersama. Set $title sebelum include. */
$title = $title ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · <?= e(APP_NAME . ' ' . APP_SCHOOL) ?></title>
<link rel="icon" href="assets/img/logosmk.png">
<script>document.documentElement.classList.add('js');</script>
<!-- Tailwind CSS (hasil build lokal, bisa offline). Sumber: tailwind.config.js + assets/css/tailwind.input.css -->
<link rel="stylesheet" href="assets/css/tailwind.css?v=<?= @filemtime(BASE_DIR . '/assets/css/tailwind.css') ?>">
<!-- Font Poppins & ikon Font Awesome 6 (lokal) -->
<link rel="stylesheet" href="assets/vendor/poppins/poppins.css">
<link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
<link rel="stylesheet" href="assets/css/app.css?v=<?= @filemtime(BASE_DIR . '/assets/css/app.css') ?>">
</head>
