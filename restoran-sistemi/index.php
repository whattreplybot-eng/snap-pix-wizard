<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
redirect(home_for_role(current_user()['role']));
