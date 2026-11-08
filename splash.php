<?php require 'config.php'; $title = 'Selamat Datang'; ?>
<?php include 'includes/head.php'; ?>
<body class="splash flex min-h-screen items-center justify-center overflow-hidden">
<style>
  .splash { background: linear-gradient(135deg, #fcd34d, #fbbf24, #f59e0b); background-size: 400% 400%; animation: gradientMove 8s ease infinite; }
  @keyframes gradientMove { 0%,100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
  @keyframes fadeScale { from { opacity: 0; transform: scale(.85); } to { opacity: 1; transform: scale(1); } }
  @keyframes dotBounce { 0%,80%,100% { transform: scale(0); } 40% { transform: scale(1); } }
  @keyframes fill { from { width: 0; } to { width: 100%; } }
  .logo-anim { animation: fadeScale 1.2s ease-out both, floaty 3s ease-in-out 1.2s infinite; }
  .dots span { display: inline-block; width: 8px; height: 8px; margin: 0 4px; background: #fff; border-radius: 50%; animation: dotBounce 1.4s infinite ease-in-out; }
  .dots span:nth-child(2) { animation-delay: .2s; } .dots span:nth-child(3) { animation-delay: .4s; }
  .bubble { position: absolute; border-radius: 9999px; background: rgba(255,255,255,.18); animation: floaty 6s ease-in-out infinite; }
</style>
<span class="bubble left-[8%] top-[15%] h-24 w-24"></span>
<span class="bubble bottom-[12%] right-[10%] h-36 w-36" style="animation-delay:1s"></span>
<span class="bubble right-[25%] top-[10%] h-10 w-10" style="animation-delay:2s"></span>

<div class="relative text-center">
  <div class="logo-anim mx-auto mb-5 flex h-28 w-28 items-center justify-center rounded-3xl bg-white/20 shadow-xl backdrop-blur">
    <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
      <path d="M2 4.5C2 3.119 3.119 2 4.5 2H12v16H4.5A2.5 2.5 0 0 1 2 15.5v-11z"/><path d="M22 4.5C22 3.119 20.881 2 19.5 2H12v16h7.5A2.5 2.5 0 0 0 22 15.5v-11z"/>
    </svg>
  </div>
  <h1 class="animate-fade-up text-4xl font-extrabold tracking-wide text-white drop-shadow" style="animation-delay:.3s">Perpus Digital</h1>
  <p class="animate-fade-up mb-5 text-sm tracking-[0.3em] text-white/90" style="animation-delay:.6s">SMKN 1 KAMAL</p>
  <img src="assets/img/logosmk.png" alt="Logo SMK" class="animate-fade-up mx-auto mb-6 w-14 drop-shadow-md" style="animation-delay:.9s">
  <div class="mx-auto mb-4 h-1.5 w-48 overflow-hidden rounded-full bg-white/30"><div class="h-full rounded-full bg-white" style="animation: fill 3.2s ease-in-out forwards"></div></div>
  <div class="dots"><span></span><span></span><span></span></div>
  <a href="role.php" class="mt-6 inline-block text-xs text-white/80 underline-offset-4 hover:underline">Lewati &rarr;</a>
</div>
<script>setTimeout(() => location.href = 'role.php', 3500);</script>
<?php include 'includes/foot.php'; ?>
