<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json; charset=utf-8');
logout();
json_ok(['redirect' => url('/login.php')]);
