<!DOCTYPE html>
<html>
<head>
    <title>Teste Direto</title>
</head>
<body>
    <h1>🧪 Teste Direto de Inserção</h1>
    
    <form method="POST">
        <label>Números (separados por vírgula):</label><br>
        <input type="text" name="numeros" value="01,02,03,04,05,06" style="width:300px;"><br><br>
        <button type="submit">Inserir no Banco</button>
    </form>
    
    <?php
    if ($_POST['numeros']) {
        $host = 'localhost';
        $dbname = 'loterias_caixa';
        $username = 'root';
        $password = '';
        
        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $stmt = $pdo->prepare("INSERT INTO megasena_jogos (numeros, usuario_ip) VALUES (?, ?)");
            $resultado = $stmt->execute([$_POST['numeros'], $_SERVER['REMOTE_ADDR']]);
            
            if ($resultado) {
                echo "<p style='color:green;'>✅ Jogo inserido com sucesso! ID: " . $pdo->lastInsertId() . "</p>";
            } else {
                echo "<p style='color:red;'>❌ Falha na inserção</p>";
            }
            
        } catch (Exception $e) {
            echo "<p style='color:red;'>❌ Erro: " . $e->getMessage() . "</p>";
        }
    }
    
    // Listar jogos existentes
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=loterias_caixa;charset=utf8mb4", 'root', '');
        $stmt = $pdo->query("SELECT * FROM megasena_jogos ORDER BY data_criacao DESC");
        $jogos = $stmt->fetchAll();
        
        echo "<h3>Jogos na tabela:</h3>";
        if (count($jogos) > 0) {
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Números</th><th>Data</th><th>IP</th></tr>";
            foreach ($jogos as $jogo) {
                echo "<tr>";
                echo "<td>{$jogo['id']}</td>";
                echo "<td>{$jogo['numeros']}</td>";
                echo "<td>{$jogo['data_criacao']}</td>";
                echo "<td>{$jogo['usuario_ip']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Nenhum jogo encontrado</p>";
        }
    } catch (Exception $e) {
        echo "<p>Erro ao listar: " . $e->getMessage() . "</p>";
    }
    ?>
</body>
</html>