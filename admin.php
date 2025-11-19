<?php
require_once 'config.php';
requireLogin();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category = $_POST['category'] ?? '';
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $nama = trim($_POST['nama'] ?? '');
        $lat = floatval($_POST['lat'] ?? 0);
        $lng = floatval($_POST['lng'] ?? 0);
        $keterangan = trim($_POST['keterangan'] ?? '');

        if ($nama && $lat && $lng) {
            $filePath = '';
            switch ($category) {
                case 'banjir': $filePath = BANJIR_FILE; break;
                case 'longsor': $filePath = LONGSOR_FILE; break;
                case 'sekolah': $filePath = SEKOLAH_FILE; break;
                case 'rs': $filePath = RS_FILE; break;
            }

            if ($filePath) {
                $data = loadJsonData($filePath);
                $newItem = [
                    'id' => generateId(),
                    'nama' => $nama,
                    'lat' => $lat,
                    'lng' => $lng,
                    'keterangan' => $keterangan
                ];

                if ($category === 'banjir') {
                    $newItem['level'] = $_POST['level'] ?? 'Sedang';
                    $newItem['tanggal'] = date('Y-m-d');
                }

                $data[] = $newItem;
                saveJsonData($filePath, $data);
                $message = 'Data berhasil ditambahkan!';
                $messageType = 'success';
            }
        } else {
            $message = 'Semua field harus diisi!';
            $messageType = 'error';
        }
    } elseif ($action === 'delete' && isset($_POST['id'])) {
        $id = $_POST['id'];
        $filePath = '';
        switch ($category) {
            case 'banjir': $filePath = BANJIR_FILE; break;
            case 'longsor': $filePath = LONGSOR_FILE; break;
            case 'sekolah': $filePath = SEKOLAH_FILE; break;
            case 'rs': $filePath = RS_FILE; break;
        }

        if ($filePath) {
            $data = loadJsonData($filePath);
            $data = array_filter($data, function($item) use ($id) {
                return $item['id'] !== $id;
            });
            $data = array_values($data); // Reindex array
            saveJsonData($filePath, $data);
            $message = 'Data berhasil dihapus!';
            $messageType = 'success';
        }
    }
}

// Load data for display
$banjirData = loadJsonData(BANJIR_FILE);
$longsorData = loadJsonData(LONGSOR_FILE);
$sekolahData = loadJsonData(SEKOLAH_FILE);
$rsData = loadJsonData(RS_FILE);

