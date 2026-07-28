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
$relationships = "";

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
        
        // Infer relationships
        if (preg_match('/^(.+)_id$/', $name, $matches)) {
            $related_singular = $matches[1];
            
            if ($name === 'parent_id') {
                $relationships .= "    $table }|--|| $table : \"$name\"\n";
            } else {
                $candidates = [
                    $related_singular . 's',
                    substr($related_singular, 0, -1) . 'ies',
                    $related_singular . 'es',
                    $related_singular
                ];
                
                // For author_id, it might refer to accounts or users.
                if ($name === 'author_id') {
                    $candidates[] = 'users';
                    $candidates[] = 're_accounts';
                }
                if ($name === 'user_id') {
                    $candidates[] = 'users';
                }

                foreach ($candidates as $candidate) {
                    if (in_array($candidate, $tables)) {
                        $relationships .= "    $table }|--|| $candidate : \"$name\"\n";
                        break; // Stop at first match
                    }
                    // check if prefix table exists (e.g. re_categories for category_id)
                    foreach ($tables as $t) {
                        if (str_ends_with($t, '_' . $candidate)) {
                            $relationships .= "    $table }|--|| $t : \"$name\"\n";
                            break 2;
                        }
                    }
                }
            }
        }
    }
    $mermaid .= "    }\n";
}

$mermaid .= "\n" . $relationships;

file_put_contents('full_er.mmd', $mermaid);
echo "Generated full_er.mmd successfully.\n";
