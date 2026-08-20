<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');
require_post();
$in = request_input();
require_csrf($in);

$username = input_str($in, 'username');
$password = (string)($in['password'] ?? '');

if ($username === '' || $password === '') {
    json_err('Kullanıcı adı ve şifre zorunludur.', 422);
}
if (!attempt_login($username, $password)) {
    json_err('Kullanıcı adı veya şifre hatalı.', 401);
}
json_ok(['user' => current_user(), 'redirect' => url(home_for_role(current_user()['role']))]);
