<?php
/**
 * AmazonGest Pro - Funções Utilitárias
 * Versão Profissional com Validações e Cálculos Avançados
 */

// Carrega configurações
require_once __DIR__ . '/config.php';

// Garante que o diretório de dados exista
if (!is_dir(DATA_DIR)) {
    @mkdir(DATA_DIR, 0755, true);
}

/**
 * Função segura para carregar JSON
 */
function carregarJSON(string $arquivo): array {
    if (!file_exists($arquivo)) {
        file_put_contents($arquivo, json_encode([]));
        return [];
    }

    $conteudo = file_get_contents($arquivo);
    if (empty($conteudo)) {
        return [];
    }

    $dados = json_decode($conteudo, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Erro JSON em {$arquivo}: " . json_last_error_msg());
        return [];
    }

    return $dados ?: [];
}

/**
 * Função segura para salvar JSON com lock de arquivo
 */
function salvarJSON(string $arquivo, array $dados): bool {
    $tmp_file = $arquivo . '.tmp';

    // Escreve no arquivo temporário primeiro
    $json = json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (file_put_contents($tmp_file, $json, LOCK_EX) === false) {
        error_log("Falha ao escrever no arquivo temporário: {$tmp_file}");
        return false;
    }

    // Renomeia para o arquivo final (operatória atômica)
    if (!rename($tmp_file, $arquivo)) {
        @unlink($tmp_file);
        error_log("Falha ao renomear arquivo: {$tmp_file} -> {$arquivo}");
        return false;
    }

    return true;
}

/**
 * Gerar ID único
 */
function gerarId(): string {
    return uniqid('amz', true) . '-' . time();
}

/**
 * Legado: gerar ID numérico
 */
function gerarIdNumerico($array, $campo = 'id') {
    if (empty($array)) return 1;
    $ids = array_column($array, $campo);
    $ids = array_map('intval', $ids);
    return max($ids) + 1;
}

function removerPorId(&$array, $id, $campo = 'id') {
    foreach ($array as $key => $item) {
        if ((int)$item[$campo] === (int)$id) {
            unset($array[$key]);
            $array = array_values($array);
            return true;
        }
    }
    return false;
}

function existeId($array, $id, $campo = 'id') {
    foreach ($array as $item) {
        if ((int)$item[$campo] === (int)$id) {
            return true;
        }
    }
    return false;
}

function obterTaxaCategoria($categoria, $config) {
    if (empty($categoria)) $categoria = 'outros';
    $cats = $config['categoriasAmazon'] ?? [];
    $cat = $cats[$categoria] ?? null;
    if ($cat && isset($cat['taxa'])) return (float)$cat['taxa'];
    return (float)($config['taxaPadrao'] ?? 15);
}

function calcularLucroLiquido($precoVenda, $precoCusto, $taxaCategoria, $frete = 0, $embalagem = 0, $gastosExtras = 0) {
    $feeAmount = $precoVenda * ($taxaCategoria / 100);
    return $precoVenda - $precoCusto - $feeAmount - $frete - $embalagem - $gastosExtras;
}

function logSistema($mensagem) {
    $logFile = DATA_DIR . 'sistema.log';
    $timestamp = date('Y-m-d H:i:s');
    $entrada = "[{$timestamp}] {$mensagem}\n";
    $fp = fopen($logFile, 'a');
    if ($fp) {
        fwrite($fp, $entrada);
        fclose($fp);
    }
}

/**
 * Sanetizar input
 */
function sanitize(string $input, string $type = 'string'): mixed {
    return match($type) {
        'email' => filter_var(trim($input), FILTER_SANITIZE_EMAIL),
        'int' => filter_var($input, FILTER_SANITIZE_NUMBER_INT),
        'float' => filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
        'url' => filter_var($input, FILTER_SANITIZE_URL),
        default => htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8')
    };
}

/**
 * Validar CPF
 */
function validarCPF(string $cpf): bool {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);

    if (strlen($cpf) != 11 || preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }

    for ($i = 9; $i < 11; $i++) {
        $sum = 0;
        for ($j = 0; $j < $i; $j++) {
            $sum += $cpf[$j] * (($i + 1) - $j);
        }
        $digit = ($sum * 10) % 11;
        $digit = $digit == 10 ? 0 : $digit;

        if ($cpf[$i] != $digit) {
            return false;
        }
    }

    return true;
}

/**
 * Formatador de moeda BRL
 */
function formatarBRL(float $valor): string {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

/**
 * Calcular percentuais
 */
function calcularPercentual(float $valor, float $total): float {
    if ($total == 0) {
        return 0;
    }
    return round(($valor / $total) * 100, 2);
}

/**
 * Calcular Margem de Lucro
 */
function calcularMargemLucro(float $precoVenda, float $precoCusto): float {
    if ($precoVenda == 0) {
        return 0;
    }
    return round((($precoVenda - $precoCusto) / $precoVenda) * 100, 2);
}

/**
 * Calcular ROI
 */
function calcularROI(float | array $ganhos, float | array $investimento): float {
    if (is_array($ganhos)) {
        $ganhos = array_sum($ganhos);
    }
    if (is_array($investimento)) {
        $investimento = array_sum($investimento);
    }

    if ($investimento == 0) {
        return 0;
    }

    return round((($ganhos - $investimento) / $investimento) * 100, 2);
}

/**
 * Paginação
 */
function paginar(array $dados, int $pagina = 1, int $itensPorPagina = 10): array {
    $total = count($dados);
    $totalPaginas = ceil($total / $itensPorPagina);
    $offset = ($pagina - 1) * $itensPorPagina;

    return [
        'dados' => array_slice($dados, $offset, $itensPorPagina),
        'pagina_atual' => $pagina,
        'total_paginas' => $totalPaginas,
        'total_itens' => $total,
        'itens_por_pagina' => $itensPorPagina
    ];
}

/**
 * Busca texto em array
 */
function buscarPalavra(array $array, string $busca, array $campos = []): array {
    $busca = mb_strtolower($busca, 'UTF-8');
    $resultados = [];

    foreach ($array as $item) {
        foreach ($campos as $campo) {
            if (isset($item[$campo]) && mb_strpos(mb_strtolower($item[$campo], 'UTF-8'), $busca) !== false) {
                $resultados[] = $item;
                break;
            }
        }
    }

    return $resultados;
}

/**
 * API response padrão
 */
function apiResponse(bool $sucesso, mixed $dados = null, string $mensagem = ''): void {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $sucesso,
        'data' => $dados,
        'message' => $mensagem,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Obter ou salvar configurações do usuário
 */
function getConfiguracoesUsuario(): array {
    return carregarJSON(CONFIG_FILE);
}

function salvarConfiguracoesUsuario(array $config): bool {
    return salvarJSON(CONFIG_FILE, $config);
}
