<?php
$_db = new PDO('mysql:host=localhost;dbname=foureyescollective', 'root', '', [
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
]);
?>
