<?php

  require_once('header.php');

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

    $html = '<html>';
    $html .= '<head>';
    $html .= '<link rel="stylesheet" type="text/css" href="../css/colors.css" />
              <script src="../js/jquery-1.9.1.min.js"></script>';

    $html .= <<<EOF

    <script>

      $(document).ready(function(){

        $(document).on('click', '#submitMessage', function(){

          var url = 'handleColors.php';
          var o = {'submitMessage': true};

          var a = $('#message').serializeArray();

          $.each(a, function() {
            if (o[this.name] !== undefined) {
              if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
              }
              o[this.name].push(this.value || '');
            } else {
              o[this.name] = this.value || '';
            }
          });

          var requestData = o;

          $.post(url,requestData, function(data) {
            $("body").empty().append(data);
          });

        });

      });

    </script>

EOF;

    $html .= '</head>';
    $html .= '<body>';

    $html .= $form;

    $html .= '</body></html>';

    print $html;

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
  elseif(isset($_REQUEST['h'])) {
    //someone has clicked a link in their email to finalize a message
    $hash = $_REQUEST['h'];
    $caller = $_REQUEST['c'];
    $receiver = $_REQUEST['r'];

    $response = $colors->validateMessage($hash,$receiver,$caller);

    if($response) {
      print '<pre>response: '; print_r($response); print '</pre>';
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