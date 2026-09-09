<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Метод не поддерживается'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!empty($_POST['website'] ?? '')) {
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    exit;
}

$now = time();
$last = (int)($_SESSION['last_request_at'] ?? 0);
if ($last && ($now - $last) < 20) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Повторите отправку через несколько секунд'], JSON_UNESCAPED_UNICODE);
    exit;
}

function clean_value(string $value, int $max): string {
    $value = trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    return mb_substr($value, 0, $max);
}

$name = clean_value((string)($_POST['name'] ?? ''), 100);
$phone = clean_value((string)($_POST['phone'] ?? ''), 40);
$email = clean_value((string)($_POST['email'] ?? ''), 150);
$product = clean_value((string)($_POST['product'] ?? ''), 160);
$message = clean_value((string)($_POST['message'] ?? ''), 1500);

if ($name === '' || $phone === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Укажите имя и телефон'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!preg_match('/^[0-9+()\-\s]{7,40}$/u', $phone)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Проверьте номер телефона'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Проверьте e-mail'], JSON_UNESCAPED_UNICODE);
    exit;
}

$to = getenv('KASPOL_FORM_TO') ?: '';
if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'error' => 'Форма пока не настроена. Используйте телефон или e-mail из раздела «Контакты».'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$subject = 'Заявка с сайта Каспий Полимер';
$body =
    "Имя: {$name}\n" .
    "Телефон: {$phone}\n" .
    "E-mail: {$email}\n" .
    "Продукция: {$product}\n\n" .
    "Комментарий:\n{$message}\n\n" .
    "IP: " . ($_SERVER['REMOTE_ADDR'] ?? '');

$host = preg_replace('/[^a-z0-9.-]/i', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
$headers = [
    'Content-Type: text/plain; charset=UTF-8',
    "From: website@{$host}"
];

if ($email !== '') {
    $headers[] = 'Reply-To: ' . $email;
}

$sent = @mail(
    $to,
    '=?UTF-8?B?' . base64_encode($subject) . '?=',
    $body,
    implode("\r\n", $headers)
);

if (!$sent) {
    http_response_code(502);
    echo json_encode([
        'ok' => false,
        'error' => 'Не удалось отправить заявку. Используйте телефон или e-mail из раздела «Контакты».'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$_SESSION['last_request_at'] = $now;
echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
