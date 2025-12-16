<?php
require_once __DIR__ . '/db.php';

$categories = [];
$categoryFolders = [];

$stm = $_db->query("SELECT category_id, category_name, folder FROM category");

foreach ($stm->fetchAll() as $c) {
    $categories[$c->category_id] = $c->category_name;
    $categoryFolders[$c->category_id] = $c->folder;
}