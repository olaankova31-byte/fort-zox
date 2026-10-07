<?php
// Общие комментарии для прототипа: хранит их на сервере и присылает на почту.
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Europe/Moscow');
$file = __DIR__ . '/comments.data.php';
$to = 'olaankova31@gmail.com';

$rows = [];
if (is_file($file)) {
    $lines = file($file, FILE_IGNORE_NEW_LINES);
    array_shift($lines); // первая строка закрывает файл от прямого чтения
    foreach ($lines as $line) {
        $r = json_decode($line, true);
        if ($r) {
            if (!isset($r['p'])) $r['p'] = 'phys'; // старые комментарии со страницы "Физическая защита"
            $rows[] = $r;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $p = json_decode(file_get_contents('php://input'), true);
    $t = trim($p['t'] ?? '');
    if ($t === '' || mb_strlen($t) > 3000) {
        http_response_code(400);
        echo '{"ok":false}';
        exit;
    }
    $r = [
        'd' => date('d.m.Y H:i'),
        'p' => mb_substr($p['p'] ?? '', 0, 10),
        'b' => mb_substr($p['b'] ?? '', 0, 10),
        'l' => mb_substr($p['l'] ?? '', 0, 80),
        'n' => mb_substr($p['n'] ?? '', 0, 60),
        't' => $t,
    ];
    if (!is_file($file)) file_put_contents($file, "<?php exit; ?>\n");
    file_put_contents($file, json_encode($r, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
    $subject = '=?UTF-8?B?' . base64_encode('Комментарий · ' . $r['l'] . ' · ФОРТ ЗОКС') . '?=';
    $body = $r['l'] . "\nДата: " . $r['d'] . "\nАвтор: " . $r['n'] . "\n\n" . $r['t'];
    @mail($to, $subject, $body, "Content-Type: text/plain; charset=UTF-8\r\n");
    echo '{"ok":true}';
    exit;
}

echo json_encode($rows, JSON_UNESCAPED_UNICODE);
