<?php

// Fetch all categories as:  CAxxxx => "Name"
function getCategoryNames($_db) {
    $stm = $_db->query("SELECT category_id, category_name FROM category ORDER BY category_id");
    return $stm->fetchAll(PDO::FETCH_KEY_PAIR);
}

// Fetch folder mapping: CAxxxx => folder name
function getCategoryFolders($_db) {
    $stm = $_db->query("SELECT category_id, folder FROM category ORDER BY category_id");
    return $stm->fetchAll(PDO::FETCH_KEY_PAIR);
}

$categories      = getCategoryNames($_db);     // name lookup
$categoryFolders = getCategoryFolders($_db);   // folder lookup