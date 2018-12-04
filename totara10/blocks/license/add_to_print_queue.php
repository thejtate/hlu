<?php
require_once('includes.php');

if(isset($_POST['user'])) {
    if(isset($_SESSION['print_users'])){
        if(key_exists($_POST['user'], $_SESSION['print_users'])){        
            // remove from list     
            unset($_SESSION['print_users'][$_POST['user']]);
        } else {
            // add to list
            $_SESSION['print_users'][$_POST['user']] = $_POST['employee'];
        }
    } else {
        $_SESSION['print_users'] = array();
        $_SESSION['print_users'][$_POST['user']] = $_POST['employee'];
    }
}
?>