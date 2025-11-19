<?php
require_once 'config.php';

// Load data for JavaScript
$banjirData = loadJsonData(BANJIR_FILE);
$longsorData = loadJsonData(LONGSOR_FILE);
$sekolahData = loadJsonData(SEKOLAH_FILE);
$rsData = loadJsonData(RS_FILE);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIG Tanggap Bencana Minahasa</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <!-- Leaflet Routing Machine -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <script>
        // Initialize data from PHP
        window.appData = {
            banjir: <?php echo json_encode($banjirData); ?>,
            longsor: <?php echo json_encode($longsorData); ?>,
            sekolah: <?php echo json_encode($sekolahData); ?>,
            rs: <?php echo json_encode($rsData); ?>
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

    <!-- NAVBAR -->
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
            <button id="login-btn" onclick="window.location.href='login.php'" class="bg-blue-700 hover:bg-blue-600 px-4 py-1.5 rounded text-sm transition">Login Admin</button>
        </div>
    </nav>

    <!-- MAIN CONTENT AREA -->
    <main class="relative h-[calc(100vh-64px)] w-full">

        <!-- VIEW: MAP -->
        <div id="map-view" class="h-full w-full absolute top-0 left-0">
            <div id="map" class="h-full w-full z-10"></div>

            <!-- Sidebar Control -->
            <div class="absolute top-4 left-4 z-20 bg-white p-5 rounded-lg shadow-xl w-[90%] max-w-sm max-h-[90%] flex flex-col overflow-hidden border-l-4 border-blue-600">
                <!-- Layer Controls -->
                <div id="layer-controls" class="overflow-y-auto no-scrollbar">
                    <h2 class="text-gray-800 font-bold text-lg mb-1">Lapisan Data</h2>
                    <p class="text-xs text-gray-500 mb-4">Pilih data yang ingin ditampilkan.</p>

                    <div class="space-y-2">
                        <label class="flex items-center justify-between p-2 bg-red-50 rounded border border-red-100 cursor-pointer hover:bg-red-100 transition">
                            <div class="flex items-center gap-2">
                                <i data-lucide="cloud-rain" class="w-4 h-4 text-red-500"></i>
                                <div>
                                    <span class="text-sm font-medium text-gray-700">Rawan Banjir</span>
                                    <p class="text-xs text-gray-500">Area dengan risiko banjir tinggi akibat luapan sungai, drainase buruk, atau curah hujan ekstrem.</p>
                                </div>
                            </div>
                            <input type="checkbox" id="toggle-banjir" checked class="accent-red-500 w-4 h-4">
                        </label>
                        <label class="flex items-center justify-between p-2 bg-yellow-50 rounded border border-yellow-100 cursor-pointer hover:bg-yellow-100 transition">
                            <div class="flex items-center gap-2">
                                <i data-lucide="mountain" class="w-4 h-4 text-yellow-600"></i>
                                <div>
                                    <span class="text-sm font-medium text-gray-700">Rawan Longsor</span>
                                    <p class="text-xs text-gray-500">Area dengan risiko longsor tinggi akibat tebing labil, tanah berpasir, atau aktivitas manusia.</p>
                                </div>
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

                    <div class="mt-4 pt-4 border-t">
                         <p class="text-xs text-gray-500 italic">Klik pada peta untuk melakukan analisis risiko lokasi dan mencari rute evakuasi terdekat.</p>
                    </div>
                </div>

                <!-- Analysis Result Panel (Hidden initially) -->
                <div id="analysis-panel" class="hidden flex flex-col h-full">
                    <div class="flex justify-between items-start mb-3">
                        <h3 class="font-bold text-gray-800">Hasil Analisis Lokasi</h3>
                        <button onclick="closeAnalysis()" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-5 h-5"></i></button>
                    </div>

                    <div id="risk-badge" class="bg-gray-200 text-gray-700 text-center py-3 rounded-lg mb-4">
                        <p class="text-xs uppercase tracking-widest font-semibold">Tingkat Risiko</p>
                        <p class="text-2xl font-bold" id="risk-level-text">MENGHITUNG...</p>
                    </div>

                    <div class="space-y-3 flex-grow overflow-y-auto no-scrollbar">
                        <!-- Nearest School -->
                        <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
                            <div class="flex items-start gap-3">
                                <div class="bg-blue-200 p-2 rounded-full text-blue-700"><i data-lucide="school" class="w-5 h-5"></i></div>
                                <div>
                                    <p class="text-xs text-blue-600 font-bold uppercase">Posko Sekolah Terdekat</p>
                                    <p class="font-semibold text-gray-800 leading-tight" id="res-school-name">-</p>
                                    <p class="text-sm text-gray-600 mt-1" id="res-school-dist">Jarak: -</p>
                                </div>
                            </div>
                        </div>

                        <!-- Nearest Hospital -->
                        <div class="bg-green-50 p-3 rounded-lg border border-green-100">
                            <div class="flex items-start gap-3">
                                <div class="bg-green-200 p-2 rounded-full text-green-700"><i data-lucide="hospital" class="w-5 h-5"></i></div>
                                <div>
                                    <p class="text-xs text-green-600 font-bold uppercase">RS Terdekat</p>
                                    <p class="font-semibold text-gray-800 leading-tight" id="res-rs-name">-</p>
                                    <p class="text-sm text-gray-600 mt-1" id="res-rs-dist">Jarak: -</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- VIEW: EDUCATION / INFO -->
        <div id="info-view" class="hidden h-full w-full bg-white overflow-y-auto">
            <div class="max-w-4xl mx-auto py-12 px-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Edukasi Bencana & Informasi</h1>
                <p class="text-gray-500 mb-10">Panduan kesiapsiagaan untuk warga Minahasa.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="bg-red-50 p-6 rounded-xl border border-red-100">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="bg-red-100 p-2 rounded-full text-red-600"><i data-lucide="cloud-rain" class="w-6 h-6"></i></div>
                            <h2 class="text-xl font-bold text-gray-800">Siaga Banjir</h2>
                        </div>
                        <ul class="space-y-2 text-gray-700 text-sm">
                            <li class="flex gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> Matikan aliran listrik jika air mulai naik.</li>
                            <li class="flex gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> Amankan dokumen penting di tempat tinggi.</li>
                            <li class="flex gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> Pantau info dari BPBD Minahasa.</li>
                            <li class="flex gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> Segera evakuasi ke titik kumpul sekolah terdekat.</li>
                        </ul>
                    </div>

                    <div class="bg-yellow-50 p-6 rounded-xl border border-yellow-100">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="bg-yellow-100 p-2 rounded-full text-yellow-600"><i data-lucide="mountain" class="w-6 h-6"></i></div>
                            <h2 class="text-xl font-bold text-gray-800">Siaga Longsor</h2>
                        </div>
                        <ul class="space-y-2 text-gray-700 text-sm">
                            <li class="flex gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> Waspada retakan tanah setelah hujan lebat.</li>
                            <li class="flex gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> Jauhi area tebing curam.</li>
                            <li class="flex gap-2"><i data-lucide="check" class="w-4 h-4 text-green-500"></i> Jika terdengar gemuruh, segera lari ke area terbuka.</li>
                        </ul>
                    </div>
                </div>

                <div class="mt-12">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Tentang Sistem Ini</h2>
                    <p class="text-gray-600 leading-relaxed mb-4">
                        Aplikasi ini adalah implementasi dari Proposal "Sistem Informasi Tanggap Bencana Berbasis SIG" oleh Kelompok 5.
                        Sistem ini menggabungkan algoritma <strong>Brute-Force Search</strong> untuk pencarian lokasi terdekat secara cepat,
                        dan <strong>Contraction Hierarchies</strong> (via OSRM) untuk kalkulasi rute jalan raya yang akurat.
                    </p>
                    <p class="text-gray-600 leading-relaxed">
                        <strong>Tim Pengembang:</strong><br>
                        1. Fanuel J. Palandeng<br>
                        2. David V. Baridji<br>
                        3. Daud A. Lendo
                    </p>
                </div>
            </div>
        </div>

    </main>

    <script>
        // --- UI LOGIC ---
        function switchView(viewId) {
            if (viewId === 'admin') {
                window.location.href = 'login.php';
                return;
            }

            document.getElementById('map-view').classList.add('hidden');
            document.getElementById('info-view').classList.add('hidden');

            document.getElementById(viewId + '-view').classList.remove('hidden');

            // Leaflet resize fix when unhiding
            if (viewId === 'map' && map) {
                setTimeout(() => { map.invalidateSize(); }, 100);
            }
        }

        // --- MAP LOGIC ---
        // Center Map on Tondano, Minahasa
        const map = L.map('map').setView([1.3113, 124.9078], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // Layer Groups
        const layers = {
            banjir: L.layerGroup().addTo(map),
            longsor: L.layerGroup().addTo(map),
            sekolah: L.layerGroup().addTo(map),
            rs: L.layerGroup().addTo(map)
        };

        // Simple colored markers using divIcon (squares)
        const icons = {
            banjir: L.divIcon({
                className: 'custom-marker',
                html: '<div style="background-color: red; width: 20px; height: 20px; border: 2px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.5);"></div>',
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            }),
            longsor: L.divIcon({
                className: 'custom-marker',
                html: '<div style="background-color: orange; width: 20px; height: 20px; border: 2px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.5);"></div>',
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            }),
            sekolah: L.divIcon({
                className: 'custom-marker',
                html: '<div style="background-color: blue; width: 20px; height: 20px; border: 2px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.5);"></div>',
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            }),
            rs: L.divIcon({
                className: 'custom-marker',
                html: '<div style="background-color: green; width: 20px; height: 20px; border: 2px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.5);"></div>',
                iconSize: [20, 20],
                iconAnchor: [10, 10]
            })
        };

        function refreshMapLayers() {
            // Clear existing
            Object.values(layers).forEach(l => l.clearLayers());

            // Add from window.appData
            ['banjir', 'longsor', 'sekolah', 'rs'].forEach(type => {
                const data = window.appData[type];
                if(data && Array.isArray(data)) {
                    data.forEach(item => {
                        const marker = L.marker([item.lat, item.lng], { icon: icons[type] });
                        marker.bindPopup(`<b>${item.nama}</b><br>${item.keterangan || ''}`);
                        layers[type].addLayer(marker);
                    });
                }
            });
        }

        // Call after map loads
        map.whenReady(function() {
            refreshMapLayers();
        });

        // Layer Toggles
        document.getElementById('toggle-banjir').addEventListener('change', e => e.target.checked ? layers.banjir.addTo(map) : layers.banjir.remove());
        document.getElementById('toggle-longsor').addEventListener('change', e => e.target.checked ? layers.longsor.addTo(map) : layers.longsor.remove());
        document.getElementById('toggle-sekolah').addEventListener('change', e => e.target.checked ? layers.sekolah.addTo(map) : layers.sekolah.remove());
        document.getElementById('toggle-rs').addEventListener('change', e => e.target.checked ? layers.rs.addTo(map) : layers.rs.remove());

        // --- ALGORITHM: BRUTE FORCE NEAREST NEIGHBOR ---
        function findNearest(targetLatLng, collectionName) {
            const data = (window.appData && window.appData[collectionName]) ? window.appData[collectionName] : [];
            if (!data || data.length === 0) return null;

            let nearest = null;
            let minDist = Infinity;

            // Brute Force: Check every single point
            data.forEach(point => {
                const pointLatLng = L.latLng(point.lat, point.lng);
                const dist = targetLatLng.distanceTo(pointLatLng); // Meters
                if (dist < minDist) {
                    minDist = dist;
                    nearest = { ...point, distance: dist, latLng: pointLatLng };
                }
            });
            return nearest;
        }

        // --- INTERACTION LOGIC ---
        let userMarker = null;
        let routingControl = null;

        map.on('click', function(e) {
            const latlng = e.latlng;

            // 1. Mark Location
            if (userMarker) map.removeLayer(userMarker);
            userMarker = L.marker(latlng, { draggable: false }).addTo(map);

            // 2. Calculate Risk (Radius Analysis)
            const radius = 2000; // 2km
            let riskScore = 0;

            ['banjir', 'longsor'].forEach(type => {
                if(window.appData && window.appData[type]){
                    window.appData[type].forEach(p => {
                        if (latlng.distanceTo([p.lat, p.lng]) <= radius) riskScore++;
                    });
                }
            });

            const riskLabel = document.getElementById('risk-level-text');
            const riskBadge = document.getElementById('risk-badge');

            if (riskScore === 0) {
                riskLabel.innerHTML = `RENDAH<br><span class='text-sm font-normal'>Risiko rendah, aman untuk aktivitas normal</span><br><span class='text-xs text-gray-600'>(${riskScore} titik risiko dalam radius 2km)</span>`;
                riskBadge.className = "bg-green-100 text-green-800 text-center py-3 rounded-lg mb-4";
            } else if (riskScore < 3) {
                riskLabel.innerHTML = `SEDANG<br><span class='text-sm font-normal'>Risiko sedang, waspadai potensi bahaya</span><br><span class='text-xs text-gray-600'>(${riskScore} titik risiko dalam radius 2km)</span>`;
                riskBadge.className = "bg-yellow-100 text-yellow-800 text-center py-3 rounded-lg mb-4";
            } else {
                riskLabel.innerHTML = `TINGGI<br><span class='text-sm font-normal'>Risiko tinggi, segera evakuasi ke titik aman</span><br><span class='text-xs text-gray-600'>(${riskScore} titik risiko dalam radius 2km)</span>`;
                riskBadge.className = "bg-red-100 text-red-800 text-center py-3 rounded-lg mb-4";
            }

            // 3. Find Nearest Safe Points (Brute Force)
            const nearestSchool = findNearest(latlng, 'sekolah');
            const nearestRS = findNearest(latlng, 'rs');

            // Update UI
            document.getElementById('res-school-name').innerText = nearestSchool ? nearestSchool.nama : 'Tidak ditemukan';
            document.getElementById('res-school-dist').innerText = nearestSchool ? (nearestSchool.distance/1000).toFixed(2) + ' km (Garis Lurus)' : '-';

            document.getElementById('res-rs-name').innerText = nearestRS ? nearestRS.nama : 'Tidak ditemukan';
            document.getElementById('res-rs-dist').innerText = nearestRS ? (nearestRS.distance/1000).toFixed(2) + ' km (Garis Lurus)' : '-';

            // 4. Route to Nearest School (Contraction Hierarchies via OSRM)
            if (routingControl) map.removeControl(routingControl);

            if (nearestSchool) {
                routingControl = L.Routing.control({
                    waypoints: [ latlng, nearestSchool.latLng ],
                    routeWhileDragging: false,
                    show: false, // Hide default panel
                    createMarker: function() { return null; }, // Don't create extra markers
                    lineOptions: { styles: [{color: '#2563eb', opacity: 0.8, weight: 6}] }
                }).on('routesfound', function(e) {
                    const route = e.routes[0];
                    const distKm = (route.summary.totalDistance / 1000).toFixed(2);
                    const timeMin = Math.round(route.summary.totalTime / 60);
                    document.getElementById('res-school-dist').innerHTML = `<span class="font-bold text-blue-700">${distKm} km</span> via jalan raya (${timeMin} menit)`;
                }).addTo(map);
            }

            // Show Panel
            document.getElementById('layer-controls').classList.add('hidden');
            document.getElementById('analysis-panel').classList.remove('hidden');
        });

        function closeAnalysis() {
            document.getElementById('analysis-panel').classList.add('hidden');
            document.getElementById('layer-controls').classList.remove('hidden');
            if (userMarker) map.removeLayer(userMarker);
            if (routingControl) map.removeControl(routingControl);
        }

        // Initial Icon Render
        lucide.createIcons();
    </script>
</body>
</html>
