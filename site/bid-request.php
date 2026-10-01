<?php
/**
 * Magellan Utilities Construction — recebimento do formulário do site.
 * Mesmo padrão do send.php da BlueOcean / AMT / Magellan Group:
 * mail() da HostGator, honeypot, anexos multipart e log de TODA submissão
 * fora do public_html como rede de segurança caso o e-mail falhe.
 *
 * ⚠ O Email Routing deste domínio PRECISA estar em "Remote" no cPanel.
 *   O MX é do Zoho; com o roteamento em "Local" o mail() entrega numa caixa
 *   local que ninguém abre e todo pedido some sem erro.
 */

declare(strict_types=1);

const MAIL_TO   = 'info@magellanuc.com';
const MAIL_FROM = 'no-reply@magellanuc.com';      // precisa ser do próprio domínio (SPF)
const THANK_YOU = '/thank-you/';
const LOG_DIR   = '/home2/lhsjeste/form-logs';    // fora do public_html
const MAX_TOTAL_BYTES = 10485760;                 // 10 MB somando os anexos
const ALLOWED_EXT = ['pdf','doc','docx','xls','xlsx','csv','txt','rtf',
                     'jpg','jpeg','png','gif','webp','heic','zip','dwg','dxf','kmz','kml'];

const CAMPOS = [
  'b-name'     => 'Name',
  'b-company'  => 'Company',
  'b-email'    => 'Email',
  'b-phone'    => 'Phone',
  'b-location' => 'Project location',
  'b-service'  => 'Service',
  'b-vertical' => 'Vertical',
  'b-start'    => 'Mobilization window',
  'b-desc'     => 'Scope',
];

function limpa(string $v): string {
    return trim(preg_replace('/[\r\n]+/', ' ', strip_tags($v)));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /', true, 302); exit;
}

// honeypot: bot preenche, humano não vê
if (!empty($_POST['website'] ?? '')) {
    header('Location: ' . THANK_YOU, true, 302); exit;
}

$linhas = [];
foreach (CAMPOS as $campo => $rotulo) {
    $v = limpa((string)($_POST[$campo] ?? ''));
    if ($v !== '') $linhas[] = $rotulo . ': ' . $v;
}
if (!$linhas) { header('Location: /', true, 302); exit; }

$email   = limpa((string)($_POST['b-email'] ?? ''));
$replyTo = filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : MAIL_FROM;
$empresa = limpa((string)($_POST['b-company'] ?? 'unknown'));
$assunto = 'Bid request — ' . ($empresa !== '' ? $empresa : 'website');

$meta = [
  'Received: ' . gmdate('Y-m-d H:i:s') . ' UTC',
  'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? '-'),
  'Page: ' . limpa((string)($_SERVER['HTTP_REFERER'] ?? '-')),
];
$corpoTexto = implode("\n", $linhas) . "\n\n---\n" . implode("\n", $meta) . "\n";

// ---- anexos
$anexos = []; $total = 0;
if (!empty($_FILES['b-file']['name'][0])) {
    $n = count($_FILES['b-file']['name']);
    for ($i = 0; $i < $n; $i++) {
        if ((int)$_FILES['b-file']['error'][$i] !== UPLOAD_ERR_OK) continue;
        $nome = basename((string)$_FILES['b-file']['name'][$i]);
        $ext  = strtolower((string)pathinfo($nome, PATHINFO_EXTENSION));
        if (!in_array($ext, ALLOWED_EXT, true)) continue;
        $tam = (int)$_FILES['b-file']['size'][$i];
        if ($total + $tam > MAX_TOTAL_BYTES) break;
        $dados = @file_get_contents((string)$_FILES['b-file']['tmp_name'][$i]);
        if ($dados === false) continue;
        $total += $tam;
        $anexos[] = ['nome' => preg_replace('/[^\w.\- ]/', '_', $nome), 'dados' => $dados];
    }
}

// ---- log (rede de segurança: vale mesmo se o mail() falhar)
if (!is_dir(LOG_DIR)) @mkdir(LOG_DIR, 0700, true);
@file_put_contents(
    LOG_DIR . '/magellanuc-submissoes-' . gmdate('Y-m') . '.log',
    "==== " . gmdate('c') . " ====\n" . $corpoTexto . 'Anexos: ' . count($anexos) . "\n\n",
    FILE_APPEND | LOCK_EX
);

// ---- monta o e-mail
$limite  = '=_mguc_' . bin2hex(random_bytes(12));
$headers = [
  'From: Magellan Utilities Construction Website <' . MAIL_FROM . '>',
  'Reply-To: ' . $replyTo,
  'MIME-Version: 1.0',
  'Content-Type: multipart/mixed; boundary="' . $limite . '"',
  'X-Mailer: magellanuc-send.php',
];

$corpo  = "--$limite\r\n";
$corpo .= "Content-Type: text/plain; charset=UTF-8\r\n";
$corpo .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$corpo .= $corpoTexto . "\r\n";
foreach ($anexos as $a) {
    $corpo .= "--$limite\r\n";
    $corpo .= 'Content-Type: application/octet-stream; name="' . $a['nome'] . "\"\r\n";
    $corpo .= 'Content-Disposition: attachment; filename="' . $a['nome'] . "\"\r\n";
    $corpo .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $corpo .= chunk_split(base64_encode($a['dados'])) . "\r\n";
}
$corpo .= "--$limite--\r\n";

@mail(MAIL_TO, $assunto, $corpo, implode("\r\n", $headers), '-f' . MAIL_FROM);

header('Location: ' . THANK_YOU, true, 302);
exit;
