<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UtmConverter;
use App\Services\LidarProcessingService;
use Maatwebsite\Excel\Facades\Excel;

class LidarController extends Controller
{
    public function index()
    {
        return view('welcome');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'lidar_file' => 'required|file|max:10240|mimes:txt,csv,xlsx',
            'coord_type' => 'required|in:latlon,utm',
            'utm_zone' => 'nullable|required_if:coord_type,utm|integer',
            'utm_is_south' => 'nullable|required_if:coord_type,utm|boolean',
        ]);

        $file = $request->file('lidar_file');
        
        $data = [];
        $extension = $file->getClientOriginalExtension();

        try {
            if (in_array($extension, ['csv', 'xlsx'])) {
                $collection = Excel::toCollection(null, $file->getRealPath())->first();
                if ($collection) {
                    $data = $collection->map(fn($row) => $row->toArray())->toArray();
                }
            } elseif ($extension === 'txt') {
                $path = $file->getRealPath();
                if (($handle = fopen($path, 'r')) !== false) {
                    while (($line = fgets($handle)) !== false) {
                        $line = trim($line);
                        if (!empty($line) && !str_starts_with($line, '#') && !str_starts_with($line, '/')) {
                            // Use str_getcsv to handle comma or space separated values
                            $data[] = str_getcsv($line, ' ');
                        }
                    }
                    fclose($handle);
                }
            } else {
                return back()->withErrors(['msg' => 'Unsupported file type.']);
            }
        } catch (\Exception $e) {
            return back()->withErrors(['msg' => 'Error reading file: ' . $e->getMessage()]);
        }
        
        if (empty($data)) {
            return back()->withErrors(['msg' => 'File does not contain any data or is in an unsupported format.']);
        }
        
        // Treat the first row as headers and the rest as data
        $headers = array_shift($data);
        
        // --- DYNAMIC STATISTICS CALCULATION ---
        $dynamicStats = [];
        $statKeywords = ['tinggi', 'height', 'jarak', 'distance', 'kedekatan', 'elevation'];

        foreach ($headers as $index => $header) {
            $headerLower = strtolower($header);
            $isStatColumn = false;
            foreach ($statKeywords as $keyword) {
                if (str_contains($headerLower, $keyword)) {
                    $isStatColumn = true;
                    break;
                }
            }

            if ($isStatColumn) {
                $columnData = array_column($data, $index);
                $numericData = array_filter($columnData, 'is_numeric');

                if (!empty($numericData)) {
                    $dynamicStats[$header] = [
                        'avg' => number_format(array_sum($numericData) / count($numericData), 2),
                        'min' => number_format(min($numericData), 2),
                        'max' => number_format(max($numericData), 2),
                    ];
                }
            }
        }
        // --- END DYNAMIC STATISTICS ---

        // --- ADVANCED DYNAMIC MAPPING (v2) ---
        $lat_col_index = null;
        $lon_col_index = null;
        $lat_drone_index = null;
        $lon_drone_index = null;
        $lat_ns_index = null;
        $lon_ns_index = null;
        $lat_generic_index = null; // New generic index
        $lon_generic_index = null; // New generic index
        $type_col_index = null;

        $keywords = [
            'lat_drone' => ['latitude drone', 'lat drone'],
            'lon_drone' => ['longtitude drone', 'lon drone'],
            'lat_ns' => ['latitude ns', 'lat ns'],
            'lon_ns' => ['longtitude ns', 'lon ns'],
            'lat_generic' => ['latitude', 'lat', 'lintang'],
            'lon_generic' => ['longitude', 'longtitude', 'lon', 'long', 'bujur'],
            'type' => ['type', 'tipe', 'jenis', 'kategori'],
        ];

        foreach ($headers as $index => $header) {
            $headerLower = trim(strtolower($header));
            
            // Using str_contains for more flexible matching
            if (is_null($type_col_index) && $this->matches_keyword($headerLower, $keywords['type'])) $type_col_index = $index;
            if (is_null($lat_drone_index) && $this->matches_keyword($headerLower, $keywords['lat_drone'])) $lat_drone_index = $index;
            if (is_null($lon_drone_index) && $this->matches_keyword($headerLower, $keywords['lon_drone'])) $lon_drone_index = $index;
            if (is_null($lat_ns_index) && $this->matches_keyword($headerLower, $keywords['lat_ns'])) $lat_ns_index = $index;
            if (is_null($lon_ns_index) && $this->matches_keyword($headerLower, $keywords['lon_ns'])) $lon_ns_index = $index;
            
            // Check for generic, but don't overwrite specific ones if found in same header
            if (is_null($lat_generic_index) && $this->matches_keyword($headerLower, $keywords['lat_generic'])) $lat_generic_index = $index;
            if (is_null($lon_generic_index) && $this->matches_keyword($headerLower, $keywords['lon_generic'])) $lon_generic_index = $index;
        }
        
        // Set a primary lat/lon for general purpose use (e.g., flyToLocation) using a priority order
        $lat_col_index = $lat_drone_index ?? $lat_ns_index ?? $lat_generic_index;
        $lon_col_index = $lon_drone_index ?? $lon_ns_index ?? $lon_generic_index;
        // --- END ADVANCED DYNAMIC MAPPING ---

        // Limit the number of rows to be displayed for performance
        $displayData = array_slice($data, 0, 50);

        $stats = [
            'total_points' => count($data),
            'headers' => $headers,
            'data' => $displayData,
            'dynamic_stats' => $dynamicStats,
            'lat_col_index' => $lat_col_index,
            'lon_col_index' => $lon_col_index,
            'type_col_index' => $type_col_index,
            'lat_drone_index' => $lat_drone_index,
            'lon_drone_index' => $lon_drone_index,
            'lat_ns_index' => $lat_ns_index,
            'lon_ns_index' => $lon_ns_index,
            'raw_data' => $data 
        ];
        return back()->with('results', $stats);
    }

    private function matches_keyword($header, $keywords)
    {
        foreach ($keywords as $keyword) {
            if (str_contains($header, $keyword)) {
                return true;
            }
        }
        return false;
    }
}