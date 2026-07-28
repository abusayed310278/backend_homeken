<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=homezen;charset=utf8mb4', 'root', '');
$sql = "SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = 'homezen'";
$stmt = $pdo->query($sql);
$fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Count of explicit FKs: " . count($fks) . "\n";