$currentTab = $_GET['tab'] ?? 'banjir';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - SIG Minahasa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-50">

    <!-- Navbar -->
    <nav class="bg-blue-900 text-white shadow-lg h-16 flex items-center justify-between px-6">
        <div class="flex items-center space-x-3">
            <i data-lucide="map" class="w-6 h-6"></i>
            <div>
                <h1 class="font-bold text-lg leading-none">SIG Minahasa</h1>
                <p class="text-xs text-blue-200">Dashboard Admin</p>
            </div>
        </div>

        <div class="flex items-center space-x-4">
            <span class="text-sm">Selamat datang, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            <a href="index.php" class="text-blue-200 hover:text-white text-sm">← Kembali ke Peta</a>
            <a href="?logout=1" class="bg-red-600 hover:bg-red-500 px-4 py-1.5 rounded text-sm transition">Logout</a>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-8 px-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Dashboard Admin</h2>
                <p class="text-gray-500">Kelola data bencana dan fasilitas keselamatan.</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded <?php echo $messageType === 'success' ? 'bg-green-50 border-green-200 text-green-700' : 'bg-red-50 border-red-200 text-red-700'; ?> border">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="flex space-x-1 bg-white p-1 rounded-lg shadow-sm w-fit mb-6">
            <a href="?tab=banjir" class="px-4 py-2 rounded-md text-sm font-medium <?php echo $currentTab === 'banjir' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100'; ?>">Banjir</a>
            <a href="?tab=longsor" class="px-4 py-2 rounded-md text-sm font-medium <?php echo $currentTab === 'longsor' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100'; ?>">Longsor</a>
            <a href="?tab=sekolah" class="px-4 py-2 rounded-md text-sm font-medium <?php echo $currentTab === 'sekolah' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100'; ?>">Sekolah</a>
            <a href="?tab=rs" class="px-4 py-2 rounded-md text-sm font-medium <?php echo $currentTab === 'rs' ? 'bg-blue-100 text-blue-700' : 'text-gray-600 hover:bg-gray-100'; ?>">Rumah Sakit</a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 h-fit">
                <h3 class="text-lg font-semibold mb-4">Tambah Data <?php echo ucfirst($currentTab); ?></h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="category" value="<?php echo $currentTab; ?>">
                    <input type="hidden" name="action" value="add">

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Nama Lokasi/Kejadian</label>
                        <input type="text" name="nama" required class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Latitude</label>
                            <input type="number" step="any" name="lat" required class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Longitude</label>
                            <input type="number" step="any" name="lng" required class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                    </div>

                    <?php if ($currentTab === 'banjir'): ?>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Tingkat Risiko</label>
                            <select name="level" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="Rendah">Rendah</option>
                                <option value="Sedang" selected>Sedang</option>
                                <option value="Tinggi">Tinggi</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Keterangan Tambahan</label>
                        <textarea name="keterangan" rows="3" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none"></textarea>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded text-sm transition">Simpan Data</button>
                </form>

                <div class="mt-4 p-3 bg-blue-50 rounded border border-blue-100 text-xs text-blue-800">
                    <span class="font-bold">Tips:</span> Gunakan Google Maps untuk mendapatkan koordinat Lat/Lng yang akurat di wilayah Minahasa.
                </div>
            </div>

            <!-- Table -->
            <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="bg-gray-50 text-gray-600 font-medium border-b">
                            <tr>
                                <th class="px-4 py-3">Nama</th>
                                <th class="px-4 py-3">Koordinat</th>
                                <th class="px-4 py-3">Keterangan</th>
                                <?php if ($currentTab === 'banjir'): ?>
                                    <th class="px-4 py-3">Level</th>
                                <?php endif; ?>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php
                            $currentData = [];
                            switch ($currentTab) {
                                case 'banjir': $currentData = $banjirData; break;
                                case 'longsor': $currentData = $longsorData; break;
                                case 'sekolah': $currentData = $sekolahData; break;
                                case 'rs': $currentData = $rsData; break;
                            }

                            if (empty($currentData)): ?>
                                <tr>
                                    <td colspan="<?php echo $currentTab === 'banjir' ? '5' : '4'; ?>" class="px-4 py-8 text-center text-gray-400">
                                        Belum ada data untuk kategori ini.
                                    </td>
                                </tr>
                            <?php else:
                                foreach ($currentData as $item): ?>
                                    <tr class="bg-white hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900"><?php echo htmlspecialchars($item['nama']); ?></td>
                                        <td class="px-4 py-3 text-gray-500 text-xs font-mono"><?php echo number_format($item['lat'], 4); ?>, <?php echo number_format($item['lng'], 4); ?></td>
                                        <td class="px-4 py-3 text-gray-500 truncate max-w-xs"><?php echo htmlspecialchars($item['keterangan'] ?? '-'); ?></td>
                                        <?php if ($currentTab === 'banjir'): ?>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-1 text-xs rounded <?php
                                                    echo $item['level'] === 'Tinggi' ? 'bg-red-100 text-red-800' :
                                                         ($item['level'] === 'Sedang' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800');
                                                ?>">
                                                    <?php echo htmlspecialchars($item['level']); ?>
                                                </span>
                                            </td>
                                        <?php endif; ?>
                                        <td class="px-4 py-3 text-right">
                                            <form method="POST" class="inline" onsubmit="return confirm('Yakin hapus data ini?')">
                                                <input type="hidden" name="category" value="<?php echo $currentTab; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-bold bg-red-50 px-2 py-1 rounded">HAPUS</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach;
                            endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
