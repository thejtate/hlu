<?php
require_once('includes.php');
session_start();
unset($_SESSION['print_users']);
header('Location: ' . $_SERVER['HTTP_REFERER']);
?>