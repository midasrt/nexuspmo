<?php
define('FCPATH', __DIR__ . '/../public/');
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';
$context = CodeIgniter\Boot::bootCli($paths);

$resourceModel = new \App\Models\ResourceModel();
$resources = $resourceModel->findAll();
echo "COUNT: " . count($resources) . "\n";
foreach ($resources as $r) {
    echo "- ID: {$r['id']}, Name: {$r['name']}\n";
}
