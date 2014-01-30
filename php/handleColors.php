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
  elseif(isset($_REQUEST['signUp'])) {
    if(isset($_REQUEST['status'])) {
      $status = $_REQUEST['status'];
    }
    if(isset($_REQUEST['email'])) {
      $email = $_REQUEST['email'];
    }

    $result = $colors->signUp($status,$email);

    print '<p>Thank you.  You should recieve and email with potential matches shortly.</p>';
    exit;

  }
  elseif(isset($_REQUEST['m'])) {
    $m = $_REQUEST['m'];
    $form = $colors->getMessageForm($m);

    print $form;
    exit;

  }
  elseif(isset($_REQUEST['submitMessage'])) {

    $message = $_REQUEST['message'];
    $receiver = $_REQUEST['receiverGuid'];
    $caller = $_REQUEST['callerGuid'];

    $response = $colors->submitMessage($message,$receiver,$caller);

    if($response) {
      print '<p>Check your email to finalize your message.</p>';
    }
    else {
      print '<p>An error occurred, please try again later.</p>';
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