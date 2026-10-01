<?php
/**
 * Magellan Utilities Construction — recebimento dos formulários do site.
 *
 * Mesmo fluxo da página Careers do Magellan Group (testado em produção):
 *   1. valida os campos e devolve mensagens claras para quem preenche;
 *   2. GRAVA o envio em ../form-logs/ (fora do public_html) antes de qualquer e-mail;
 *   3. envia o e-mail para a equipe — REMETENTE e DESTINATÁRIO são a mesma caixa
 *      (info@magellanuc.com), porque não existe conta no-reply neste domínio;
 *   4. se o e-mail falhar, registra em falhas e dispara um alerta (ALERT_TO) — o envio
 *      continua salvo em ../form-logs/;
 *   5. manda uma confirmação de recebimento para quem preencheu (se deu e-mail).
 *
 * Responde JSON quando chamado por fetch (assets/forms.js) e redireciona para a
 * página de obrigado quando o formulário é enviado sem JavaScript.
 */

declare(strict_types=1);

// ------------------------------------------------------------------ config
const SITE_NAME  = 'Magellan Utilities Construction';
const MAIL_TO    = 'info@magellanuc.com';
const MAIL_FROM  = 'info@magellanuc.com';     // mesma caixa que recebe — não existe conta no-reply
const ALERT_TO   = ['info@magellanuc.com', 'victorchiquetti@gmail.com'];
const PHONE_FALLBACK = '(321) 285-5022';
const LOG_PREFIX = 'muc';
const MAX_TOTAL_BYTES = 10485760;  // 10 MB somando todos os anexos
const ALLOWED_EXT = ['pdf','doc','docx','xls','xlsx','csv','txt','rtf','jpg','jpeg','png','gif','webp','heic','zip','dwg','dxf','kmz','kml'];

const THANK_YOU = [
  'en' => '/thank-you/',
  'es' => '/thank-you/',
  'pt' => '/thank-you/',
];

const DEFAULT_FORM = 'bid';
const FORMS = [
  'bid' => [
    'assunto'  => 'Bid request',
    'name'     => 'b-name',
    'email'    => 'b-email',
    'phone'    => 'b-phone',
    'company'  => 'b-company',
    'required' => ['b-name', 'b-company', 'b-email'],
    'campos'   => [
      'b-name' => 'Name',
      'b-company' => 'Company',
      'b-email' => 'Email',
      'b-phone' => 'Phone',
      'b-location' => 'Project location',
      'b-service' => 'Service',
      'b-vertical' => 'Vertical',
      'b-start' => 'Mobilization window',
      'b-desc' => 'Scope',
    ],
  ],
];


// ------------------------------------------------------------------ respostas
$lang      = in_array(($_POST['lang'] ?? 'en'), ['en','es','pt'], true) ? (string)$_POST['lang'] : 'en';
$wantsJson = stripos((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false;
$thankYou  = THANK_YOU[$lang];

function respond_ok(string $redirect, bool $json): void {
    if ($json) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'redirect' => $redirect]);
    } else {
        header('Location: ' . $redirect, true, 303);
    }
    exit;
}

/** @param array<string,string> $errors campo => mensagem ('_form' = erro geral) */
function respond_error(array $errors, int $status, bool $json, string $back): void {
    http_response_code($status);
    if ($json) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'errors' => $errors]);
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    $items = '';
    foreach ($errors as $msg) { $items .= '<li>' . htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') . '</li>'; }
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<meta name="robots" content="noindex"><title>Please check your form — ' . htmlspecialchars(SITE_NAME, ENT_QUOTES, 'UTF-8') . '</title></head>'
       . '<body style="font-family:system-ui,sans-serif;max-width:560px;margin:60px auto;padding:0 20px;color:#1b2a3f">'
       . '<h1 style="font-size:24px">We could not send your form</h1><ul style="line-height:1.7">' . $items . '</ul>'
       . '<p><a href="' . htmlspecialchars($back, ENT_QUOTES, 'UTF-8') . '">&larr; Go back and fix it</a></p>'
       . '<p style="color:#5b6b80">Prefer to talk? Call ' . PHONE_FALLBACK . ' or email <a href="mailto:' . MAIL_TO . '">' . MAIL_TO . '</a>.</p></body></html>';
    exit;
}

