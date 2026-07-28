<?php

$host = '127.0.0.1';
$db   = 'homezen';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

$mermaid = "erDiagram\n";

foreach ($tables as $table) {
    $mermaid .= "    $table {\n";
    $columns = $pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll();
    
    foreach ($columns as $column) {
        $type = preg_replace('/\(.*\)/', '', $column['Type']); // remove sizes like int(11)
        $type = preg_replace('/[^a-zA-Z0-9_]/', '_', $type); // sanitize for mermaid
        $name = preg_replace('/[^a-zA-Z0-9_]/', '_', $column['Field']);
        
        $key = '';
        if ($column['Key'] === 'PRI') $key = 'PK';
        elseif ($column['Key'] === 'MUL') $key = 'FK';
        
        $mermaid .= "        $type $name $key\n";
    }
    $mermaid .= "    }\n";
}

file_put_contents('full_er.mmd', $mermaid);
echo "Generated full_er.mmd successfully.\n";
