<?php

if(preg_match('/local/i', $_SERVER['HTTP_HOST'])) {
  $pdo = new PDO('mysql:dbname=colors','root','root');
}
else {
  $pdo = new PDO('mysql:host=mysql.vaguespace.org;dbname=colors_vs','mollja2','ivory041183');
}

session_start();

include('colors.php');

?>