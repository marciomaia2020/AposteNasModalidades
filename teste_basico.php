<?php
echo "<h1>🧪 Teste Básico de Conexão</h1>";

// Configurações do banco
$host = 'localhost';
$dbname = 'loterias_caixa';
$username = 'root';
$password = '';

try {
    // Testar conexão
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p>✅ Conexão OK</p>";
    
    // Inserir jogo diretamente
    $sql = "INSERT INTO megasena_jogos (numeros, usuario_ip) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    $resultado = $stmt->execute(['01,02,03,04,05,06', 'teste_ip']);
    
    if ($resultado) {
        $id = $pdo->lastInsertId();
        echo "<p>✅ Jogo inserido! ID: $id</p>";
    } else {
        echo "<p>❌ Falha na inserção</p>";
    }
    
    // Listar jogos
    $stmt = $pdo->query("SELECT * FROM megasena_jogos");
    $jogos = $stmt->fetchAll();
    
    echo "<h3>Jogos na tabela:</h3>";
    foreach ($jogos as $jogo) {
        echo "<p>ID: {$jogo['id']} | Números: {$jogo['numeros']} | Data: {$jogo['data_criacao']}</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
}
?>