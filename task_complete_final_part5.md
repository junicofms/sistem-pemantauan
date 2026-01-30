Baik, saya telah memperbaiki logika pada peta.

**Masalahnya adalah:**
Sistem sebelumnya saya atur untuk secara spesifik mencari header `latitude drone` dan `latitude ns`. Jika file Anda hanya memiliki header `latitude` dan `longitude` biasa, sistem tidak dapat menemukannya.

**Solusi yang Diterapkan:**
Saya telah memperbarui sistem agar lebih fleksibel:
1.  **Prioritas:** Sistem akan **pertama** mencari kolom spesifik `drone` dan `ns`. Jika ditemukan, sistem akan menampilkan titik-titik biru dan ungu seperti yang diminta sebelumnya.
2.  **Fallback (Jika Gagal):** Jika kolom spesifik tersebut **tidak ditemukan**, sistem akan secara otomatis mencari kolom generik dengan nama `latitude` dan `longitude`. Jika ditemukan, sistem akan menampilkan titik-titik tersebut di peta dengan pewarnaan berdasarkan tipenya (merah untuk tower, hijau untuk pohon/span).

Dengan ini, peta sekarang akan berfungsi baik untuk file yang memiliki kolom koordinat spesifik (`drone`/`ns`) maupun file yang hanya memiliki kolom `latitude` dan `longitude` biasa. Silakan coba lagi.