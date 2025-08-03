<?php
// test_save.php - Arquivo para testar a inserção no banco
echo "<h1>🧪 Teste de Inserção no Banco</h1>";

$host = 'localhost';
$dbname = 'loterias_caixa';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ <strong>Conexão com banco OK!</strong></p>";
    
    // Inserir um jogo de teste na Mega-Sena
    $stmt = $pdo->prepare("INSERT INTO megasena_jogos (numeros, usuario_ip) VALUES (?, ?)");
    $stmt->execute(['01,02,03,04,05,06', $_SERVER['REMOTE_ADDR']]);
    
    $id = $pdo->lastInsertId();
    echo "<p>✅ <strong>Jogo de teste inserido! ID: $id</strong></p>";
    
    // Listar todos os jogos da Mega-Sena
    $stmt = $pdo->prepare("SELECT * FROM megasena_jogos ORDER BY data_criacao DESC");
    $stmt->execute();
    $jogos = $stmt->fetchAll();
    
    echo "<h3>📊 Jogos na tabela megasena_jogos:</h3>";
    if (count($jogos) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Números</th><th>Data</th><th>IP</th></tr>";
        foreach($jogos as $jogo) {
            echo "<tr>";
            echo "<td>{$jogo['id']}</td>";
            echo "<td><strong>{$jogo['numeros']}</strong></td>";
            echo "<td>{$jogo['data_criacao']}</td>";
            echo "<td>{$jogo['usuario_ip']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ Nenhum jogo encontrado na tabela</p>";
    }
    
    echo "<h3>🔧 Teste da API:</h3>";
    echo "<p><a href='api.php?modalidade=megasena' target='_blank'>🔗 Testar API - GET megasena</a></p>";
    
} catch(PDOException $e) {
    echo "<p>❌ <strong>Erro: " . $e->getMessage() . "</strong></p>";
}
?>