// ------------------------------------------------------------------ guardas
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: /', true, 303);
    exit;
}

$back = (string)($_SERVER['HTTP_REFERER'] ?? '/');
if (!preg_match('#^https?://(www\.)?magellanuc\.com/#', $back)) { $back = '/'; }

// Arquivo maior que post_max_size: o PHP descarta tudo e $_POST chega vazio.
if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    respond_error(['_form' => 'The files are too large. Please send up to 10 MB in total, or submit the form without them.'], 413, $wantsJson, $back);
}

// Honeypot: campo escondido que só um robô preenche. Finge sucesso.
if (trim((string)($_POST['website'] ?? '')) !== '') { respond_ok($thankYou, $wantsJson); }

function clean(string $v): string { return trim(str_replace(["\r", "\n", "%0a", "%0d"], ' ', $v)); }
function subj(string $v): string { return '=?UTF-8?B?' . base64_encode($v) . '?='; }
function len(string $v): int { return function_exists('mb_strlen') ? mb_strlen($v, 'UTF-8') : strlen($v); }

$tipo = (string)($_POST['form'] ?? DEFAULT_FORM);
if (!isset(FORMS[$tipo])) { $tipo = DEFAULT_FORM; }
$def    = FORMS[$tipo];
$campos = $def['campos'];

$nome    = clean((string)($_POST[$def['name']] ?? ''));
$email   = clean((string)($_POST[$def['email']] ?? ''));
$phone   = clean((string)($_POST[$def['phone']] ?? ''));
$empresa = isset($def['company']) ? clean((string)($_POST[$def['company']] ?? '')) : '';

// ------------------------------------------------------------------ validação
$errors = [];
foreach ($def['required'] as $k) {
    $v = $_POST[$k] ?? '';
    if (!is_string($v) || trim($v) === '') { $errors[$k] = 'This field is required.'; }
}
if ($nome !== '' && (len($nome) < 2 || len($nome) > 100)) { $errors[$def['name']] = 'Please enter a valid name (2 to 100 characters).'; }
if ($email !== '' && (len($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL))) { $errors[$def['email']] = 'That email address does not look right.'; }
$digits = preg_replace('/\D/', '', $phone) ?? '';
if ($phone !== '' && (strlen($digits) < 7 || strlen($digits) > 15)) { $errors[$def['phone']] = 'That phone number looks incomplete. Please include the area code.'; }
if ($email === '' && $phone === '' && !isset($errors[$def['email']]) && !isset($errors[$def['phone']])) { $errors[$def['phone']] = 'Please give us a phone number or an email.'; }
foreach ($campos as $key => $label) {
    if (isset($_POST[$key]) && is_string($_POST[$key]) && len($_POST[$key]) > 3000) { $errors[$key] = 'This text is too long (3000 characters maximum).'; }
}

// ------------------------------------------------------------------ anexos
$anexos = []; $total = 0;
$uploadErr = [
    UPLOAD_ERR_INI_SIZE => 'The file is too large.', UPLOAD_ERR_FORM_SIZE => 'The file is too large.',
    UPLOAD_ERR_PARTIAL => 'The file did not finish uploading. Please try again.',
    UPLOAD_ERR_NO_TMP_DIR => 'We could not receive the file right now.', UPLOAD_ERR_CANT_WRITE => 'We could not receive the file right now.',
    UPLOAD_ERR_EXTENSION => 'We could not receive the file right now.',
];
foreach ($_FILES as $campo => $f) {
    $nomes = is_array($f['name']) ? $f['name'] : [$f['name']];
    $tmps  = is_array($f['tmp_name']) ? $f['tmp_name'] : [$f['tmp_name']];
    $errs  = is_array($f['error']) ? $f['error'] : [$f['error']];
    foreach ($nomes as $i => $arq) {
        $code = $errs[$i] ?? UPLOAD_ERR_NO_FILE;
        if ($code === UPLOAD_ERR_NO_FILE) continue;
        if ($code !== UPLOAD_ERR_OK) { $errors[$campo] = $uploadErr[$code] ?? 'The file could not be uploaded.'; continue; }
        if (!is_uploaded_file($tmps[$i])) continue;
        $ext = strtolower(pathinfo((string)$arq, PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EXT, true)) { $errors[$campo] = 'That file type is not accepted.'; continue; }
        $tam = (int)filesize($tmps[$i]);
        if ($total + $tam > MAX_TOTAL_BYTES) { $errors[$campo] = 'The files are too large. Up to 10 MB in total.'; continue; }
        $total += $tam;
        $anexos[] = ['name' => preg_replace('/[^A-Za-z0-9._-]/', '_', basename((string)$arq)), 'data' => (string)file_get_contents($tmps[$i])];
    }
}
if ($errors) { respond_error($errors, 422, $wantsJson, $back); }

