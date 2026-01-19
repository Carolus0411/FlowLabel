<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ServiceCharge;

$s = ServiceCharge::first();
if (!$s) {
    echo "NO SERVICE CHARGE in DB".PHP_EOL;
    exit;
}
$attrs = array_keys($s->getAttributes());
print_r($attrs);
echo PHP_EOL;
?>
