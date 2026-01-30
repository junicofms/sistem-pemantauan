Baik, saya telah memperbaiki kesalahan dalam logika deteksi kolom di backend. Saya juga telah membuat pencarian header untuk 'latitude' dan 'longitude' menjadi lebih fleksibel untuk menangani variasi ejaan (seperti 'longtitude') dan spasi ekstra.

Logika JavaScript di frontend juga sudah disiapkan untuk menangani kasus di mana hanya ada satu set koordinat generik yang ditemukan.

Silakan coba unggah file Anda sekali lagi. Peta sekarang seharusnya dapat menampilkan titik koordinat dengan benar, baik untuk file dengan header 'latitude'/'longitude' generik maupun untuk file dengan header 'drone'/'ns' yang lebih spesifik.