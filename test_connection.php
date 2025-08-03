<?php
// Teste de conexão com o banco
$host = 'localhost';
$dbname = 'loterias_caixa';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>✅ Conexão com o banco OK!</h2>";
    
    // Testar se as tabelas existem
    $tables = [
        'megasena_jogos', 'quina_jogos', 'lotofacil_jogos', 'lotomania_jogos',
        'timemania_jogos', 'duplasena_jogos', 'diadesorte_jogos', 'supersete_jogos',
        'loteca_jogos', 'federal_jogos', 'maismilionaria_jogos'
    ];
    
    echo "<h3>📊 Status das Tabelas:</h3>";
    foreach($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        
        if ($stmt->rowCount() > 0) {
            // Contar registros
            $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM $table");
            $countStmt->execute();
            $count = $countStmt->fetch()['total'];
            
            echo "<p>✅ $table - <strong>$count registros</strong></p>";
        } else {
            echo "<p>❌ $table - <strong>Não existe</strong></p>";
        }
    }
    
} catch(PDOException $e) {
    echo "<h2>❌ Erro de conexão:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>