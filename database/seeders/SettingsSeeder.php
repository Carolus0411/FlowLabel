<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks to avoid errors during truncation
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        DB::table('settings')->truncate();
        
        DB::table('settings')->insert([
            [
  'id' => 1,
  'key' => 'da7287b68c622e63d10b764f479dc018',
  'value' => 'eyJpdiI6IkxIMDdkNENROHAzVkw2RVJnSGhLS0E9PSIsInZhbHVlIjoiRGRwaGJUQ3I5eGVrMW0yRjNqRHpCMk1TZk1nZWtWVkJxVnVVa1VMWjZXND0iLCJtYWMiOiIzMzZmZmJiZjY3MjE4ZWJhNmZiYTM0NjEyZjNlNjQwNjEzNjRkOTc0ODI3NTZhZmM3MmRhMGUwNDUxOTVkYjNkIiwidGFnIjoiIn0=',
],
            [
  'id' => 2,
  'key' => '59fa17b5ab29e5806b52ec7074ba4fe6',
  'value' => 'eyJpdiI6IkU1S2hndjNOTHRmeVJrRkdzRWJFbUE9PSIsInZhbHVlIjoiSE9WUEg0V1JhVGNvNlhmRzArY2l6YUUvenNwVlExSTJySTJIMTFXdUpCaz0iLCJtYWMiOiI1OWJkMTMzODc0ZWRhMzM4ZDQ2ZGYxYWY3ZjZjOWFhYjIzZTA1ZmZiNDYwN2Q4YWQzZjM3MmFjNDhmY2JlNTA4IiwidGFnIjoiIn0=',
],
            [
  'id' => 3,
  'key' => '5f2ceea06bf12b8dda587d2275066436',
  'value' => 'eyJpdiI6IjNQb0s3dHluSytQMUQzQ0VKNGJwQWc9PSIsInZhbHVlIjoiRFY4L2YrT01Kekl5Q3JJaGJmUGFoa3FodFpuRGp5WDJ1RkdIV2xvV3gvOD0iLCJtYWMiOiI3MzJlNDk0OTBkMjY1OGU2NjRmNDY0ODYzMmU0ZjlhMmYyMTVhOTYyZTdmYTVmOGQyODVjNzQ2NTNkOWNhZjI0IiwidGFnIjoiIn0=',
],
            [
  'id' => 4,
  'key' => '1696f27b1820055690c031502ecec91c',
  'value' => 'eyJpdiI6InVXRDREM0NuY3phR0IvWnd5L2wvU3c9PSIsInZhbHVlIjoiMUhFcGxUc2NSa3I3SE9MUStHTXhXaUtmWmY4THpCQTRjZzlpdy8xTW1ZMD0iLCJtYWMiOiIwZDU2NTIwZTRjNWYwNjMwMWFkNDhiZWZhMzY2NTY2NTM2YjI5MWJhZmE0N2FiYTI4MDViZjAxNmU4M2I5MjMwIiwidGFnIjoiIn0=',
],
            [
  'id' => 5,
  'key' => '1123f371c1b88f1e8f8eaf3eeb8f83db',
  'value' => 'eyJpdiI6Ii92amV2aklXZkFLTWFKVXRuS2pnU2c9PSIsInZhbHVlIjoiZjVuVDVOWFFpeERaYkV5ZU1WVnJycXpiYnZUalk5ZUllbkcrMHF2TWlIZz0iLCJtYWMiOiI0MmI2NTJlZTlmOGI1MWRmZTI2MmRhNTdjYTkyNGRmYTUxNzhkNDY0NDY3MDllZWQ1M2U1NjZiOTI5YTU0NDlkIiwidGFnIjoiIn0=',
],
            [
  'id' => 6,
  'key' => '9a24ba9aba1db2ccd3cbad6232a9ed0b',
  'value' => 'eyJpdiI6IllPSFU5NUtvTkVPaklheXhySnJTMmc9PSIsInZhbHVlIjoiVkxYckV2MUdFRGp1VkVCYmxRbFRuWkp6SnJ3YjBBRWFtQ3VaQkFjZjdYZz0iLCJtYWMiOiI1NWY2YTJjNGZmYjIxM2Q0NjAzNTRiNTg5YmFhYzk1ODY3ZTYyYTM4MGI4Zjg5MGZhMThjYTU5MTNiNGY1NjVlIiwidGFnIjoiIn0=',
],
            [
  'id' => 7,
  'key' => '932a50162b5545ccece19b247db39ad1',
  'value' => 'eyJpdiI6IlQ2TmR3MHczbmhqb0E4UzlzRVFEQVE9PSIsInZhbHVlIjoiMzg3dmJlelQzRW9DZ2RjL3N3YnpJRHdoeFVBZmFGWmplTHluakw2OGRHOD0iLCJtYWMiOiIzMGJhOWE1ZDM0NzVjZGI1MmYxNzU0NWQ2ZTFjZDEwNzM1Y2VhNTU2NWE0NmIyOWEwZTFkNjkzMjY0ZjdjMWFhIiwidGFnIjoiIn0=',
],
            [
  'id' => 8,
  'key' => '61f569d4ec2da4ef6452361ab168f4cd',
  'value' => 'eyJpdiI6IkdqSGZrOTBLNVVwcHdvNlNSbURiMXc9PSIsInZhbHVlIjoiVXZwZTZkMWNxemNMaS8wV0VjOVpySERlamVzT0ZaV0x4cm1LMnJQZUpqOD0iLCJtYWMiOiJlNDY5NmUyMmNhNjY2ZTNiMmM0ZjAwMDBkMTc1ZjJlYjUzMWRkMWM1MGQ5MjkyZjNhMDVkYjMyZDFlZjQzNDU0IiwidGFnIjoiIn0=',
],
            [
  'id' => 9,
  'key' => 'f30ba64a2dec8f47a66e21c56cc3e805',
  'value' => 'eyJpdiI6Im5Za1hTWE9NZVFBUG1NUWExTzUrZkE9PSIsInZhbHVlIjoidVFYbmFFSlU1bmtuSk8rU0czRFNBYndTS3ZrOVM1QVF4WXptTUxxejUyST0iLCJtYWMiOiI5ZjJlOWI0OTQ1ZDM4YzJkYWZiYjZhNjQ3YzQ1OTM0MzQxYzM5MWMyODQ2Y2YyMmVlNDI2NmRlZTg0M2IwYmJiIiwidGFnIjoiIn0=',
],
            [
  'id' => 10,
  'key' => '406992a2e6d8f1231cd50ed73936b670',
  'value' => 'eyJpdiI6IjJYcm1sQ0NnSUJCSUxzUGUxQ2JJaVE9PSIsInZhbHVlIjoiL01aSzFqQTBoekU1OXBKSWNTdUM2V05hVjAzaE1BeS9IanFEOTNFR0tWcz0iLCJtYWMiOiI0MjY4Y2I0YWU4MDIyMzc2YjkzNzBkMDMxMzBiMmM1MTY3ZTE5ZmU0MzFiYWM5MDdmNTdlZjRmZTM5MTgxZDE0IiwidGFnIjoiIn0=',
],
            [
  'id' => 11,
  'key' => '0f24b2185238621290482188679634f8',
  'value' => 'eyJpdiI6InFNbjZPbFQ4VVRIQzFsN2xpZE9NUFE9PSIsInZhbHVlIjoiMGZYZmEza1h1TURCbGtBZHorOEwvT3dsZkk4b0phVWpJTkFhM3pFUDFEST0iLCJtYWMiOiJjMDNmMGNhNDE3ZDhjMDFiZTc1Zjk2MzJkYzUzYTI0Mjc4ZWUxNWY4YTYyNzIxODA4MGRlNGVlNTBmN2JhMGY5IiwidGFnIjoiIn0=',
],
            [
  'id' => 12,
  'key' => 'e4fbf2bb24c576144dc923e29e1be79b',
  'value' => 'eyJpdiI6IkFyUTU2M0cySnllcUMrcnV3VncxR3c9PSIsInZhbHVlIjoidzMyRS9KUW83ek1VdUxFcmNIZzlybkhlUWVVYmIxdDVwb3YrM3RPM2dWdz0iLCJtYWMiOiI5ODA0NTIxMjY2YjhkMDY2ZmYyMjdjMjE2MmQ0NDlkYzFiM2U4YWQxYjRjMjdkMGYzOWE2ZGI0MzU0ZjRlOTk5IiwidGFnIjoiIn0=',
],
            [
  'id' => 13,
  'key' => 'ab5c07261f106828ad4a5e8e431535f7',
  'value' => 'eyJpdiI6IktCMUhBTDJYSUlUNWpYR2IxYnVaWUE9PSIsInZhbHVlIjoib1RDaDdaeFZSK3h0bHREQWlMaU44TjB5N3ZnaXA2M3BnVGE5UmZvOUQ1VT0iLCJtYWMiOiJmYjYzNTFkMTUzODE1MjUzYWM5YTE0YzdmZDMyYjc5ODg0NGEyNDgxOTQzYTAxYmQzYzYyNDU3MTkyY2U1MGMwIiwidGFnIjoiIn0=',
]
        ]);
        
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
