<?php

  include($_SERVER['DOCUMENT_ROOT'] . '/php/header.php');
  include($_SERVER['DOCUMENT_ROOT'] . '/php/colors.php');

  $colors = new colors($pdo);

  if(isset($_REQUEST['id'])) {

    $_SESSION['choices'][] = $_REQUEST['id'];

    if(count($_SESSION['choices']) == 5 ) {
      $status = $colors->getSignUpForm();
      
      echo $twig->render('signup.html', array('status' => $status));
      exit;
    }

    $schemes = $colors->getSchemes();

    $instructions = '<p id="instructions">Choose your favorite color scheme...</p>';

    echo $twig->render('choices.html', array('instructions' => $instructions, 'schemes' => $schemes));

  }
  elseif(isset($_REQUEST['signUp'])) {
    if(isset($_REQUEST['status'])) {
      $status = $_REQUEST['status'];
    }
    if(isset($_REQUEST['email'])) {
      $email = $_REQUEST['email'];
    }

    $result = $colors->signUp($status,$email);

    echo $twig->render('handleSignUp.html', array('result' => $result));
    exit;

  }
  elseif(isset($_REQUEST['m'])) {
    $m = $_REQUEST['m']; //receiver guid
    $c = $_REQUEST['c']; //caller guid
    $h = $_REQUEST['h']; //hash

    $ret = $colors->getMessageForm($m,$c,$h);

    echo $twig->render('messageForm.html', array('instructions' => $ret['instructions'], 'm' => $m, 'c' => $c, 'h' => $h));

    exit;

  }
  elseif(isset($_REQUEST['submitMessage'])) {

    $message = $_REQUEST['message'];
    $receiver = $_REQUEST['receiverGuid'];
    $caller = $_REQUEST['callerGuid'];
    $hash = $_REQUEST['h'];

    $result = $colors->submitMessage($message,$receiver,$caller,$hash);

    echo $twig->render('handleSignUp.html', array('result' => $result));
    exit;

  }
  else {

    $_SESSION['visit_id'] = $colors->startColor();

    $schemes = $colors->getSchemes();

    $instructions = '<p id="instructions">Choose your favorite color scheme...</p>';

    echo $twig->render('base_header.html',array());
    echo $twig->render('choices.html', array('instructions' => $instructions, 'schemes' => $schemes));
    echo $twig->render('base_footer.html',array());


  }

?>