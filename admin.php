<?php
require_once 'config.php';
requireLogin();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$pdo = getDBConnection();
$message = '';
$currentTab = $_GET['tab'] ?? 'banjir';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cat = $_POST['category'] ?? '';
    $act = $_POST['action'] ?? '';

    if (in_array($cat, ['banjir', 'longsor', 'sekolah', 'rs'])) {
        if ($act === 'add') {
            $nama = $_POST['nama'];
            $lat = $_POST['lat'];
            $lng = $_POST['lng'];
            $ket = $_POST['keterangan'];
            $id = generateId();
            
            if ($nama) {
                if ($cat === 'banjir') {
                    $sql = "INSERT INTO banjir (id, nama, lat, lng, keterangan, level, tanggal) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $pdo->prepare($sql)->execute([$id, $nama, $lat, $lng, $ket, $_POST['level'], date('Y-m-d')]);
                } elseif ($cat === 'longsor') {
                    $sql = "INSERT INTO longsor (id, nama, lat, lng, keterangan, level) VALUES (?, ?, ?, ?, ?, ?)";
                    $pdo->prepare($sql)->execute([$id, $nama, $lat, $lng, $ket, 'Tinggi']);
                } else {
                    $sql = "INSERT INTO $cat (id, nama, lat, lng, keterangan) VALUES (?, ?, ?, ?, ?)";
                    $pdo->prepare($sql)->execute([$id, $nama, $lat, $lng, $ket]);
                }
                $message = "Data disimpan.";
            }
        } elseif ($act === 'delete') {
            $pdo->prepare("DELETE FROM $cat WHERE id = ?")->execute([$_POST['id']]);
            $message = "Data dihapus.";
        }
    }
}

$data = $pdo->query("SELECT * FROM $currentTab ORDER BY nama ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin - SIG Minahasa</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-50">
    <nav class="bg-blue-900 text-white p-4 flex justify-between items-center">
        <div class="font-bold text-lg">Admin Dashboard</div>
        <div class="space-x-4 text-sm">
            <a href="index.php" class="hover:text-blue-300">Lihat Peta</a>
            <a href="?logout=1" class="bg-red-600 px-3 py-1 rounded">Logout</a>
        </div>
    </nav>

    <div class="max-w-6xl mx-auto p-6">
        <?php if($message): ?><div class="bg-green-100 text-green-800 p-3 rounded mb-4"><?= $message ?></div><?php endif; ?>

        <div class="flex space-x-2 mb-6">
            <?php foreach(['banjir', 'longsor', 'sekolah', 'rs'] as $t): ?>
                <a href="?tab=<?= $t ?>" class="px-4 py-2 rounded <?= $currentTab == $t ? 'bg-blue-600 text-white' : 'bg-white text-gray-600' ?>"><?= ucfirst($t) ?></a>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded shadow h-fit">
                <h3 class="font-bold mb-4">Tambah Data</h3>
                <form method="POST" class="space-y-3">
                    <input type="hidden" name="category" value="<?= $currentTab ?>">
                    <input type="hidden" name="action" value="add">
                    <input type="text" name="nama" placeholder="Nama Lokasi" required class="w-full border p-2 rounded">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" step="any" name="lat" placeholder="Latitude" required class="w-full border p-2 rounded">
                        <input type="number" step="any" name="lng" placeholder="Longitude" required class="w-full border p-2 rounded">
                    </div>
                    <?php if($currentTab === 'banjir'): ?>
                        <select name="level" class="w-full border p-2 rounded">
                            <option>Rendah</option><option selected>Sedang</option><option>Tinggi</option>
                        </select>
                    <?php endif; ?>
                    <textarea name="keterangan" placeholder="Keterangan" class="w-full border p-2 rounded"></textarea>
                    <button class="w-full bg-blue-600 text-white py-2 rounded">Simpan</button>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white rounded shadow overflow-hidden">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-3">Nama</th>
                            <th class="p-3">Koordinat</th>
                            <th class="p-3">Ket</th>
                            <th class="p-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($data)): ?><tr><td colspan="4" class="p-4 text-center text-gray-400">Kosong</td></tr><?php endif; ?>
                        <?php foreach($data as $row): ?>
                        <tr class="border-b">
                            <td class="p-3"><?= htmlspecialchars($row['nama']) ?></td>
                            <td class="p-3 text-xs font-mono"><?= number_format($row['lat'],4) ?>, <?= number_format($row['lng'],4) ?></td>
                            <td class="p-3 truncate max-w-xs"><?= htmlspecialchars($row['keterangan']) ?></td>
                            <td class="p-3 text-right">
                                <form method="POST" onsubmit="return confirm('Hapus?')" class="inline">
                                    <input type="hidden" name="category" value="<?= $currentTab ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button class="text-red-600 font-bold text-xs">HAPUS</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>