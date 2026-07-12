<?php
unset($_SESSION['user']);
session_regenerate_id(true);
header('Location: ' . BASE_PATH . '/');
exit;
