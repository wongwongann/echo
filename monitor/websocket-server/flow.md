FOR PRESENTATION
----------------

[live-monitor.php] ←→ [WebSocket Connection] ←→ [server.js] ←→ [Database]

1. Inisiasi:
   browser (live-monitor.php) → membuat WebSocket connection → server.js
   
2. Koneksi Berhasil:
   server.js → mengirim data awal → live-monitor.php
   
3. RT Update:
   server.js → poll database setiap 5 detik
   jika ada data baru → broadcast ke semua klien → live-monitor.php update UI

[Database] → [server.js] → [WebSocket Connection] → [live-monitor.php]
   ^            ^                    ^                      ^
   |            |                    |                      |
   |        2. Query         3. ws.send()            4. ws.onmessage
   1. fetchBroadcasts()   

note:

• WebSocket harus sudah terhubung sebelum data bisa dikirim
• Data dikirim dalam format string (biasanya JSON yang di-stringify)
• Koneksi WebSocket tetap terbuka dan bisa digunakan untuk komunikasi berkelanjutan
• Server bisa mengirim data kapan saja setelah koneksi terbentuk
• Client akan menerima data melalui event onmessage
