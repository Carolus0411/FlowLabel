<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\AutoCode;

class Code {

    public function autoCode( $format, $reset = 'month', $date = '', $length = 4 ): string
    {
        if (empty($date)) $date = date('Y-m-d');

        $time = strtotime($date);

        if ($reset == 'year') {
            $key = $format .'|'. date('Y', $time);
        } else {
            $key = $format .'|'. date('Y', $time) .'|'. date('m', $time);
        }

        $code = Code::updateOrCreate(
            [ 'key' => $key, 'format' => $format ],
        )->increment('num');

        //$code = Code::where('key', $key)->first();

        $replacer = [
            '{Y}' => date('Y', $time),
            '{y}' => date('y', $time),
            '{m}' => date('m', $time),
            '{d}' => date('d', $time),
            '{num}' => Str::padLeft($code->num, $length, '0'),
        ];

        return str_replace(array_keys($replacer), array_values($replacer), $format);
    }

    public static function auto( $code, $date = '', $length = 4 ): string
    {
        $date = $date ? $date : date('Y-m-d');
        $time = strtotime($date);
        $prefix = $code . '/'.date('y', $time).'/'.date('m', $time).'/';
        AutoCode::updateOrCreate(
            ['prefix' => $prefix],
        )->increment('num');
        $code = AutoCode::where('prefix', $prefix)->first();
        return $code->prefix . Str::padLeft($code->num, $length, '0');
    }
}
