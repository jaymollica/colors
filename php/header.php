<?php

if(preg_match('/local/i', $_SERVER['HTTP_HOST'])) {
  $pdo = new PDO('mysql:dbname=colors','root','root');
  
}

session_start();

include($_SERVER['DOCUMENT_ROOT'] . '/php/colors.php');

?>