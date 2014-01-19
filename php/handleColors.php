<?php

  require_once($_SERVER['DOCUMENT_ROOT'] . '/php/header.php');

  $colors = new colors($pdo);

  if(isset($_REQUEST['id'])) {

    $_SESSION['choices'][] = $_REQUEST['id'];

    if(count($_SESSION['choices']) == 5 ) {
      $form = $colors->getSignUpForm();
      print $form;
      exit;
    }

  }
  else {

    $_SESSION['visit_id'] = $colors->startColor();

  }

  $schemes = $colors->getSchemes();

  $scheme = array();
  foreach ($schemes AS $s) {
    $scheme[] = '<div class="scheme" id="' . $s['id'] . '"><div class="color" style="background-color:#' . $s['color_1'] . '"></div><div class="color" style="background-color:#' . $s['color_2'] . '"></div><div class="color" style="background-color:#' . $s['color_3'] . '"></div></div>';
  }

  foreach ($scheme AS $s) {
    print $s;
  }



?>