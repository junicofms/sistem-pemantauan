<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UtmConverter;
use App\Services\LidarProcessingService;

class LidarController extends Controller
{
    public function index()
    {
        return view('welcome');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'lidar_file' => 'required|file|max:10240',
            'coord_type' => 'required|in:latlon,utm',
            'utm_zone' => 'nullable|required_if:coord_type,utm|integer',
            'utm_is_south' => 'nullable|required_if:coord_type,utm|boolean',
        ]);

        $file = $request->file('lidar_file');
        $coordType = $request->input('coord_type');
        $utmZone = $request->input('utm_zone');
        $utmIsSouth = $request->input('utm_is_south');

        $path = $file->getRealPath();
        $rawPoints = [];
        $directObjects = [];
        $isObjectFormat = false;
        $lineCount = 0;

        if (($handle = fopen($path, 'r')) !== false) {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);
                if (empty($line) || str_starts_with($line, '#') || str_starts_with($line, '/')) continue;

                $parts = preg_split('/[\s,]+/', $line);
                $colCount = count($parts);

                if ($colCount >= 3) {
                    // 1. Ambil angka mentah
                    $v1 = floatval($parts[0]);
                    $v2 = floatval($parts[1]);
                    
                    // 2. Tentukan Lat/Lon (Logika Indonesia: Lat ~ -6, Lon ~ 106)
                    if ($coordType === 'utm') {
                        $converted = UtmConverter::toLatLng($v1, $v2, $utmZone, $utmIsSouth);
                        $lat = $converted['latitude'];
                        $lon = $converted['longitude'];
                    } else {
                        // Otomatis: Nilai absolut lebih kecil adalah Latitude
                        if (abs($v1) < abs($v2)) {
                            $lat = $v1; $lon = $v2;
                        } else {
                            $lat = $v2; $lon = $v1;
                        }
                    }

                    // 3. Proses berdasarkan format kolom
                    if ($lineCount === 0 && $colCount >= 4) $isObjectFormat = true;

                    if ($isObjectFormat && $colCount >= 4) {
                        $tp = floatval($parts[2]);
                        $tt = floatval($parts[3]);
                        $dist = abs($tt - $tp);
                        
                        $directObjects[] = [
                            'id' => $lineCount + 1,
                            'centroid_lat' => $lat,
                            'centroid_lon' => $lon,
                            'height' => $tp,
                            'type' => 'tree', 
                            'name' => 'Pohon ' . ($lineCount + 1),
                            'nearest_tower' => 'Tower Ref',
                            'nearest_tower_height' => $tt,
                            'distance_to_tower' => $dist
                        ];
                    } else {
                        $rawPoints[] = [
                            'latitude' => $lat,
                            'longitude' => $lon,
                            'elevation' => floatval($parts[2])
                        ];
                    }
                    $lineCount++;
                }
            }
            fclose($handle);
        }

        if (empty($rawPoints) && empty($directObjects)) {
            return back()->withErrors(['msg' => 'File tidak valid atau kosong.']);
        }

        // Proses Final Objek
        $processor = new LidarProcessingService();
        $processed = $isObjectFormat ? $directObjects : $processor->process($rawPoints);

        // Hitung Statistik
        $trees = array_filter($processed, fn($o) => $o['type'] === 'tree');
        $towers = array_filter($processed, fn($o) => $o['type'] === 'tower');
        $distList = array_filter(array_column($trees, 'distance_to_tower'), fn($d) => $d > 0);

        $maxDist = !empty($distList) ? max($distList) : 0;
        
        $stats = [
            'total_points' => $lineCount,
            'total_objects' => count($processed),
            'min_distance' => !empty($distList) ? min($distList) : 0,
            'max_distance' => $maxDist,
            'average_distance' => !empty($distList) ? array_sum($distList) / count($distList) : 0,
            'max_distance_location' => '-',
            'max_distance_tree_height' => 0,
            'max_distance_tower_height' => 0,
            'raw_data' => $processed, 
        ];

        // Temukan lokasi titik terjauh
        foreach ($trees as $tree) {
            if ($tree['distance_to_tower'] == $maxDist && $maxDist > 0) {
                $stats['max_distance_location'] = "Lat: " . round($tree['centroid_lat'], 6) . ", Lon: " . round($tree['centroid_lon'], 6);
                $stats['max_distance_tree_height'] = $tree['height'];
                
                if ($isObjectFormat) {
                    $stats['max_distance_tower_height'] = $tree['nearest_tower_height'];
                } else {
                    foreach ($towers as $t) {
                        if ($t['name'] === $tree['nearest_tower']) {
                            $stats['max_distance_tower_height'] = $t['height'];
                            break;
                        }
                    }
                }
                break;
            }
        }

        // Formatting untuk tampilan
        $stats['min_distance'] = number_format($stats['min_distance'], 2);
        $stats['max_distance'] = number_format($stats['max_distance'], 2);
        $stats['average_distance'] = number_format($stats['average_distance'], 2);
        $stats['max_distance_tree_height'] = number_format($stats['max_distance_tree_height'], 2);
        $stats['max_distance_tower_height'] = number_format($stats['max_distance_tower_height'], 2);

        return back()->with('results', $stats);
    }
}