<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($__bootstrapLoaded)) return;
$__bootstrapLoaded = true;

$srcDir = __DIR__;
require_once $srcDir . '/Database.php';
require_once $srcDir . '/Tiles.php';
require_once $srcDir . '/Auth.php';
require_once $srcDir . '/MapGenerator.php';
require_once $srcDir . '/Game.php';

$db = Database::connect();

// Auto-migrate: add noclip column if missing
try {
    $db->query('SELECT noclip FROM players LIMIT 1');
} catch (PDOException $e) {
    $db->exec('ALTER TABLE players ADD COLUMN `noclip` TINYINT NOT NULL DEFAULT 0 AFTER `warp4`');
}

$auth = new Auth($db);
$mapGen = new MapGenerator(__DIR__ . '/../public/levels');
$game = new Game($db, $mapGen);
