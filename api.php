<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Configuração do banco de dados
$host = 'localhost';
$dbname = 'loterias_caixa';
$username = 'root'; // Ajuste conforme sua configuração
$password = '';     // Ajuste conforme sua configuração

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die(json_encode(['error' => 'Erro de conexão: ' . $e->getMessage()]));
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Obter IP do usuário
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

$user_ip = getUserIP();

switch($method) {
    case 'GET':
        // Buscar jogos de uma modalidade
        $modalidade = $_GET['modalidade'] ?? '';
        if ($modalidade) {
            $table = $modalidade . '_jogos';
            $stmt = $pdo->prepare("SELECT * FROM $table WHERE usuario_ip = ? ORDER BY data_criacao DESC");
            $stmt->execute([$user_ip]);
            $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'jogos' => $jogos]);
        }
        break;
        
    case 'POST':
        // Salvar novo jogo
        $modalidade = $input['modalidade'] ?? '';
        $jogo = $input['jogo'] ?? [];
        
        if ($modalidade && $jogo) {
            $table = $modalidade . '_jogos';
            
            switch($modalidade) {
                case 'timemania':
                    $stmt = $pdo->prepare("INSERT INTO $table (numeros, time_coracao, usuario_ip) VALUES (?, ?, ?)");
                    $stmt->execute([
                        implode(',', $jogo['numbers']),
                        $jogo['team'],
                        $user_ip
                    ]);
                    break;
                    
                case 'diadesorte':
                    $stmt = $pdo->prepare("INSERT INTO $table (numeros, mes_sorte, usuario_ip) VALUES (?, ?, ?)");
                    $stmt->execute([
                        implode(',', $jogo['numbers']),
                        $jogo['month'],
                        $user_ip
                    ]);
                    break;
                    
                case 'maismilionaria':
                    $stmt = $pdo->prepare("INSERT INTO $table (numeros, trevos, usuario_ip) VALUES (?, ?, ?)");
                    $stmt->execute([
                        implode(',', $jogo['numbers']),
                        implode(',', $jogo['trevos']),
                        $user_ip
                    ]);
                    break;
                    
                case 'supersete':
                    $stmt = $pdo->prepare("INSERT INTO $table (numeros, usuario_ip) VALUES (?, ?)");
                    $stmt->execute([
                        implode('', $jogo['numbers']),
                        $user_ip
                    ]);
                    break;
                    
                case 'loteca':
                    $stmt = $pdo->prepare("INSERT INTO $table (palpites, usuario_ip) VALUES (?, ?)");
                    $stmt->execute([
                        implode(',', $jogo['numbers']),
                        $user_ip
                    ]);
                    break;
                    
                case 'federal':
                    $stmt = $pdo->prepare("INSERT INTO $table (numero, usuario_ip) VALUES (?, ?)");
                    $stmt->execute([
                        $jogo['numbers'][0],
                        $user_ip
                    ]);
                    break;
                    
                default:
                    // Modalidades padrão (megasena, quina, lotofacil, lotomania, duplasena)
                    $stmt = $pdo->prepare("INSERT INTO $table (numeros, usuario_ip) VALUES (?, ?)");
                    $stmt->execute([
                        implode(',', $jogo['numbers']),
                        $user_ip
                    ]);
                    break;
            }
            
            $id = $pdo->lastInsertId();
            echo json_encode(['success' => true, 'id' => $id]);
        }
        break;
        
    case 'DELETE':
        // Deletar jogo
        $modalidade = $input['modalidade'] ?? '';
        $id = $input['id'] ?? 0;
        
        if ($modalidade && $id) {
            $table = $modalidade . '_jogos';
            $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ? AND usuario_ip = ?");
            $stmt->execute([$id, $user_ip]);
            echo json_encode(['success' => true]);
        }
        break;
}
?>