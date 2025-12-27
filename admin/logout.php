<?php
session_start();
session_destroy();
require_once __DIR__ . '/../includes/anti_inspect.php';
header("Location: /ska");

?>