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
                    
                    <!-- Dynamic Statistics -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <p class="text-xs text-gray-500 uppercase">Total Baris Data</p>
                            <p class="text-xl font-bold">{{ number_format(session('results')['total_points']) }}</p>
                        </div>

                        @if(isset(session('results')['dynamic_stats']))
                            @foreach(session('results')['dynamic_stats'] as $statName => $stats)
                                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                                    <p class="text-xs text-gray-500 uppercase font-bold">{{ $statName }}</p>
                                    <div class="grid grid-cols-3 gap-1 mt-2 text-center">
                                        <div>
                                            <p class="text-[10px] text-gray-400">MIN</p>
                                            <p class="text-sm font-semibold text-green-600">{{ $stats['min'] }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-gray-400">AVG</p>
                                            <p class="text-base font-bold text-indigo-600">{{ $stats['avg'] }}</p>
                                        </div>
                                        <div>
                                            <p class="text-[10px] text-gray-400">MAX</p>
                                            <p class="text-sm font-semibold text-red-600">{{ $stats['max'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>

                    <!-- Dynamic Map Section -->
                    <div class="mt-8">
                        <div class="flex justify-between items-center mb-2">
                            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Peta Sebaran Titik</h3>
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
                        <div id="map" class="rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm"></div>
                        <p class="mt-2 text-xs text-gray-500 italic">* Peta akan menampilkan semua titik jika kolom Lintang & Bujur terdeteksi.</p>
                    </div>

                    <!-- Dynamic Data Table -->
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold mb-4">Data LiDAR</h3>
                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700 text-xs">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-800">
                                    <tr>
                                        @if(isset(session('results')['headers']))
                                            @foreach(session('results')['headers'] as $header)
                                                <th class="px-4 py-2 text-left font-bold">{{ $header }}</th>
                                            @endforeach
                                        @endif
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @if(isset(session('results')['data']))
                                        @php
                                            $latIndex = session('results')['lat_col_index'];
                                            $lonIndex = session('results')['lon_col_index'];
                                        @endphp
                                        @foreach (session('results')['data'] as $row)
                                            <tr 
                                                @if($latIndex !== null && $lonIndex !== null && isset($row[$latIndex]) && isset($row[$lonIndex]))
                                                    onclick="flyToLocation({{ floatval($row[$latIndex]) }}, {{ floatval($row[$lonIndex]) }})"
                                                    class="hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors cursor-pointer"
                                                @else
                                                    class="hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                                @endif
                                            >
                                                @foreach ($row as $cell)
                                                    <td class="px-4 py-2">{{ $cell }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 italic text-right">* Tabel menampilkan 50 baris pertama.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
    
        <!-- Google Maps API -->
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_api_key') }}&libraries=drawing,geometry"></script>
    
    <script>
        function toggleUtmFields() {
            // ... (existing function, no changes needed)
        }

        const fileInput = document.getElementById('dropzone-file');
        // ... (existing listener, no changes needed)

        let map;
        let drawingManager;
        let measureLine = null;
        let searchMarker = null;

        function toggleMeasure() {
            // ... (existing function, no changes needed)
        }

        function resetMeasure() {
            // ... (existing function, no changes needed)
        }

        function searchLocation() {
            // ... (existing function, no changes needed)
        }
        
        function flyToLocation(lat, lon) {
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

            setTimeout(() => { tempMarker.setMap(null); }, 2500);
        }

        @if(session('results'))
            document.addEventListener('DOMContentLoaded', function() {
                const results = @json(session('results'));
                // Primary lat/lon for table-to-map interaction and generic fallback
                const primaryLatIndex = results.lat_col_index;
                const primaryLonIndex = results.lon_col_index;
                
                // Specific coordinate sets for multi-point plotting
                const latDroneIndex = results.lat_drone_index;
                const lonDroneIndex = results.lon_drone_index;
                const latNsIndex = results.lat_ns_index;
                const lonNsIndex = results.lon_ns_index;
                
                const typeIndex = results.type_col_index;
                const headers = results.headers;
                const data = results.raw_data;

                // Check if any coordinates were found at all
                if (!data || !headers || (primaryLatIndex === null)) {
                    document.getElementById('map').innerHTML = '<div class="p-4 text-center text-gray-500">Tidak ada kolom koordinat yang dapat dikenali di file Anda.</div>';
                    return;
                }

                const firstRowWithCoords = data.find(row => !isNaN(parseFloat(row[primaryLatIndex])) && !isNaN(parseFloat(row[primaryLonIndex])));
                
                if (!firstRowWithCoords) {
                    document.getElementById('map').innerHTML = '<div class="p-4 text-center text-gray-500">Tidak ada data koordinat yang valid untuk ditampilkan di peta.</div>';
                    return;
                }

                map = new google.maps.Map(document.getElementById('map'), {
                    center: { lat: parseFloat(firstRowWithCoords[primaryLatIndex]), lng: parseFloat(firstRowWithCoords[primaryLonIndex]) },
                    zoom: 16,
                    mapTypeId: 'satellite'
                });
                
                const bounds = new google.maps.LatLngBounds();
                const infowindow = new google.maps.InfoWindow();

                const createMarker = (lat, lon, color, rowData) => {
                    const position = { lat: lat, lng: lon };
                    bounds.extend(position);
                    const marker = new google.maps.Marker({
                        position: position,
                        map: map,
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 6,
                            fillColor: color,
                            fillOpacity: 0.9,
                            strokeColor: "#000",
                            strokeWeight: 1
                        }
                    });
                    marker.addListener('click', () => {
                        let content = '<div class="text-sm font-sans min-w-[150px] space-y-1">';
                        headers.forEach((header, index) => {
                            content += `<div><strong>${header}:</strong> ${rowData[index]}</div>`;
                        });
                        content += `<a href="https://www.google.com/maps/dir/?api=1&destination=${lat},${lon}" target="_blank" class="mt-2 inline-block bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-1 px-3 rounded-lg transition">Arahkan</a>`;
                        content += `</div>`;
                        infowindow.setContent(content);
                        infowindow.open(map, marker);
                    });
                };
                
                const getMarkerColorByType = (type) => {
                    if (!type) return '#4f46e5'; // Default blue
                    const lowerType = type.toLowerCase();
                    if (['tower', 'tiang', 'tegakan'].some(k => lowerType.includes(k))) return '#ef4444'; // Red
                    if (['tree', 'pohon', 'span', 'vegetasi'].some(k => lowerType.includes(k))) return '#22c55e'; // Green
                    return '#4f46e5'; // Default blue
                };

                // Decide which plotting logic to use
                const hasSpecificCoords = latDroneIndex !== null || lonDroneIndex !== null || latNsIndex !== null || lonNsIndex !== null;

                if (hasSpecificCoords) {
                    // --- MULTI-POINT PLOTTING LOGIC ---
                    data.forEach(row => {
                        // Plot Drone Marker
                        if (latDroneIndex !== null && lonDroneIndex !== null) {
                            const lat = parseFloat(row[latDroneIndex]);
                            const lon = parseFloat(row[lonDroneIndex]);
                            if (!isNaN(lat) && !isNaN(lon)) createMarker(lat, lon, '#4285F4', row); // Google Blue for Drone
                        }
                        // Plot NS Marker
                        if (latNsIndex !== null && lonNsIndex !== null) {
                            const lat = parseFloat(row[latNsIndex]);
                            const lon = parseFloat(row[lonNsIndex]);
                            if (!isNaN(lat) && !isNaN(lon)) createMarker(lat, lon, '#9c27b0', row); // Purple for NS
                        }
                    });
                } else {
                    // --- GENERIC SINGLE-POINT PLOTTING LOGIC ---
                    data.forEach(row => {
                        const lat = parseFloat(row[primaryLatIndex]);
                        const lon = parseFloat(row[primaryLonIndex]);
                        if (!isNaN(lat) && !isNaN(lon)) {
                            const type = typeIndex !== null ? row[typeIndex] : null;
                            const color = getMarkerColorByType(type);
                            createMarker(lat, lon, color, row);
                        }
                    });
                }

                if (bounds.isEmpty()) {
                     document.getElementById('map').innerHTML = '<div class="p-4 text-center text-gray-500">Meskipun kolom koordinat ditemukan, tidak ada baris dengan nilai numerik yang valid.</div>';
                } else {
                    map.fitBounds(bounds);
                }
            });
        @endif
    </script>
</body>
</html>