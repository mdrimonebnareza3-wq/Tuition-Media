<?php
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/functions.php';

session_unset();
session_destroy();
session_start();

set_flash('success', 'You have been logged out.');
redirect('login.php');
