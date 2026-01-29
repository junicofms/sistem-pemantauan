<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem Pemantauan UPT PLN</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    <style>
        body { font-family: 'Instrument Sans', sans-serif; }
        #map { height: 500px; width: 100%; z-index: 1; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200 flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-4xl bg-white dark:bg-gray-800 shadow-xl rounded-2xl overflow-hidden">
        <div class="bg-indigo-600 p-6 text-white text-center">
            <h1 class="text-3xl font-bold">Sistem Pemantauan UPT PLN</h1>
            <p class="mt-2 text-indigo-100">Upload file point cloud untuk menghitung data jarak dan pemetaan.</p>
        </div>

        <div class="p-8">
            @if ($errors->any())
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('lidar.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                
                <!-- Coordinate Settings -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg border border-gray-200 dark:border-gray-600">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Sistem Koordinat</label>
                        <select name="coord_type" id="coord_type" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" onchange="toggleUtmFields()">
                            <option value="latlon">Latitude / Longitude</option>
                            <option value="utm">UTM (Universal Transverse Mercator)</option>
                        </select>
                    </div>
                    <div id="utm_zone_field" class="hidden">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">UTM Zone (Ex: 48)</label>
                        <input type="number" name="utm_zone" value="48" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div id="utm_hemi_field" class="hidden">
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Belahan Bumi</label>
                        <select name="utm_is_south" class="w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="1">Selatan (South) - Indonesia</option>
                            <option value="0">Utara (North)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Pilih File LiDAR (Format: Longitude Latitude TinggiPohon TinggiTower)</label>
                    <div class="flex items-center justify-center w-full">
                    <label for="dropzone-file" class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:hover:bg-gray-800 dark:bg-gray-700 hover:bg-gray-100 transition-colors">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <svg class="w-8 h-8 mb-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            <p class="text-sm text-gray-500"><span class="font-semibold">Klik untuk upload</span></p>
                        </div>
                        <input id="dropzone-file" type="file" name="lidar_file" class="hidden" required />
                    </label>
                </div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-4 rounded-lg transition duration-300 shadow-md">Proses Data</button>
            </form>

            @if (session('results'))
                <div class="mt-10 border-t border-gray-200 dark:border-gray-700 pt-8">
                    <h2 class="text-2xl font-semibold mb-6 text-gray-800 dark:text-white">Hasil Analisis</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <p class="text-xs text-gray-500 uppercase">Total Titik</p>
                            <p class="text-xl font-bold">{{ number_format(session('results')['total_points']) }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <p class="text-xs text-gray-500 uppercase">Tinggi Pohon (Avg)</p>
                            <p class="text-xl font-bold text-indigo-600">{{ session('results')['average_distance'] }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <p class="text-xs text-gray-500 uppercase">Tinggi Pohon (Min)</p>
                            <p class="text-xl font-bold text-green-600">{{ session('results')['min_distance'] }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <p class="text-xs text-gray-500 uppercase">Kedekatan (Max)</p>
                            <p class="text-xl font-bold text-red-600">{{ session('results')['max_distance'] }}</p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg md:col-span-2">
                            <p class="text-xs text-gray-500 uppercase mb-2">Titik Lokasi</p>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                <div>
                                    <p class="text-[10px] text-gray-500 uppercase">Koordinat</p>
                                    <p class="text-sm font-bold">{{ session('results')['max_distance_location'] }}</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-500 uppercase">Tinggi Pohon</p>
                                    <p class="text-sm font-bold text-green-600">{{ session('results')['max_distance_tree_height'] }} m</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-500 uppercase">Tinggi Tower</p>
                                    <p class="text-sm font-bold text-red-600">{{ session('results')['max_distance_tower_height'] }} m</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-gray-500 uppercase">Jarak Kedekatan</p>
                                    <p class="text-sm font-bold text-indigo-600">{{ session('results')['max_distance'] }} m</p>
                                </div>
                            </div>
                        </div>
                    </div>

                        <div class="mt-8">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Peta Sebaran Titik (Google Maps)</h3>
                            <div class="flex items-center gap-2">
                                <div id="measure-result" class="text-sm font-bold text-indigo-600 dark:text-indigo-400"></div>
                                <button type="button" id="measure-btn" onclick="toggleMeasure()" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg transition shadow-sm">
                                    Ukur Jarak Manual
                                </button>
                            </div>
                        </div>

                        <!-- Search Coordinates Bar -->
                        <div class="flex flex-wrap items-center gap-2 mb-3 p-2 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 shadow-sm">
                            <div class="flex-1 min-w-[150px]">
                                <label class="block text-[10px] uppercase text-gray-500 mb-1 font-bold">Lintang (Latitude)</label>
                                <input type="text" id="search-lat" placeholder="Contoh: -6.234" class="w-full p-2 text-xs border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <div class="flex-1 min-w-[150px]">
                                <label class="block text-[10px] uppercase text-gray-500 mb-1 font-bold">Bujur (Longitude)</label>
                                <input type="text" id="search-lon" placeholder="Contoh: 106.845" class="w-full p-2 text-xs border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-md focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <button type="button" onclick="searchLocation()" class="mt-5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-md transition flex items-center gap-1 h-9">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                Cari Titik
                            </button>
                        </div>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                Cari Lokasi
                            </button>
                        </div>

                        <div id="map" class="rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm"></div>
                        <p class="mt-2 text-xs text-gray-500 italic">* Jarak horizontal antara pohon dan tower dihitung otomatis oleh sistem.</p>
                    </div>

                    <div class="mt-8">
                        <h3 class="text-lg font-semibold mb-4">Sampel Data Objek LiDAR</h3>
                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 text-xs">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        <th class="px-4 py-2 text-left">No</th>
                                        <th class="px-4 py-2 text-left">Lintang (Lat)</th>
                                        <th class="px-4 py-2 text-left">Bujur (Lon)</th>
                                        <th class="px-4 py-2 text-left">Tinggi Tower</th>
                                        <th class="px-4 py-2 text-left">Tinggi Pohon</th>
                                        <th class="px-4 py-2 text-left">Kedekatan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach (session('results')['raw_data'] as $index => $obj)
                                        @if($index < 50)
                                        <tr onclick="flyToLocation(this)" data-lat="{{ $obj['centroid_lat'] }}" data-lon="{{ $obj['centroid_lon'] }}" class="hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors" style="cursor: pointer;">
                                            <td class="px-4 py-2">{{ $index + 1 }}</td>
                                            <td class="px-4 py-2">{{ number_format($obj['centroid_lat'], 6) }}</td>
                                            <td class="px-4 py-2">{{ number_format($obj['centroid_lon'], 6) }}</td>
                                            <td class="px-4 py-2 text-red-600 font-medium">
                                                {{ $obj['type'] == 'tower' ? number_format($obj['height'], 2) : number_format($obj['nearest_tower_height'], 2) }} m
                                            </td>
                                            <td class="px-4 py-2 text-green-600 font-medium">
                                                {{ $obj['type'] == 'tree' ? number_format($obj['height'], 2) : '-' }}
                                            </td>
                                            <td class="px-4 py-2 text-indigo-600 font-bold">
                                                {{ $obj['distance_to_tower'] > 0 ? number_format($obj['distance_to_tower'], 2) . ' m' : '-' }}
                                            </td>
                                        </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 italic text-right">* Tabel menampilkan 50 objek pertama. Seluruh objek ditampilkan di peta.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
    
        <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}&libraries=drawing,geometry"></script>
    
    <script>
        function toggleUtmFields() {
            const type = document.getElementById('coord_type').value;
            const zoneField = document.getElementById('utm_zone_field');
            const hemiField = document.getElementById('utm_hemi_field');
            
            if (type === 'utm') {
                zoneField.classList.remove('hidden');
                hemiField.classList.remove('hidden');
            } else {
                zoneField.classList.add('hidden');
                hemiField.classList.add('hidden');
            }
        }

        const fileInput = document.getElementById('dropzone-file');
        fileInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                const fileName = this.files[0].name;
                const textContainer = this.previousElementSibling;
                const p = textContainer.querySelector('p.text-sm');
                p.innerHTML = `<span class="font-semibold text-indigo-500">${fileName}</span> siap diupload`;
            }
        });

        // Map and Measurement Logic
        let map;
        let measurementMode = false;
        let drawingManager;
        let measureLine = null;

        function toggleMeasure() {
            measurementMode = !measurementMode;
            const btn = document.getElementById('measure-btn');
            
            if (measurementMode) {
                btn.classList.add('bg-red-500');
                btn.classList.remove('bg-indigo-600');
                btn.innerText = 'Batal / Reset';
                drawingManager.setDrawingMode(google.maps.drawing.OverlayType.POLYLINE);
            } else {
                resetMeasure();
                btn.classList.add('bg-indigo-600');
                btn.classList.remove('bg-red-500');
                btn.innerText = 'Ukur Jarak Manual';
                drawingManager.setDrawingMode(null);
            }
        }

        function resetMeasure() {
            if (measureLine) {
                measureLine.setMap(null);
                measureLine = null;
            }
            document.getElementById('measure-result').innerText = '';
        }

        let searchMarker = null;
        function searchLocation() {
            const rawLat = document.getElementById('search-lat').value.replace(',', '.').trim();
            const rawLon = document.getElementById('search-lon').value.replace(',', '.').trim();
            
            const lat = parseFloat(rawLat);
            const lon = parseFloat(rawLon);

            if (isNaN(lat) || isNaN(lon)) {
                alert('Silakan masukkan koordinat Latitude dan Longitude yang valid.');
                return;
            }

            if (!map) {
                alert('Peta belum diinisialisasi. Silakan upload data terlebih dahulu.');
                return;
            }

            const location = {lat: lat, lng: lon};
            map.setCenter(location);
            map.setZoom(19);

            if (searchMarker) searchMarker.setMap(null);
            searchMarker = new google.maps.Marker({
                position: location,
                map: map,
                title: `Lokasi Dicari (Lat: ${lat}, Lon: ${lon})`
            });
        }

        function flyToLocation(element) {
            const lat = parseFloat(element.getAttribute('data-lat'));
            const lon = parseFloat(element.getAttribute('data-lon'));

            if (isNaN(lat) || isNaN(lon) || !map) return;
            
            const location = {lat: lat, lng: lon};
            map.panTo(location);
            map.setZoom(19);

            const tempMarker = new google.maps.Marker({
                position: location,
                map: map,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 10,
                    fillColor: "#4f46e5",
                    fillOpacity: 0.6,
                    strokeColor: '#312e81',
                    strokeWeight: 1
                }
            });

            setTimeout(() => {
                tempMarker.setMap(null);
            }, 2500);
        }

        @if(session('results'))
            document.addEventListener('DOMContentLoaded', function() {
                const objects = @json(session('results')['raw_data']);
                
                if (objects.length > 0) {
                    const firstObj = objects[0];
                    map = new google.maps.Map(document.getElementById('map'), {
                        center: {lat: firstObj.centroid_lat, lng: firstObj.centroid_lon},
                        zoom: 16,
                        mapTypeId: 'satellite'
                    });

                    drawingManager = new google.maps.drawing.DrawingManager({
                        drawingMode: null,
                        drawingControl: false,
                        polylineOptions: {
                            strokeColor: '#FF0000',
                            strokeWeight: 3,
                            clickable: false,
                            editable: true,
                            zIndex: 1
                        }
                    });
                    drawingManager.setMap(map);
                    
                    google.maps.event.addListener(drawingManager, 'overlaycomplete', function(event) {
                        if (measureLine) measureLine.setMap(null);
                        
                        measureLine = event.overlay;
                        
                        const path = measureLine.getPath();
                        let totalDistance = google.maps.geometry.spherical.computeLength(path);

                        document.getElementById('measure-result').innerText = 'Jarak: ' + (totalDistance > 1000 ? (totalDistance/1000).toFixed(2) + ' km' : totalDistance.toFixed(2) + ' m');

                        google.maps.event.addListener(path, 'insert_at', () => {
                             totalDistance = google.maps.geometry.spherical.computeLength(path);
                             document.getElementById('measure-result').innerText = 'Jarak: ' + (totalDistance > 1000 ? (totalDistance/1000).toFixed(2) + ' km' : totalDistance.toFixed(2) + ' m');
                        });

                        google.maps.event.addListener(path, 'set_at', () => {
                             totalDistance = google.maps.geometry.spherical.computeLength(path);
                             document.getElementById('measure-result').innerText = 'Jarak: ' + (totalDistance > 1000 ? (totalDistance/1000).toFixed(2) + ' km' : totalDistance.toFixed(2) + ' m');
                        });
                        
                        drawingManager.setDrawingMode(null);
                        measurementMode = false;
                        const btn = document.getElementById('measure-btn');
                        btn.classList.add('bg-indigo-600');
                        btn.classList.remove('bg-red-500');
                        btn.innerText = 'Ukur Jarak Manual';
                    });

                    const towers = {};
                    const bounds = new google.maps.LatLngBounds();
                    const infowindow = new google.maps.InfoWindow();

                    objects.forEach(obj => {
                        const position = {lat: obj.centroid_lat, lng: obj.centroid_lon};
                        bounds.extend(position);

                        if (obj.type === 'tower') {
                            towers[obj.name] = { lat: obj.centroid_lat, lon: obj.centroid_lon, height: obj.height };

                            const marker = new google.maps.Marker({
                                position: position,
                                map: map,
                                icon: {
                                    path: google.maps.SymbolPath.CIRCLE,
                                    scale: 8,
                                    fillColor: "#ef4444",
                                    fillOpacity: 0.8,
                                    strokeColor: "#000",
                                    strokeWeight: 1
                                }
                            });

                            marker.addListener('click', () => {
                                infowindow.setContent(`
                                <div class="text-sm font-sans">
                                    <p class="font-bold text-red-600 mb-1 border-b border-gray-200 pb-1">${obj.name}</p>
                                    <p class="mt-1"><strong>Tinggi Tower:</strong> ${obj.height.toFixed(2)} m</p>
                                    <p class="text-xs text-gray-500 mt-1 italic">Titik Pusat Tower</p>
                                    <a href="https://www.google.com/maps/dir/?api=1&destination=${obj.centroid_lat},${obj.centroid_lon}" target="_blank" class="mt-2 inline-block bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-1 px-3 rounded-lg transition">
                                        Arahkan
                                    </a>
                                </div>`);
                                infowindow.open(map, marker);
                            });
                        }
                    });

                    objects.forEach(obj => {
                        if (obj.type === 'tree') {
                            const position = {lat: obj.centroid_lat, lng: obj.centroid_lon};
                            bounds.extend(position);

                            let towerInfoHtml = '';
                            if (obj.nearest_tower !== '-' && towers[obj.nearest_tower]) {
                                const t = towers[obj.nearest_tower];
                                towerInfoHtml = `<p><strong>Tinggi Tower:</strong> ${t.height.toFixed(2)} m</p>`;
                            }

                            const marker = new google.maps.Marker({
                                position: position,
                                map: map,
                                icon: {
                                    path: google.maps.SymbolPath.CIRCLE,
                                    scale: 6,
                                    fillColor: "#22c55e",
                                    fillOpacity: 0.8,
                                    strokeColor: "#000",
                                    strokeWeight: 1
                                }
                            });
                            
                            marker.addListener('click', () => {
                                infowindow.setContent(`
                                <div class="text-sm font-sans min-w-[150px]">
                                    <p class="font-bold text-indigo-700 mb-1 border-b border-gray-200 pb-1">
                                        ${obj.nearest_tower !== '-' ? obj.nearest_tower : 'Data Objek'}
                                    </p>
                                    <div class="space-y-1 mt-1">
                                        ${towerInfoHtml}
                                        <p><strong>Tinggi Pohon:</strong> ${obj.height.toFixed(2)} m</p>
                                        <p class="text-indigo-600"><strong>Kedekatan:</strong> ${obj.distance_to_tower.toFixed(2)} m</p>
                                    </div>
                                    <a href="https://www.google.com/maps/dir/?api=1&destination=${obj.centroid_lat},${obj.centroid_lon}" target="_blank" class="mt-2 inline-block bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-1 px-3 rounded-lg transition">
                                        Arahkan
                                    </a>
                                </div>`);
                                infowindow.open(map, marker);
                            });
                        }
                    });
                    
                    map.fitBounds(bounds);
                }
            });
        @endif
    </script>
</body>
</html>