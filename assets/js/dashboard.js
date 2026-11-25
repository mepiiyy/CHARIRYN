/**
 * dashboard.js
 * Efek halus pada header saat halaman digulir.
  * Gunanya : Memberi efek pada header ketika halaman digulir.
 * Alur    : Cari elemen terkait -> pasang event -> perbarui tampilan atau perilaku browser.
 * Dipakai oleh: includes/footer.php.
 * Terhubung ke: elemen HTML dengan ID/class dan atribut data.
*/
(function () {
    'use strict';

    var kepala = document.querySelector('.kepala');

    if (!kepala) {
        return;
    }

    /** Menyegarkan informasi saldo berdasarkan piutang dan nominal pembayaran terpilih. */
    function perbarui() {
        if (window.scrollY > 8) {
            kepala.classList.add('mengambang');
        } else {
            kepala.classList.remove('mengambang');
        }
    }

    window.addEventListener('scroll', perbarui, { passive: true });
    perbarui();
})();