<?php
/**
 * includes/layout_start.php — sidebar + topbar untuk dashboard siswa & pustakawan.
 * Variabel yang dibutuhkan:
 *   $base   : file dashboard (mis. 'dashboard_siswa.php')
 *   $page   : menu aktif
 *   $menu   : [ key => [label, icon, badge] ]  (key '-' = judul grup)
 *   $user   : ['nama' => .., 'sub' => .., 'foto' => null|path]
 *   $search : ['placeholder' => .., 'page' => 'katalog'] | null
 *   $notifs : [ ['icon'=>..,'text'=>..,'url'=>..] ]
 *   $menuUser : [ [label, url, icon] ] untuk dropdown profil
 */
include BASE_DIR . '/includes/head.php';
$notifs = $notifs ?? [];
?>
<body class="bg-krem min-h-screen">

<!-- overlay sidebar (mobile) -->
<div id="sidebar-overlay" class="fixed inset-0 z-30 hidden bg-black/30 lg:hidden"></div>

<!-- ============ SIDEBAR ============ -->
<aside id="sidebar" class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col justify-between border-r border-gold-dark bg-white px-4 py-6 transition-transform duration-300 lg:translate-x-0">
  <div class="overflow-y-auto">
    <a href="<?= e($base) ?>" class="mb-8 flex items-center gap-3 px-2">
      <img src="assets/img/logosmk.png" alt="Logo" class="h-11 w-11">
      <div class="leading-tight">
        <p class="text-[15px] font-extrabold text-ink">SMKN 1 Kamal</p>
        <p class="text-xs font-medium text-gold-dark">Library</p>
      </div>
    </a>
    <nav class="space-y-1">
      <?php foreach ($menu as $key => $m): ?>
        <?php if ($m[0] === '-'): ?>
          <p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-soft"><?= e($m[1]) ?></p>
        <?php else: ?>
          <a href="<?= e($base) ?>?page=<?= e($key) ?>" class="side-link <?= $page === $key ? 'active' : '' ?>">
            <i class="fa-solid <?= e($m[1]) ?>"></i><span><?= e($m[0]) ?></span>
            <?php if (!empty($m[2])): ?><span class="side-badge"><?= (int)$m[2] ?></span><?php endif; ?>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>
    </nav>
  </div>
  <a href="logout.php" class="mt-6 flex items-center justify-center gap-2 rounded-xl bg-red-500 px-4 py-3 font-semibold text-white transition hover:-translate-y-0.5 hover:bg-red-600"
     onclick="event.preventDefault(); confirmBox('Yakin ingin keluar dari aplikasi?',{okText:'Logout'}).then(ok=>ok&&(location.href='logout.php'))">
    <i class="fa-solid fa-right-from-bracket"></i> Logout
  </a>
</aside>

<!-- ============ KONTEN ============ -->
<div class="lg:ml-64">
  <header class="sticky top-0 z-20 px-4 pt-4 sm:px-6 lg:px-8">
    <div class="flex items-center gap-3 rounded-custom bg-white/95 px-4 py-3 shadow-md-soft backdrop-blur">
      <button class="btn btn-ghost btn-sm lg:hidden" data-sidebar-toggle aria-label="Menu"><i class="fa-solid fa-bars text-lg"></i></button>

      <?php if (!empty($search)): ?>
      <form method="get" action="<?= e($base) ?>" class="relative hidden max-w-md flex-1 sm:block">
        <input type="hidden" name="page" value="<?= e($search['page']) ?>">
        <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-soft"></i>
        <input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="<?= e($search['placeholder']) ?>"
               class="w-full rounded-full border border-transparent bg-krem/60 py-2.5 pl-11 pr-4 text-sm shadow-inner transition focus:border-gold focus:bg-white focus:outline-none focus:ring-2 focus:ring-gold/40">
      </form>
      <?php endif; ?>

      <p class="ml-auto hidden text-xs text-soft md:block" data-clock></p>

      <!-- notifikasi -->
      <div class="relative">
        <button class="relative flex h-10 w-10 items-center justify-center rounded-full text-soft transition hover:bg-krem hover:text-gold-dark" data-dropdown="dd-notif" aria-label="Notifikasi">
          <i class="fa-solid fa-bell text-lg"></i>
          <?php if ($notifs): ?><span class="absolute right-1.5 top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white"><?= count($notifs) ?></span><?php endif; ?>
        </button>
        <div id="dd-notif" data-dropdown-menu class="absolute right-0 mt-2 hidden w-80 overflow-hidden rounded-2xl border border-gold/30 bg-white shadow-lg-soft">
          <p class="border-b border-gold/20 bg-gold/10 px-4 py-3 text-sm font-semibold">Notifikasi</p>
          <?php if (!$notifs): ?>
            <p class="px-4 py-6 text-center text-sm text-soft"><i class="fa-regular fa-bell-slash mb-2 block text-2xl"></i>Tidak ada notifikasi</p>
          <?php else: foreach ($notifs as $n): ?>
            <a href="<?= e($n['url']) ?>" class="flex gap-3 border-b border-gray-50 px-4 py-3 text-sm transition hover:bg-krem/60">
              <i class="fa-solid <?= e($n['icon']) ?> mt-0.5 text-gold-dark"></i><span><?= e($n['text']) ?></span>
            </a>
          <?php endforeach; endif; ?>
        </div>
      </div>

      <!-- profil -->
      <div class="relative">
        <button class="flex items-center gap-2 rounded-full py-1 pl-1 pr-3 transition hover:bg-krem" data-dropdown="dd-user">
          <?php if (!empty($user['foto'])): ?>
            <img src="<?= e($user['foto']) ?>" class="h-9 w-9 rounded-full border-2 border-gold-dark object-cover" alt="">
          <?php else: ?>
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-gold text-sm font-bold text-ink"><?= e(mb_strtoupper(mb_substr($user['nama'], 0, 1))) ?></span>
          <?php endif; ?>
          <span class="hidden text-left leading-tight sm:block">
            <span class="block text-sm font-semibold text-ink"><?= e($user['nama']) ?></span>
            <span class="block text-[11px] text-soft"><?= e($user['sub']) ?></span>
          </span>
          <i class="fa-solid fa-chevron-down hidden text-[10px] text-soft sm:block"></i>
        </button>
        <div id="dd-user" data-dropdown-menu class="absolute right-0 mt-2 hidden w-52 overflow-hidden rounded-2xl border border-gold/30 bg-white py-1 shadow-lg-soft">
          <?php foreach ($menuUser ?? [] as $mu): ?>
            <a href="<?= e($mu[1]) ?>" class="flex items-center gap-3 px-4 py-2.5 text-sm hover:bg-krem/60"><i class="fa-solid <?= e($mu[2]) ?> w-4 text-gold-dark"></i><?= e($mu[0]) ?></a>
          <?php endforeach; ?>
          <a href="logout.php" class="flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50"><i class="fa-solid fa-right-from-bracket w-4"></i>Logout</a>
        </div>
      </div>
    </div>
  </header>

  <main class="px-4 py-6 sm:px-6 lg:px-8">
