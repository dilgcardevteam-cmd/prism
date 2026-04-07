<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

DB::statement('ALTER TABLE tbusers MODIFY verification_token VARCHAR(255) NULL');
echo "Table altered successfully\n";
