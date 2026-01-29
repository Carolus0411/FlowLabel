<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\AutoCode;

class Code {

    public function autoCode( $format, $reset = 'month', $date = '', $length = 4 ): string
    {
        $dt = !empty($date) ? \Illuminate\Support\Carbon::parse($date) : now();

        if ($reset == 'year') {
            $key = $format .'|'. $dt->format('Y');
        } else {
            $key = $format .'|'. $dt->format('Y') .'|'. $dt->format('m');
        }

        $code = AutoCode::updateOrCreate(
            [ 'key' => $key, 'format' => $format ],
        );
        $code->increment('num');
        $code->refresh();

        //$code = Code::where('key', $key)->first();

        $replacer = [
            '{Y}' => $dt->format('Y'),
            '{y}' => $dt->format('y'),
            '{m}' => $dt->format('m'),
            '{d}' => $dt->format('d'),
            '{num}' => Str::padLeft($code->num, $length, '0'),
        ];

        return str_replace(array_keys($replacer), array_values($replacer), $format);
    }

    public static function auto( $code, $date = '', $length = 4 ): string
    {
        $dt = !empty($date) ? \Illuminate\Support\Carbon::parse($date) : now();
        $prefix = $code . '/'.$dt->format('y').'/'.$dt->format('m').'/';
        AutoCode::updateOrCreate(
            ['prefix' => $prefix],
        )->increment('num');
        $code = AutoCode::where('prefix', $prefix)->first();
        return $code->prefix . Str::padLeft($code->num, $length, '0');
    }
}
