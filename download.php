<?php
set_time_limit(0);
ini_set('memory_limit', '1024M'); // Menaikkan batas memori untuk penggabungan data besar

if (ob_get_level() == 0) ob_start();

$apiKey = isset($_POST['api_key']) ? trim($_POST['api_key']) : '';
$isDownloading = ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($apiKey));

// Folder untuk menyimpan cache per halaman
$cacheDir = __DIR__ . '/cache_desa';

if ($isDownloading) {
    header('X-Accel-Buffering: no');
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unduh Data Desa dengan Sistem Resume</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background-color: #f4f6f9; color: #333; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: bold; color: #444; }
        input[type="text"] { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
        button { background-color: #007bff; color: white; border: none; padding: 12px 24px; font-size: 16px; border-radius: 4px; cursor: pointer; width: 100%; font-weight: bold; }
        button:hover { background-color: #0056b3; }
        #progressContainer { margin-top: 25px; background-color: #e9ecef; border-radius: 5px; overflow: hidden; height: 25px; display: <?php echo $isDownloading ? 'block' : 'none'; ?>; }
        #progressBar { width: 0%; height: 100%; background-color: #28a745; text-align: center; line-height: 25px; color: white; font-size: 14px; font-weight: bold; transition: width 0.1s ease; }
        #status { margin-top: 15px; font-weight: bold; color: #555; font-size: 14px; }
    </style>
</head>
<body>

<div class="container">
    <h2>Download Data Desa (Fitur Resume)</h2>
    <p>Jika gagal/terhenti di tengah jalan, jalankan ulang skrip ini. Pengunduhan akan otomatis dilanjutkan dari halaman terakhir yang gagal.</p>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="api_key">Masukkan API Key Anda (`x-api-co-id`):</label>
            <input type="text" id="api_key" name="api_key" value="<?php echo htmlspecialchars($apiKey); ?>" placeholder="Contoh: eyJhbGciOi..." required autocomplete="off">
        </div>
        <button type="submit" id="btnDownload">Mulai / Lanjutkan Unduh</button>
    </form>
    
    <div id="progressContainer">
        <div id="progressBar">0%</div>
    </div>
    
    <div id="status"></div>

    <?php
    if ($isDownloading) {
        $baseUrl = "https://use.api.co.id/regional/indonesia/villages";
        $currentPage = 1;
        $totalPage = 1;
        $allData = [];

        function updateProgress($percent, $msg) {
            echo "<script>
                document.getElementById('progressBar').style.width = '{$percent}%';
                document.getElementById('progressBar').innerText = '{$percent}%';
                document.getElementById('status').innerHTML = '{$msg}';
            </script>";
            ob_flush();
            flush();
        }

        try {
            // LANGKAH 1: Ambil Halaman Pertama untuk mengetahui Total Halaman asli
            $firstPageFile = "{$cacheDir}/page_1.json";
            if (file_exists($firstPageFile)) {
                $firstPageData = json_decode(file_get_contents($firstPageFile), true);
                $totalPage = (int) $firstPageData['paging']['total_page'];
            } else {
                // Jika cache halaman 1 belum ada, lakukan curl
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "{$baseUrl}?page=1");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "x-api-co-id: {$apiKey}",
                    "Content-Type: application/json"
                ]);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode !== 200) {
                    throw new Exception("Gagal mengambil halaman 1. HTTP Status: {$httpCode}");
                }

                $result = json_decode($response, true);
                if (isset($result['is_success']) && $result['is_success']) {
                    // Simpan respon lengkap halaman 1 ke cache
                    file_put_contents($firstPageFile, $response);
                    $totalPage = (int) $result['paging']['total_page'];
                } else {
                    throw new Exception($result['message'] ?? "Format respon halaman 1 tidak valid.");
                }
            }

            // LANGKAH 2: Loop melalui seluruh halaman
            do {
                $cacheFile = "{$cacheDir}/page_{$currentPage}.json";

                // Jika file cache halaman ini sudah ada, lewati curl
                if (file_exists($cacheFile)) {
                    $currentPage++;
                    continue;
                }

                // Jika belum ada, lakukan request ke API
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "{$baseUrl}?page={$currentPage}");
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    "x-api-co-id: {$apiKey}",
                    "Content-Type: application/json"
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($httpCode !== 200) {
                    throw new Exception("Gagal di halaman {$currentPage}. HTTP Status: {$httpCode}. Anda bisa klik tombol 'Mulai / Lanjutkan Unduh' lagi nanti.");
                }

                $result = json_decode($response, true);
                if (isset($result['is_success']) && $result['is_success'] && !empty($result['data'])) {
                    // Simpan respon lengkap ke dalam file cache per halaman
                    file_put_contents($cacheFile, $response);

                    $percent = round(($currentPage / $totalPage) * 100);
                    $msg = "Mengunduh halaman {$currentPage} dari {$totalPage}...";
                    updateProgress($percent, $msg);

                    $currentPage++;
                    usleep(50000); // Jeda 50ms untuk rate-limiting
                } else {
                    throw new Exception($result['message'] ?? "Gagal mengambil data halaman {$currentPage}");
                }

            } while ($currentPage <= $totalPage);

            // LANGKAH 3: Gabungkan Semua File Cache Menjadi Satu JSON Utuh
            updateProgress(100, "Semua halaman selesai diunduh! Menggabungkan data...");

            for ($i = 1; $i <= $totalPage; $i++) {
                $file = "{$cacheDir}/page_{$i}.json";
                if (file_exists($file)) {
                    $pageData = json_decode(file_get_contents($file), true);
                    if (isset($pageData['data'])) {
                        $allData = array_merge($allData, $pageData['data']);
                    }
                }
            }

            // Simpan file JSON utama
            $jsonOutput = json_encode($allData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $filename = "semua_desa_indonesia.json";
            file_put_contents($filename, $jsonOutput);

            // Bersihkan folder cache setelah selesai agar tidak memakan memori disk
            array_map('unlink', glob("{$cacheDir}/*.*"));
            rmdir($cacheDir);

            echo "<script>
                document.getElementById('status').innerHTML = '<b>Selesai 100%!</b> <br>Total data berhasil digabungkan: " . count($allData) . " desa.<br><a href=\"{$filename}\" download style=\"color: #28a745; font-size: 16px;\"><b>Klik di sini untuk mendownload file JSON</b></a>';
            </script>";
            ob_flush();
            flush();

        } catch (Exception $e) {
            echo "<script>
                document.getElementById('status').style.color = 'red';
                document.getElementById('status').innerText = 'Kesalahan: " . addslashes($e->getMessage()) . "';
            </script>";
            ob_flush();
            flush();
        }
    }
    ?>
</div>

</body>