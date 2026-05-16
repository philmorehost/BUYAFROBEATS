<?php
require_once __DIR__ . '/../../includes/Core.php';
session_start();
session_destroy();
header('Location: ../../index.php');
exit;
