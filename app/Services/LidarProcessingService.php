<?php

namespace App\Services;

class LidarProcessingService
{
    /**
     * Radius in meters to consider points as part of the same object (Cluster).
     */
    protected $clusterRadius = 5.0;

    /**
     * Height threshold in meters. Objects taller than this are classified as 'Tower'.
     */
    protected $towerHeightThreshold = 30.0;

    /**
     * Main processing function.
     *
     * @param array $points Raw points [['latitude' => 1.23, 'longitude' => 104.5, 'elevation' => 10], ...]
     * @return array Processed objects and distances
     */
    public function process(array $points)
    {
        // 1. Group points into clusters (Objects)
        $clusters = $this->clusterPoints($points);

        // 2. Analyze clusters to determine physical properties (Height, Centroid)
        $objects = $this->analyzeClusters($clusters);

        // 3. Classify objects as 'tree' or 'tower'
        $classifiedObjects = $this->classifyObjects($objects);

        // 4. Calculate distances between Trees and Towers
        $results = $this->calculateObjectDistances($classifiedObjects);

        return $results;
    }

    /**
     * Public wrapper to calculate distances for pre-formed objects.
     * Useful when data is already grouped (e.g., from a 4-column CSV).
     */
    public function calculateDistancesOnly(array $objects)
    {
        return $this->calculateObjectDistances($objects);
    }

    /**
     * Simple clustering: Iterates points and adds them to a cluster if within radius.
     * Note: For very large datasets, a spatial index (QuadTree) would be better.
     */
    private function clusterPoints(array $points)
    {
        $clusters = [];

        foreach ($points as $point) {
            $added = false;

            // Try to fit point into an existing cluster
            foreach ($clusters as &$cluster) {
                // Check distance to the cluster's first point (simplified centroid for performance)
                $refPoint = $cluster[0];
                $dist = $this->haversineGreatCircleDistance(
                    $point['latitude'], $point['longitude'],
                    $refPoint['latitude'], $refPoint['longitude']
                );

                if ($dist <= $this->clusterRadius) {
                    $cluster[] = $point;
                    $added = true;
                    break;
                }
            }

            // If not fit, create new cluster
            if (!$added) {
                $clusters[] = [$point];
            }
        }

        return $clusters;
    }

    /**
     * Calculate height (Max Z - Min Z) and Centroid (Avg Lat/Lon) for each cluster.
     */
    private function analyzeClusters(array $clusters)
    {
        $analyzed = [];

        foreach ($clusters as $index => $points) {
            $minZ = null;
            $maxZ = null;
            $sumLat = 0;
            $sumLon = 0;
            $count = count($points);

            foreach ($points as $p) {
                $z = $p['elevation'];
                
                if ($minZ === null || $z < $minZ) $minZ = $z;
                if ($maxZ === null || $z > $maxZ) $maxZ = $z;

                $sumLat += $p['latitude'];
                $sumLon += $p['longitude'];
            }

            $height = $maxZ - $minZ;
            
            // Assume the object stands on the ground (Min Z)
            // Height of object is the relative height from its base.
            
            $analyzed[] = [
                'id' => $index + 1,
                'centroid_lat' => $sumLat / $count,
                'centroid_lon' => $sumLon / $count,
                'base_elevation' => $minZ,
                'max_elevation' => $maxZ,
                'height' => $height,
                'points_count' => $count
            ];
        }

        return $analyzed;
    }

    /**
     * Classify objects based on height.
     */
    private function classifyObjects(array $objects)
    {
        foreach ($objects as &$obj) {
            // Simple heuristic: Taller than threshold is likely a Tower
            if ($obj['height'] >= $this->towerHeightThreshold) {
                $obj['type'] = 'tower';
                $obj['name'] = 'Tower ' . $obj['id'];
            } else {
                $obj['type'] = 'tree';
                $obj['name'] = 'Pohon ' . $obj['id'];
            }
        }
        return $objects;
    }

    /**
     * Match every Tree to the nearest Tower and calculate distance.
     */
    private function calculateObjectDistances(array $objects)
    {
        $trees = array_filter($objects, fn($o) => $o['type'] === 'tree');
        $towers = array_filter($objects, fn($o) => $o['type'] === 'tower');
        
        $finalData = [];

        // If no towers found, we still need to return objects with default values
        if (empty($towers)) {
            foreach ($objects as $obj) {
                $obj['nearest_tower'] = '-';
                $obj['nearest_tower_height'] = 0;
                $obj['distance_to_tower'] = 0;
                $obj['vertical_clearance'] = 0;
                $finalData[] = $obj;
            }
            return $finalData;
        }

        foreach ($trees as $tree) {
            $nearestTower = null;
            $minDist = null;

            foreach ($towers as $tower) {
                $dist = $this->haversineGreatCircleDistance(
                    $tree['centroid_lat'], $tree['centroid_lon'],
                    $tower['centroid_lat'], $tower['centroid_lon']
                );

                if ($minDist === null || $dist < $minDist) {
                    $minDist = $dist;
                    $nearestTower = $tower;
                }
            }

            // Enrich the tree data with relation to the tower
            $tree['nearest_tower'] = $nearestTower ? $nearestTower['name'] : '-';
            $tree['nearest_tower_height'] = $nearestTower ? $nearestTower['height'] : 0;
            $tree['distance_to_tower'] = $minDist ?? 0;
            
            // Calculate Vertical Clearance
            $tree['vertical_clearance'] = $nearestTower ? ($nearestTower['height'] - $tree['height']) : 0;

            $finalData[] = $tree;
        }

        // Add towers back to the result so they appear on the map
        foreach ($towers as $tower) {
            $tower['nearest_tower'] = '-';
            $tower['nearest_tower_height'] = $tower['height'];
            $tower['distance_to_tower'] = 0;
            $tower['vertical_clearance'] = 0;
            $finalData[] = $tower;
        }

        return $finalData;
    }

    /**
     * Calculates the great-circle distance between two points on the Earth's surface.
     * @return float Distance in meters
     */
    private function haversineGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
    {
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        
        return $angle * $earthRadius;
    }
}
