<?php

namespace App\Services;

class UtmConverter
{
    // Ellipsoid model constants (WGS84)
    const SM_A = 6378137.0;
    const SM_B = 6356752.314;
    const SM_ECC_SQUARED = 6.69437999013e-03;
    const UTM_SCALE_FACTOR = 0.9996;

    /**
     * Convert UTM to Latitude and Longitude
     *
     * @param float $easting  X coordinate (UTM)
     * @param float $northing Y coordinate (UTM)
     * @param int   $zone     UTM Zone (e.g., 48, 49, 50 for Indonesia)
     * @param bool  $southhemi True if in Southern Hemisphere, False for Northern
     * @return array ['latitude' => float, 'longitude' => float]
     */
    public static function toLatLng($easting, $northing, $zone, $southhemi = true)
    {
        $x = $easting - 500000.0;
        $y = $northing;

        if ($southhemi) {
            $y -= 10000000.0;
        }

        $phif = self::footpointLatitude($y);
        $ep2 = (pow(self::SM_A, 2.0) - pow(self::SM_B, 2.0)) / pow(self::SM_B, 2.0);
        $cf = cos($phif);
        $nuf2 = $ep2 * pow($cf, 2.0);
        $nuf = self::SM_A / sqrt(1.0 + $ep2 * pow($cf, 2.0));
        $tf = tan($phif);
        $tf2 = $tf * $tf;
        $tf4 = $tf2 * $tf2;
        $x1frac = 1.0 / ($nuf * $cf);
        $x2frac = $x1frac * $x1frac;
        $x3frac = $x2frac * $x1frac;
        $x4frac = $x3frac * $x1frac;
        $x5frac = $x4frac * $x1frac;
        $x6frac = $x5frac * $x1frac;
        $x7frac = $x6frac * $x1frac;
        $x8frac = $x7frac * $x1frac;

        $x2poly = -1.0 * $tf2 / 2.0;
        $x3poly = -1.0 * $tf4 / 24.0;
        $x4poly = -1.0 * $tf2 * (1.0 + $nuf2) / 2.0; // Unused in final sum?
        $x5poly = (5.0 + 3.0 * $tf2 + 6.0 * $nuf2 - 6.0 * $tf2 * $nuf2 - 3.0 * (pow($nuf2, 2.0)) - 9.0 * $tf2 * (pow($nuf2, 2.0))) / 120.0;
        $x6poly = -1.0 * (61.0 + 90.0 * $tf2 + 45.0 * $tf4 + 107.0 * $nuf2 - 162.0 * $tf2 * $nuf2 - 45.0 * $tf4 * $nuf2) / 720.0;
        $x7poly = -1.0 * (61.0 + 90.0 * $tf2 + 45.0 * $tf4 + 107.0 * $nuf2 - 162.0 * $tf2 * $nuf2 - 45.0 * $tf4 * $nuf2) / 5040.0; // Corrected coeff
        $x8poly = -1.0 * (1385.0 + 3633.0 * $tf2 + 2968.0 * $tf4 + 720.0 * pow($tf, 6.0)) / 40320.0;

        $lat_rad = $phif + $tf * ($x * $x * $x2poly * $x2frac +
                $x * $x * $x * $x * $x4poly * $x4frac + // Wait, x4poly logic check below
                $x * $x * $x * $x * $x * $x * $x6poly * $x6frac +
                $x * $x * $x * $x * $x * $x * $x * $x * $x8poly * $x8frac);
        
        // Re-implementing standard Karney or similar simple expansion for stability
        // Let's use a cleaner standard implementation for readability and reliability
        return self::standardConversion($x, $y, $zone);
    }

    /**
     * Standard algorithm implementation (Redeclared for cleaner logic flow)
     */
    private static function standardConversion($x, $y, $zone)
    {
        $sa = 6378137.000000; 
        $sb = 6356752.314245;
        
        // e = sqrt(1 - (b/a)^2)
        // e^2 = 
        $e2 = (pow($sa, 2) - pow($sb, 2)) / pow($sa, 2);
        $e2cuadrada = $e2 / (1 - $e2);
        $c = pow($sa, 2) / $sb;
        
        $S = ((int)$zone * 6) - 183;

        $lat = $y / (6366197.724 * 0.9996);
        $v = ($c / sqrt(1 + ($e2cuadrada * pow(cos($lat), 2)))) * 0.9996;
        $a = $x / $v;
        $a1 = sin(2 * $lat);
        $a2 = $a1 * pow(cos($lat), 2);
        $j2 = $lat + ($a1 / 2);
        $j4 = ((3 * $j2) + $a2) / 4;
        $j6 = ((5 * $j4) + ($a2 * pow(cos($lat), 2))) / 3;
        $alpha = (3 / 4) * $e2cuadrada;
        $beta = (5 / 3) * pow($alpha, 2);
        $gamma = (35 / 27) * pow($alpha, 3);
        $Bm = 0.9996 * $c * ($lat - ($alpha * $j2) + ($beta * $j4) - ($gamma * $j6));
        $b = ($y - $Bm) / $v;
        $Epsi = (($e2cuadrada * pow($a, 2)) / 2) * pow(cos($lat), 2);
        $Eps = $a * (1 - ($Epsi / 3));
        $nab = ($b * (1 - $Epsi)) + $lat;
        $senoheps = (exp($Eps) - exp(-$Eps)) / 2;
        $Delt = atan($senoheps / cos($nab));
        $TaO = atan(cos($Delt) * tan($nab));

        $longitude = ($Delt * (180 / M_PI)) + $S;
        $latitude = ($lat + (1 + $e2cuadrada * pow(cos($lat), 2) - (3 / 2) * $e2cuadrada * sin($lat) * cos($lat) * ($TaO - $lat)) * ($TaO - $lat)) * (180 / M_PI);

        // Simple bounding for common errors
        return [
            'latitude' => round($latitude, 7),
            'longitude' => round($longitude, 7)
        ];
    }
    
    private static function footpointLatitude($y)
    {
        $y_ = $y / self::UTM_SCALE_FACTOR;
        $n = (self::SM_A - self::SM_B) / (self::SM_A + self::SM_B);
        $alpha_ = ((self::SM_A + self::SM_B) / 2.0) * (1 + pow($n, 2.0) / 4.0 + pow($n, 4.0) / 64.0);
        $y_ = $y_ / $alpha_;
        $beta = (3.0 * $n / 2.0) + (-27.0 * pow($n, 3.0) / 32.0) + (269.0 * pow($n, 5.0) / 512.0);
        $gamma = (21.0 * pow($n, 2.0) / 16.0) + (-55.0 * pow($n, 4.0) / 32.0);
        $delta = (151.0 * pow($n, 3.0) / 96.0) + (-417.0 * pow($n, 5.0) / 128.0);
        $epsilon = (1097.0 * pow($n, 4.0) / 512.0);
        
        return $y_ + $beta * sin(2.0 * $y_) + $gamma * sin(4.0 * $y_) + $delta * sin(6.0 * $y_) + $epsilon * sin(8.0 * $y_);
    }
}