// ------------------------------------------------------------------ corpo
$ref = strtoupper(date('ymd') . '-' . substr(bin2hex(random_bytes(3)), 0, 5));
$linhas = ['Reference: ' . $ref, 'Form: ' . $def['assunto'], ''];
foreach ($campos as $key => $label) {
    $valor = trim((string)($_POST[$key] ?? ''));
    if ($valor === '') continue;
    $linhas[] = $label . ': ' . $valor;
}
foreach ($_POST as $key => $valor) {   // qualquer campo novo que ainda não esteja mapeado
    if (isset($campos[$key]) || in_array($key, ['website','lang','form'], true)) continue;
    if (!is_string($valor) || trim($valor) === '') continue;
    $linhas[] = ucfirst(str_replace(['-','_'], ' ', $key)) . ': ' . trim($valor);
}
if ($anexos) { $linhas[] = 'Attachments: ' . implode(', ', array_column($anexos, 'name')); }
$linhas[] = '';
$linhas[] = str_repeat('-', 46);
$linhas[] = 'Sent from ' . ($_SERVER['HTTP_HOST'] ?? '') . ' (' . ($_SERVER['HTTP_REFERER'] ?? '') . ')';
$linhas[] = 'Date: ' . date('Y-m-d H:i:s T');
$linhas[] = 'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$corpo   = implode("\n", $linhas);
$subject = $def['assunto'] . ' — ' . $nome . ($empresa !== '' ? ' (' . $empresa . ')' : '') . ' [' . $ref . ']';

// ------------------------------------------------------------------ 1) gravar antes de qualquer e-mail
$dir = __DIR__ . '/../form-logs';
function dir_ready(string $d): bool { return (is_dir($d) || @mkdir($d, 0700, true)) && is_writable($d); }
function log_text(string $d, string $file, string $text): bool { return @file_put_contents($d . '/' . $file, $text, FILE_APPEND | LOCK_EX) !== false; }

$saved = false; $savedFiles = [];
if (dir_ready($dir)) {
    foreach ($anexos as $a) {
        $p = $dir . '/' . LOG_PREFIX . '-' . $ref . '_' . $a['name'];
        if (@file_put_contents($p, $a['data']) !== false) { $savedFiles[] = basename($p); }
    }
    $saved = log_text($dir, LOG_PREFIX . '-submissoes-' . date('Y-m') . '.log',
        str_repeat('=', 64) . "\nDATA: " . date('Y-m-d H:i:s T') . "\nREF: {$ref}\nFORM: " . LOG_PREFIX . '-' . $tipo
        . "\nEMAIL ENVIADO: pendente (resultado na linha RESULTADO abaixo)\n" . str_repeat('-', 64) . "\n" . $corpo
        . ($savedFiles ? "\nANEXOS SALVOS: " . implode(', ', $savedFiles) : '') . "\n\n");
}

