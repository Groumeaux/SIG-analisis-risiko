<?php
require_once 'config.php';

// Connect to Database
$pdo = getDBConnection();

// Helper function
function fetchData($pdo, $table) {
    try {
        $result = $pdo->query("SELECT * FROM $table");
        return $result->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// Fetch data
$banjirData = fetchData($pdo, 'banjir');
$longsorData = fetchData($pdo, 'longsor');
$sekolahData = fetchData($pdo, 'sekolah');
$rsData = fetchData($pdo, 'rs');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIG Tanggap Bencana Minahasa</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>

    <script>
        // Pass PHP data to JavaScript with Numeric Check
        window.appData = {
            banjir: <?php echo json_encode($banjirData, JSON_NUMERIC_CHECK); ?>,
            longsor: <?php echo json_encode($longsorData, JSON_NUMERIC_CHECK); ?>,
            sekolah: <?php echo json_encode($sekolahData, JSON_NUMERIC_CHECK); ?>,
            rs: <?php echo json_encode($rsData, JSON_NUMERIC_CHECK); ?>
        };
    </script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        #map { height: calc(100vh - 64px); }
        .leaflet-control-attribution { background: rgba(255,255,255,0.8) !important; }
        .leaflet-routing-container { display: none; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-gray-50 overflow-hidden">

    <nav class="bg-blue-900 text-white shadow-lg h-16 flex items-center justify-between px-6 z-50 relative">
        <div class="flex items-center space-x-3">
            <i data-lucide="map" class="w-6 h-6"></i>
            <div>
                <h1 class="font-bold text-lg leading-none">SIG Minahasa</h1>
                <p class="text-xs text-blue-200">Tanggap Bencana & Evakuasi</p>
            </div>
        </div>

        <div class="hidden md:flex items-center space-x-6">
            <button onclick="switchView('map')" class="hover:text-blue-300 transition text-sm font-medium">Peta Interaktif</button>
            <button onclick="switchView('info')" class="hover:text-blue-300 transition text-sm font-medium">Edukasi & Info</button>
            <button onclick="switchView('admin')" class="hover:text-yellow-400 transition text-sm font-medium text-yellow-300">Dashboard Admin</button>
        </div>

        <div class="flex items-center space-x-4">
            <a href="login.php" class="bg-blue-700 hover:bg-blue-600 px-4 py-1.5 rounded text-sm transition">Login Admin</a>
        </div>
    </nav>

    <main class="relative h-[calc(100vh-64px)] w-full">
        <div id="map-view" class="h-full w-full absolute top-0 left-0">
            <div id="map" class="h-full w-full z-10"></div>

            <div class="absolute top-4 left-4 z-20 bg-white p-5 rounded-lg shadow-xl w-[90%] max-w-sm max-h-[90%] flex flex-col overflow-hidden border-l-4 border-blue-600">
                <div id="layer-controls" class="overflow-y-auto no-scrollbar">
                    <h2 class="text-gray-800 font-bold text-lg mb-1">Lapisan Data</h2>
                    <p class="text-xs text-gray-500 mb-4">Pilih data yang ingin ditampilkan.</p>
                    
                    <div class="space-y-2">
                        <label class="flex items-center justify-between p-2 bg-red-50 rounded border border-red-100 cursor-pointer hover:bg-red-100 transition">
                            <div class="flex items-center gap-2">
                                <i data-lucide="cloud-rain" class="w-4 h-4 text-red-500"></i>
                                <span class="text-sm font-medium text-gray-700">Rawan Banjir</span>
                            </div>
                            <input type="checkbox" id="toggle-banjir" checked class="accent-red-500 w-4 h-4">
                        </label>
                        <label class="flex items-center justify-between p-2 bg-yellow-50 rounded border border-yellow-100 cursor-pointer hover:bg-yellow-100 transition">
                            <div class="flex items-center gap-2">
                                <i data-lucide="mountain" class="w-4 h-4 text-yellow-600"></i>
                                <span class="text-sm font-medium text-gray-700">Rawan Longsor</span>
                            </div>
                            <input type="checkbox" id="toggle-longsor" checked class="accent-yellow-500 w-4 h-4">
                        </label>
                        <label class="flex items-center justify-between p-2 bg-blue-50 rounded border border-blue-100 cursor-pointer hover:bg-blue-100 transition">
                            <div class="flex items-center gap-2">
                                <i data-lucide="school" class="w-4 h-4 text-blue-500"></i>
                                <span class="text-sm font-medium text-gray-700">Sekolah (Titik Kumpul)</span>
                            </div>
                            <input type="checkbox" id="toggle-sekolah" checked class="accent-blue-500 w-4 h-4">
                        </label>
                        <label class="flex items-center justify-between p-2 bg-green-50 rounded border border-green-100 cursor-pointer hover:bg-green-100 transition">
                            <div class="flex items-center gap-2">
                                <i data-lucide="hospital" class="w-4 h-4 text-green-500"></i>
                                <span class="text-sm font-medium text-gray-700">Rumah Sakit</span>
                            </div>
                            <input type="checkbox" id="toggle-rs" checked class="accent-green-500 w-4 h-4">
                        </label>
                    </div>
                </div>

                <div id="analysis-panel" class="hidden flex flex-col h-full">
                    <div class="flex justify-between items-start mb-3">
                        <h3 class="font-bold text-gray-800">Hasil Analisis</h3>
                        <button onclick="closeAnalysis()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
                    </div>
                    
                    <div id="risk-badge" class="bg-gray-200 text-gray-700 text-center py-3 rounded-lg mb-4">
                        <p class="text-xs uppercase tracking-widest font-semibold text-gray-600 mb-1">Tingkat Risiko</p>
                        <div id="risk-level-text" class="text-2xl font-bold leading-tight">MENGHITUNG...</div>
                    </div>

                    <div class="space-y-3 flex-grow overflow-y-auto no-scrollbar">
                        <div class="bg-blue-50 p-3 rounded-lg border border-blue-100 relative overflow-hidden">
                            <div class="absolute top-0 right-0 bg-blue-600 text-white text-[10px] px-2 py-0.5 rounded-bl">Rute Biru</div>
                            <p class="text-xs text-blue-600 font-bold uppercase">Posko Sekolah Terdekat</p>
                            <p class="font-semibold text-gray-800" id="res-school-name">-</p>
                            <p class="text-sm text-gray-600" id="res-school-dist">-</p>
                        </div>
                        <div class="bg-green-50 p-3 rounded-lg border border-green-100 relative overflow-hidden">
                             <div class="absolute top-0 right-0 bg-green-600 text-white text-[10px] px-2 py-0.5 rounded-bl">Rute Hijau</div>
                            <p class="text-xs text-green-600 font-bold uppercase">RS Terdekat</p>
                            <p class="font-semibold text-gray-800" id="res-rs-name">-</p>
                            <p class="text-sm text-gray-600" id="res-rs-dist">-</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="info-view" class="hidden h-full w-full bg-white overflow-y-auto">
            <div class="max-w-4xl mx-auto py-12 px-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Edukasi Bencana</h1>
                <p class="text-gray-500 mb-10">Panduan kesiapsiagaan.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="bg-red-50 p-6 rounded-xl border border-red-100">
                        <h2 class="text-xl font-bold text-gray-800 mb-2">Siaga Banjir</h2>
                        <ul class="space-y-2 text-gray-700 text-sm">
                            <li>• Matikan aliran listrik.</li>
                            <li>• Amankan dokumen penting.</li>
                            <li>• Evakuasi ke tempat tinggi.</li>
                        </ul>
                    </div>
                    <div class="bg-yellow-50 p-6 rounded-xl border border-yellow-100">
                        <h2 class="text-xl font-bold text-gray-800 mb-2">Siaga Longsor</h2>
                        <ul class="space-y-2 text-gray-700 text-sm">
                            <li>• Waspada retakan tanah.</li>
                            <li>• Jauhi tebing curam.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function switchView(viewId) {
            if(viewId === 'admin') { window.location.href = 'admin.php'; return; }
            document.getElementById('map-view').classList.add('hidden');
            document.getElementById('info-view').classList.add('hidden');
            document.getElementById(viewId + '-view').classList.remove('hidden');
            if(viewId === 'map' && map) setTimeout(() => map.invalidateSize(), 100);
        }

        // Map Init
        const map = L.map('map').setView([1.3113, 124.9078], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

        const layers = {
            banjir: L.layerGroup().addTo(map),
            longsor: L.layerGroup().addTo(map),
            sekolah: L.layerGroup().addTo(map),
            rs: L.layerGroup().addTo(map)
        };

        const icons = {
            banjir: L.divIcon({className: '', html: '<div style="background:red;width:16px;height:16px;border:2px solid white;border-radius:50%;box-shadow:0 0 5px rgba(0,0,0,0.5)"></div>'}),
            longsor: L.divIcon({className: '', html: '<div style="background:orange;width:16px;height:16px;border:2px solid white;border-radius:50%;box-shadow:0 0 5px rgba(0,0,0,0.5)"></div>'}),
            sekolah: L.divIcon({className: '', html: '<div style="background:blue;width:16px;height:16px;border:2px solid white;border-radius:50%;box-shadow:0 0 5px rgba(0,0,0,0.5)"></div>'}),
            rs: L.divIcon({className: '', html: '<div style="background:green;width:16px;height:16px;border:2px solid white;border-radius:50%;box-shadow:0 0 5px rgba(0,0,0,0.5)"></div>'})
        };

        function refreshLayers() {
            Object.values(layers).forEach(l => l.clearLayers());
            ['banjir', 'longsor', 'sekolah', 'rs'].forEach(type => {
                if(window.appData && Array.isArray(window.appData[type])) {
                    window.appData[type].forEach(item => {
                        const lat = parseFloat(item.lat);
                        const lng = parseFloat(item.lng);
                        if(!isNaN(lat) && !isNaN(lng)) {
                            L.marker([lat, lng], {icon: icons[type]})
                            .bindPopup(`<b>${item.nama}</b><br>${item.keterangan || ''}`)
                            .addTo(layers[type]);
                        }
                    });
                }
            });
        }
        
        document.getElementById('toggle-banjir').addEventListener('change', e => e.target.checked ? layers.banjir.addTo(map) : layers.banjir.remove());
        document.getElementById('toggle-longsor').addEventListener('change', e => e.target.checked ? layers.longsor.addTo(map) : layers.longsor.remove());
        document.getElementById('toggle-sekolah').addEventListener('change', e => e.target.checked ? layers.sekolah.addTo(map) : layers.sekolah.remove());
        document.getElementById('toggle-rs').addEventListener('change', e => e.target.checked ? layers.rs.addTo(map) : layers.rs.remove());

        let userMarker;
        let radiusCircle; // Variabel untuk lingkaran radius
        let routeControlSchool = null;
        let routeControlRS = null;
        
        map.on('click', function(e) {
            // 1. PINPOINT LOKASI & LINGKARAN RADIUS
            if(userMarker) map.removeLayer(userMarker);
            if(radiusCircle) map.removeLayer(radiusCircle); // Hapus lingkaran lama

            userMarker = L.marker(e.latlng).addTo(map);
            
            // Buat lingkaran visualisasi 2KM
            radiusCircle = L.circle(e.latlng, {
                color: 'red',       // Warna garis
                fillColor: '#f03',  // Warna isi
                fillOpacity: 0.1,   // Transparansi
                radius: 2000        // Radius dalam meter (2km)
            }).addTo(map);

            // 2. HITUNG RISIKO
            let riskCount = 0;
            const radiusKm = 2;
            const radiusMeter = radiusKm * 1000;

            ['banjir', 'longsor'].forEach(t => {
                if(window.appData[t]) window.appData[t].forEach(p => {
                    if(e.latlng.distanceTo([p.lat, p.lng]) <= radiusMeter) riskCount++;
                });
            });
            
            const riskText = document.getElementById('risk-level-text');
            const riskBadge = document.getElementById('risk-badge');
            
            if(riskCount === 0) { 
                riskText.innerHTML = `RENDAH<div class="text-sm font-normal mt-1 text-green-800">Aman (0 bencana dalam radius 2km)</div>`; 
                riskBadge.className = "bg-green-100 text-green-800 text-center py-3 rounded-lg mb-4 border border-green-200"; 
            }
            else if(riskCount < 3) { 
                riskText.innerHTML = `SEDANG<div class="text-sm font-normal mt-1 text-yellow-800">${riskCount} bencana terdeteksi dalam jangkauan 2km</div>`; 
                riskBadge.className = "bg-yellow-100 text-yellow-800 text-center py-3 rounded-lg mb-4 border border-yellow-200"; 
            }
            else { 
                riskText.innerHTML = `TINGGI<div class="text-sm font-normal mt-1 text-red-800">${riskCount} bencana terdeteksi dalam jangkauan 2km</div>`; 
                riskBadge.className = "bg-red-100 text-red-800 text-center py-3 rounded-lg mb-4 border border-red-200"; 
            }

            // 3. CARI TITIK TERDEKAT
            const findNearest = (data) => {
                if(!data || data.length === 0) return null;
                let nearest = null, minDist = Infinity;
                data.forEach(p => {
                    let dist = e.latlng.distanceTo([p.lat, p.lng]);
                    if(dist < minDist) { minDist = dist; nearest = {...p, dist: dist, latLng: L.latLng(p.lat, p.lng)}; }
                });
                return nearest;
            };

            const nSchool = findNearest(window.appData.sekolah);
            const nRS = findNearest(window.appData.rs);

            document.getElementById('res-school-name').innerText = nSchool ? nSchool.nama : '-';
            document.getElementById('res-school-dist').innerText = nSchool ? (nSchool.dist/1000).toFixed(2) + ' km' : '-';
            document.getElementById('res-rs-name').innerText = nRS ? nRS.nama : '-';
            document.getElementById('res-rs-dist').innerText = nRS ? (nRS.dist/1000).toFixed(2) + ' km' : '-';

            // 4. BUAT RUTE KE SEKOLAH (GARIS BIRU)
            if(routeControlSchool) map.removeControl(routeControlSchool);
            if(nSchool) {
                routeControlSchool = L.Routing.control({
                    waypoints: [e.latlng, nSchool.latLng],
                    lineOptions: { styles: [{color: '#2563eb', opacity: 0.8, weight: 6}] }, // Biru Tebal
                    show: false,
                    createMarker: () => null
                }).on('routesfound', function(r) {
                     const d = r.routes[0].summary.totalDistance;
                     const time = Math.round(r.routes[0].summary.totalTime / 60);
                     document.getElementById('res-school-dist').innerText = `${(d/1000).toFixed(2)} km (Rute: ${time} mnt)`;
                }).addTo(map);
            }

            // 5. BUAT RUTE KE RS (GARIS HIJAU)
            if(routeControlRS) map.removeControl(routeControlRS);
            if(nRS) {
                routeControlRS = L.Routing.control({
                    waypoints: [e.latlng, nRS.latLng],
                    lineOptions: { styles: [{color: '#16a34a', opacity: 0.8, weight: 6}] }, // Hijau Tebal
                    show: false,
                    createMarker: () => null
                }).on('routesfound', function(r) {
                     const d = r.routes[0].summary.totalDistance;
                     const time = Math.round(r.routes[0].summary.totalTime / 60);
                     document.getElementById('res-rs-dist').innerText = `${(d/1000).toFixed(2)} km (Rute: ${time} mnt)`;
                }).addTo(map);
            }

            document.getElementById('layer-controls').classList.add('hidden');
            document.getElementById('analysis-panel').classList.remove('hidden');
        });

        function closeAnalysis() {
            document.getElementById('analysis-panel').classList.add('hidden');
            document.getElementById('layer-controls').classList.remove('hidden');
            if(userMarker) map.removeLayer(userMarker);
            if(radiusCircle) map.removeLayer(radiusCircle); // Bersihkan lingkaran
            if(routeControlSchool) map.removeControl(routeControlSchool);
            if(routeControlRS) map.removeControl(routeControlRS);
        }

        refreshLayers();
        lucide.createIcons();
    </script>
</body>
</html>