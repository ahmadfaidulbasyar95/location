<?php
// Tingkatkan batas waktu eksekusi dan memori karena memproses data besar
set_time_limit(0);
ini_set('memory_limit', '1024M');

$sourceFile = 'semua_desa_indonesia.json';
$baseDir = __DIR__ . '/api/v1';

// Cek apakah file sumber ada
if (!file_exists($sourceFile)) {
    die("Error: File '{$sourceFile}' tidak ditemukan! Pastikan file tersebut berada di folder yang sama dengan skrip ini.\n");
}

echo "Membaca file sumber...\n";
$rawData = file_get_contents($sourceFile);
$villages = json_decode($rawData, true);

if (!is_array($villages)) {
    die("Error: Gagal membaca atau mengurai data JSON.\n");
}

// Keranjang untuk mengelompokkan data secara unik
$provinces = [];
$regencies = [];
$districts = [];
$villagesGrouped = [];

echo "Memproses dan mengelompokkan data wilayah...\n";

foreach ($villages as $item) {
    // 1. Ambil data Provinsi (Unik)
    if (!isset($provinces[$item['province_code']])) {
        $provinces[$item['province_code']] = [$item['province_code'], $item['province'] ];
    }

    // 2. Ambil data Kabupaten/Kota (Unik per Provinsi)
    $pCode = $item['province_code'];
    if (!isset($regencies[$pCode][$item['regency_code']])) {
        $regencies[$pCode][$item['regency_code']] = [$item['regency_code'], $item['regency'] ];
    }

    // 3. Ambil data Kecamatan (Unik per Kabupaten)
    $rCode = $item['regency_code'];
    if (!isset($districts[$rCode][$item['district_code']])) {
        $districts[$rCode][$item['district_code']] = [$item['district_code'], $item['district'] ];
    }

    // 4. Ambil data Desa (Dikelompokkan per Kecamatan)
    $dCode = $item['district_code'];
    $villagesGrouped[$dCode][] = [$item['code'], $item['name'] ];
}

// Fungsi bantu untuk membuat folder dan menulis file JSON
function saveJson($path, $data) {
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    // Menggunakan JSON_VALUES untuk mereset index array dari associative ke indexed array
    $formattedData = array_values($data);
    file_put_contents($path, json_encode($formattedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

echo "Menyimpan file-file JSON...\n";

// A. Simpan Provinsi -> api/v1/province.json
saveJson("{$baseDir}/province.json", $provinces);
echo "- Berhasil membuat api/v1/province.json\n";

// B. Simpan Kabupaten/Kota -> api/v1/regency/{province_code}.json
foreach ($regencies as $provinceCode => $regencyList) {
    saveJson("{$baseDir}/regency/{$provinceCode}.json", $regencyList);
}
echo "- Berhasil membuat file regency per provinsi.\n";

// C. Simpan Kecamatan -> api/v1/district/{regency_code}.json
foreach ($districts as $regencyCode => $districtList) {
    saveJson("{$baseDir}/district/{$regencyCode}.json", $districtList);
}
echo "- Berhasil membuat file district per kabupaten.\n";

// D. Simpan Desa/Kelurahan -> api/v1/village/{district_code}.json
foreach ($villagesGrouped as $districtCode => $villageList) {
    saveJson("{$baseDir}/village/{$districtCode}.json", $villageList);
}
echo "- Berhasil membuat file village per kecamatan.\n";

echo "\nSemua proses selesai! Struktur folder API berhasil dibuat.\n";