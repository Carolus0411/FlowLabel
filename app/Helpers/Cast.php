<?php

namespace App\Helpers;

use Illuminate\Support\Carbon;

class Cast {

    public static function number( $num )
    {
        if(empty($num)) return 0;
        $num = @trim(@rtrim(@ltrim($num)));
        return floatval(preg_replace('#[^0-9\.\-]#i', '', $num));
    }

    public static function currency( $num, $decimal = 2 )
    {
        if(empty($num)) $num = 0;
        $num = self::number($num);
        return number_format($num, $decimal, '.', ',');
    }

    public static function money( $num, $decimal = 2 )
    {
        if(empty($num)) $num = 0;
        $num = self::number($num);
        return number_format($num, $decimal, '.', ',');
    }

    public static function absMoney( $num, $decimal = 2 )
    {
        if(empty($num)) $num = 0;
        $num = self::number($num);
        return str_replace('-', '', number_format($num, $decimal, '.', ','));
    }

    public static function money2( $num, $decimal = 2 )
    {
        if(empty($num)) $num = 0;
        $num = self::number($num);
        if ($num >= 0) return number_format($num, $decimal, '.', ',');
        else return '('. str_replace('-', '', number_format($num, $decimal, '.', ',')) .')';
    }

    public static function nn( $num )
    {
        if (empty($num)) $num = 0;
        $num = floatval(self::number($num));
        if ($num >= 10000) {
            $num = 10000;
        }
        return $num;
    }

    public static function date( $str, $format = 'd/m/y' )
    {
        if(in_array($str, [null,"","0000-00-00","1900-01-01"])) return '';
        return Carbon::parse($str)->format($format);
    }

    public static function datetime( $str, $format = 'd-M-y, H:i' )
    {
        if(in_array($str, [null,"","0000-00-00 00:00:00","1900-01-01 00:00:00"])) return '';
        return Carbon::parse($str)->format($format);
    }

    public static function monthForHuman( $str )
    {
        $year = substr($str, 0, 4);
        $month = substr($str, 4, 2);
        $dateFormat = $year.'-'.$month.'-01';
        $monthName = date('F', strtotime($dateFormat));
        return $monthName.' '.$year;
    }

    public static function volumeWeight( $length, $width, $height )
    {
        return round(( self::nn($length) * self::nn($width) * self::nn($height) ) / 5000, 3);
    }

    public static function chargeableWeight( $weight_type, $actual_weight, $volume_weight )
    {
        $actual_weight = floatval(self::number($actual_weight));
        $volume_weight = floatval(self::number($volume_weight));

        if ( $weight_type == 'weight' ) {
            $chargeable_weight = ceil($actual_weight);
        } else if ( $weight_type == 'volume' ) {
            $chargeable_weight = ceil($volume_weight);
        } else {
            if ( $actual_weight >= $volume_weight ) {
                $chargeable_weight = ceil($actual_weight);
            } else {
                $chargeable_weight = ceil($volume_weight);
            }
        }

        return $chargeable_weight;
    }
}
