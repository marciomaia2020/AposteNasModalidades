<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Configuração do banco de dados - AJUSTE CONFORME SUA CONFIGURAÇÃO
$host = 'localhost';
$dbname = 'loterias_caixa';  // Nome do seu banco
$username = 'root';          // Seu usuário MySQL
$password = '';              // Sua senha MySQL (deixe vazio se não tiver)

// Conectar ao banco
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Erro de conexão com o banco: ' . $e->getMessage()]));
}

// Obter método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Para requisições OPTIONS (CORS preflight)
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Obter dados da requisição
$input = json_decode(file_get_contents('php://input'), true);

// Função para obter IP do usuário
function getUserIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

$user_ip = getUserIP();

try {
    switch($method) {
        case 'GET':
            // Buscar jogos de uma modalidade
            $modalidade = $_GET['modalidade'] ?? '';
            
            if (empty($modalidade)) {
                throw new Exception('Modalidade não informada');
            }
            
            $table = $modalidade . '_jogos';
            
            // Verificar se a tabela existe
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Tabela $table não encontrada");
            }
            
            // Buscar jogos do usuário
            $stmt = $pdo->prepare("SELECT * FROM $table WHERE usuario_ip = ? ORDER BY data_criacao DESC");
            $stmt->execute([$user_ip]);
            $jogos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true, 
                'jogos' => $jogos,
                'total' => count($jogos)
            ]);
            break;
            
        case 'POST':
            // Salvar novo jogo
            $modalidade = $input['modalidade'] ?? '';
            $jogo = $input['jogo'] ?? [];
            
            if (empty($modalidade) || empty($jogo)) {
                throw new Exception('Dados incompletos para salvar o jogo');
            }
            
            $table = $modalidade . '_jogos';
            
            // Verificar se a tabela existe
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Tabela $table não encontrada");
            }
            
            // Inserir conforme a modalidade
            switch($modalidade) {
                case 'timemania':
                    if (empty($jogo['numbers']) || empty($jogo['team'])) {
                        throw new Exception('Números e time são obrigatórios para Timemania');
                    }
                    $stmt = $pdo->prepare("INSERT INTO $table (numeros, time_coracao, usuario_ip) VALUES (?, ?, ?)");
                    $stmt->execute([
                        implode(',', $jogo['numbers']),
                        $jogo['team'],
                        $user_ip
                    ]);
                    break;
                    
                case 'diadesorte':
                    if (empty($jogo['numbers']) || empty($jogo['month'])) {
                        throw new Exception('Números e mês são obrigatórios para Dia de Sorte');
                    }
                    $stmt = $pdo->prepare("INSERT INTO $table (numeros, mes_sorte, usuario_ip) VALUES (?, ?, ?)");
                    $stmt->execute([
                        implode(',', $jogo['numbers']),
                        $jogo['month'],
                        $user_ip
                    ]);
                    break;
                    
                case 'maismilionaria':
                    if (empty($jogo['numbers']) || empty($jogo['trevos'])) {
                        throw new Exception('Números e trevos são obrigatórios para + Milionária');
                    }
                    $stmt = $pdo->prepare("INSERT INTO $table (numeros, trevos, usuario_ip) VALUES (?, ?, ?)");
                    $stmt->execute([
                        implode(',', $jogo['numbers']),
                        implode(',', $jogo['trevos']),
                        $user_ip
                    ]);
                    break;
                    
                case 'supersete':
                    if (empty($jogo['numbers'])) {
                        throw new Exception('Números são obrigatórios para Super Sete');
                    }
                    $stmt = $pdo->prepare("INSERT INTO $table (numeros, usuario_ip) VALUES (?, ?)");
                    $stmt->execute([
                        implode('', $jogo['numbers']),
                        $user_ip
                    ]);
                    break;
                    
                case 'loteca':
                    if (empty($jogo['numbers'])) {
                        throw new Exception('Palpites são obrigatórios para Loteca');
                    }
                    $stmt = $pdo->prepare("INSERT INTO $table (palpites, usuario_ip) VALUES (?, ?)");
                    $stmt->execute([
                        implode(',', $jogo['numbers']),
                        $user_ip
                    ]);
                    break;
                    
                case 'federal':
                    if (empty($jogo['numbers']) || !isset($jogo['numbers'][0])) {
                        throw new Exception('Número é obrigatório para Federal');
                    }
                    $stmt = $pdo->prepare("INSERT INTO $table (numero, usuario_ip) VALUES (?, ?)");
                    $stmt->execute([
                        $jogo['numbers'][0],
                        $user_ip
                    ]);
                    break;
                    
                default:
                    // Modalidades padrão (megasena, quina, lotofacil, lotomania, duplasena)
                    if (empty($jogo['numbers'])) {
                        throw new Exception('Números são obrigatórios');
                    }
                    $stmt = $pdo->prepare("INSERT INTO $table (numeros, usuario_ip) VALUES (?, ?)");
                    $stmt->execute([
                        implode(',', $jogo['numbers']),
                        $user_ip
                    ]);
                    break;
            }
            
            $id = $pdo->lastInsertId();
            
            echo json_encode([
                'success' => true, 
                'id' => $id,
                'message' => 'Jogo salvo com sucesso!'
            ]);
            break;
            
        case 'DELETE':
            // Deletar jogo
            $modalidade = $input['modalidade'] ?? '';
            $id = $input['id'] ?? 0;
            
            if (empty($modalidade) || empty($id)) {
                throw new Exception('Modalidade e ID são obrigatórios para deletar');
            }
            
            $table = $modalidade . '_jogos';
            
            // Verificar se a tabela existe
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Tabela $table não encontrada");
            }
            
            // Deletar apenas se for do mesmo usuário (IP)
            $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ? AND usuario_ip = ?");
            $stmt->execute([$id, $user_ip]);
            
            $affected = $stmt->rowCount();
            
            echo json_encode([
                'success' => true,
                'affected' => $affected,
                'message' => $affected > 0 ? 'Jogo deletado com sucesso!' : 'Jogo não encontrado'
            ]);
            break;
            
        default:
            throw new Exception('Método HTTP não permitido');
    }
    
} catch(Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro no banco de dados: ' . $e->getMessage()
    ]);
}
?>