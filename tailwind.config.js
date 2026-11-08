/**
 * Konfigurasi Tailwind CSS — warna asli aplikasi dipertahankan.
 * Setelah menambah/mengubah class Tailwind di file PHP/JS, jalankan:  npm run build
 */
module.exports = {
  content: ['./*.php', './includes/**/*.php', './pages/**/*.php', './assets/js/**/*.js'],
  theme: {
    extend: {
      fontFamily: { sans: ['Poppins', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
      colors: {
        krem: '#F5F5DC',            // latar utama
        gold: {
          DEFAULT: '#FFD700',       // emas terang (menu aktif / highlight)
          dark: '#DAA520',          // emas gelap (garis, ikon, tombol)
          deep: '#A38029',          // hover tombol emas gelap
          btn: '#E3B341',           // tombol login
          'btn-dark': '#D4A017',    // hover tombol login
        },
        ink: '#333333',             // teks utama
        soft: '#7A7A7A',            // teks sekunder
        merah: '#EF4444',
        hijau: '#43B481',
      },
      boxShadow: {
        soft: '0 4px 10px rgba(0,0,0,0.05)',
        'md-soft': '0 8px 15px rgba(0,0,0,0.08)',
        'lg-soft': '0 12px 25px rgba(0,0,0,0.12)',
        gold: '0 10px 20px rgba(255,215,0,0.25)',
      },
      borderRadius: { custom: '18px' },
      keyframes: {
        fadeUp: { '0%': { opacity: 0, transform: 'translateY(20px)' }, '100%': { opacity: 1, transform: 'translateY(0)' } },
        floaty: { '0%,100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-8px)' } },
      },
      animation: {
        'fade-up': 'fadeUp .7s ease-out both',
        floaty: 'floaty 3s ease-in-out infinite',
      },
    },
  },
  plugins: [],
};