// ------------------------------------------------------------------ 2) e-mail para a equipe
function build_message(string $body, array $anexos, array &$headers): string {
    if (!$anexos) { $headers[] = 'Content-Type: text/plain; charset=UTF-8'; return $body; }
    $b = 'mg' . bin2hex(random_bytes(12));
    $headers[] = 'Content-Type: multipart/mixed; boundary="' . $b . '"';
    $p = "--{$b}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n" . $body . "\r\n\r\n";
    foreach ($anexos as $a) {
        $p .= "--{$b}\r\nContent-Type: application/octet-stream; name=\"{$a['name']}\"\r\nContent-Transfer-Encoding: base64\r\n"
            . "Content-Disposition: attachment; filename=\"{$a['name']}\"\r\n\r\n" . chunk_split(base64_encode($a['data'])) . "\r\n";
    }
    return $p . "--{$b}--";
}
$replyTo = $email !== '' ? $email : MAIL_FROM;
$headers = ['From: ' . SITE_NAME . ' Website <' . MAIL_FROM . '>', 'Reply-To: ' . $replyTo, 'MIME-Version: 1.0'];
$payload = build_message($corpo, $anexos, $headers);
$mailed  = @mail(MAIL_TO, subj($subject), $payload, implode("\r\n", $headers), '-f' . MAIL_FROM);
if (!$mailed) { $mailed = @mail(MAIL_TO, subj($subject), $payload, implode("\r\n", $headers)); }

if (dir_ready($dir)) {
    log_text($dir, LOG_PREFIX . '-submissoes-' . date('Y-m') . '.log', "RESULTADO {$ref}: EMAIL ENVIADO: " . ($mailed ? 'sim' : 'NAO') . "\n\n");
}

// ------------------------------------------------------------------ 3) falhou: alertar
if (!$mailed) {
    if (dir_ready($dir)) { log_text($dir, LOG_PREFIX . '-falhas.log', date('c') . " {$ref} mail() retornou false; salvo=" . ($saved ? 'sim' : 'NAO') . "\n"); }
    $ah = ['From: ' . SITE_NAME . ' Website <' . MAIL_FROM . '>', 'MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8'];
    $ab = 'The website could not email a new ' . $def['assunto'] . ".\n\nReference: {$ref}\nName: {$nome}\nCompany: {$empresa}\nPhone: {$phone}\nEmail: {$email}\n\n"
        . ($saved ? 'The full submission is SAVED on the server in form-logs/' . LOG_PREFIX . '-submissoes-' . date('Y-m') . ".log (look up the reference above). Nothing was lost."
                  : 'WARNING: it could NOT be saved on the server either. Contact the person using the details above.')
        . "\n\nDate: " . date('Y-m-d H:i:s T') . "\n";
    foreach (ALERT_TO as $to) { @mail($to, subj('[ACTION NEEDED] Website form failed — ' . $ref), $ab, implode("\r\n", $ah)); }
}
if (!$saved && !$mailed) {
    respond_error(['_form' => 'We could not receive your form right now. Please call ' . PHONE_FALLBACK . ' or email ' . MAIL_TO . '.'], 500, $wantsJson, $back);
}

// ------------------------------------------------------------------ 4) confirmação para quem preencheu
if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $T = [
      'en' => ['We received your request — ' . SITE_NAME, "Hi {$nome},\n\nWe received your request. Thank you for contacting " . SITE_NAME . ".\n\nYour reference number is {$ref}. We will get back to you shortly.\nIf you need us sooner, call " . PHONE_FALLBACK . ".\n\n" . SITE_NAME . "\n"],
      'es' => ['Recibimos su solicitud — ' . SITE_NAME, "Hola {$nome},\n\nRecibimos su solicitud. Gracias por contactar a " . SITE_NAME . ".\n\nSu número de referencia es {$ref}. Le responderemos pronto.\nSi nos necesita antes, llame al " . PHONE_FALLBACK . ".\n\n" . SITE_NAME . "\n"],
      'pt' => ['Recebemos sua solicitação — ' . SITE_NAME, "Olá {$nome},\n\nRecebemos sua solicitação. Obrigado por entrar em contato com a " . SITE_NAME . ".\n\nSeu número de referência é {$ref}. Responderemos em breve.\nSe precisar de nós antes, ligue para " . PHONE_FALLBACK . ".\n\n" . SITE_NAME . "\n"],
    ];
    $ackHeaders = ['From: ' . SITE_NAME . ' <' . MAIL_FROM . '>', 'Reply-To: ' . MAIL_TO, 'MIME-Version: 1.0', 'Content-Type: text/plain; charset=UTF-8'];
    @mail($email, subj($T[$lang][0]), $T[$lang][1], implode("\r\n", $ackHeaders));
}

respond_ok($thankYou . '?ref=' . rawurlencode($ref), $wantsJson);
