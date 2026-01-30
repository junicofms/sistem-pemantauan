Tentu, permintaan Anda untuk membedakan titik koordinat drone dan ns di peta telah selesai.

**Berikut adalah perubahannya:**

1.  **Deteksi Multi-Koordinat:** Sistem sekarang secara otomatis akan mencari beberapa set kolom koordinat di file Anda:
    *   `latitude drone` & `longtitude drone`
    *   `latitude ns` & `longtitude ns`

2.  **Pewarnaan Titik Peta yang Berbeda:**
    *   Jika koordinat "drone" ditemukan dalam sebuah baris data, titik tersebut akan muncul di peta dengan warna **Biru**.
    *   Jika koordinat "ns" ditemukan dalam baris data yang sama, akan muncul titik **tambahan** di peta dengan warna **Ungu**.

Dengan ini, Anda dapat melihat kedua titik dari satu baris data secara bersamaan di peta, masing-masing dengan warna yang berbeda untuk memudahkan identifikasi. Info yang muncul saat titik di-klik akan tetap menampilkan semua data dari baris tersebut.