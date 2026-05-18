<?php
require_once __DIR__ . '/includes/functions.php';
redirect(is_logged_in() ? role_home(current_user()['role']) : 'login.php');
