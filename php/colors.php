<?php

  class colors {

    protected $_db;

    public function __construct(PDO $db) {
      $this->_db = $db;
    }

    public function startColor() {
      $visitId = uniqid (rand(), true);

      $_SESSION = '';

      return $visitId;
    }

    public function getSchemes() {

      //select3 random color schemes
      $sql = $this->_db->prepare("SELECT * FROM schemes ORDER BY rand() LIMIT 3");
      $sql->execute();
        if($sql->rowCount() > 0) {
          $schemes = $sql->fetchAll(PDO::FETCH_ASSOC);

          $i = 0;

          foreach($schemes AS $s) {

            $schemes[$i] = $s;

            $i++;

          } 
        }
      return $schemes;
    }

    public function getSignUpForm() {

      //after user exhausts choices, show them the sign up form
      $sql = $this->_db->prepare("SELECT * FROM statuses ORDER BY ID ASC");
      $sql->execute();
        if($sql->rowCount() > 0) {
          $statuses = $sql->fetchAll(PDO::FETCH_ASSOC);

          $i = 0;

          foreach($statuses AS $s) {

            $status[$i] = $s;

            $i++;

          } 
        }

      $form = '';
      $form .= '<p>I am a...</p>';
      $form .= '<form id="signUp">';

      foreach($status AS $s) {
        $form .= '<div class="statusRow"><label><input type="radio" name="status" value="' . $s['short_desc'] . '" />' . $s['description'] . '</label></div>';
      }

      $form .= '<p>Email me with potential matches.</p>';

      $form .= '<div class="emailBox"><input type="email" name="email" /></div>';

      $form .= '<div class="submitBox"><input type="button" id="submit" value="Submit" /></div>';

      $form .= '</form>';

      return $form;

    }

    public function signUp($status,$email) {

      //process signup form

      //validate email
      if (!preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/",$email)) {
        print '<p>An invalid email address was entered.</p>'; exit;        
      }

      //explode status so that m4m splits into m,m
      $parts = explode("4",$status);

      //check to see if the email was already entered, if so update, if not create new entry
      $sql = $this->_db->prepare("SELECT * FROM visitors WHERE email = ?");
      $sql->execute(array($email));
      if($sql->rowCount() > 0) {
        $res = $sql->fetch(PDO::FETCH_ASSOC);
        $uid = $res['id'];
        $_SESSION['guid'] = $res['guid'];
        $sql = $this->_db->prepare("UPDATE visitors SET session_id = ?, num_entries = num_entries + 1, gender = ?, looking_for = ? WHERE email = ?");
        $sql->execute(array($_SESSION['visit_id'],$parts[0],$parts[1],$email));
      }
      else {
        while(TRUE) {
          $guid = $this->randGuid();

          $_SESSION['guid'] = $guid;

          $sql = $this->_db->prepare("SELECT * FROM visitors WHERE guid = ?");
          $sql->execute(array($guid));
          if($sql->rowCount() == 0) {
            $sql = $this->_db->prepare("INSERT INTO visitors (session_id,email,num_entries,gender,looking_for,guid) VALUES (?,?,?,?,?,?)");
            $sql->execute(array($_SESSION['visit_id'],$email,1,$parts[0],$parts[1],$guid));
            $uid = $this->_db->lastInsertId();
            break;
          }
        }
      }

      //log users color preferences, if they have liked a color more than once increase the likes, if it's new to them add it into the db
      foreach($_SESSION['choices'] AS $choice) {
        $sql = $this->_db->prepare("SELECT * FROM scheme_likes WHERE scheme_id = ? AND user_id = ?");
        $sql->execute(array($choice,$uid));
        if($sql->rowCount() > 0) {
          $sql = $this->_db->prepare("UPDATE scheme_likes SET num_likes = num_likes +1 WHERE scheme_id = ? AND user_id = ?");
          $sql->execute(array($choice,$uid));
        }
        else {
          $sql = $this->_db->prepare("INSERT INTO scheme_likes (scheme_id,user_id,num_likes) VALUES (?,?,1)");
          $sql->execute(array($choice,$uid));
        }
      }

      //return an array of matched email addresses
      $matchedEmails = $this->getMatchesA($uid);

      //compose email with potential matches
      $mail = $this->emailMatches($matchedEmails,$email);

      if($mail) {
        print '<p>Thank you, an email with any potential matches should arrive soon.</p>';
      }
      else {
        '<p>A problem has occured, please try again later.</p>';
      }
      
      exit;

    }

    public function getMatchesA($uid) {

      //get a list of schemes the user likes, then return all users who also like those schemes
      $sql = $this->_db->prepare("SELECT scheme_id FROM scheme_likes WHERE user_id = ?");
      $sql->execute(array($uid));
      if($sql->rowCount() > 0) {
        $res = $sql->fetchAll();
        foreach($res AS $row) {
          $sid = $row['scheme_id'];
          $sql = $this->_db->prepare("SELECT user_id FROM scheme_likes WHERE scheme_id = ? AND user_id != ?");
          $sql->execute(array($sid,$uid));
          if($sql->rowCount() > 0) {
            while($users = $sql->fetch(PDO::FETCH_ASSOC)) {
              $ids[] = $users['user_id'];;
            }
          }
        }

        //count the number of times each user has the same likes, sort by most incommon to least, return the top 5
        if(count($ids) > 0) {
          $counts = array_count_values($ids);
        
          ksort($counts);

          $i = 0;
          foreach($counts AS $key => $val) {

            $sql = $this->_db->prepare("SELECT * FROM visitors WHERE id = ?");
            $sql->execute(array($key));

            $res = $sql->fetch(PDO::FETCH_ASSOC);

            $matches[] = $res;

            $i++;

            if($i >= 4) {
              break;
            }

          }

          return $matches;

        }
        else {
          print '<p>no match</p>';
        }
        
      }

    }

    public function emailMatches($matches,$visitorEmail) {

      $to = $visitorEmail;

      $subject = 'Your color matches have arrived!';
      $from = 'Do-Not-Reply@color.com';

      $headers = "From: " . $from . "\r\n";
      $headers .= "MIME-Version: 1.0\r\n";
      $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

      $message = '<html><body>';
      $message .= '<p>Below is a list of people who best match your color preferences.  The links will take you to a form where you can contact them if you wish.</p>';

      $message .= '<ol>';
      $i = 0;

      foreach($matches AS $m) {

        $docroot = $_SERVER['DOCUMENT_ROOT'];
        $host = $_SERVER['HTTP_HOST'];

        $message .= '<li><a href = "http://' . $host . '/php/handleColors.php?m=' . $m['guid'] . '">' . $m['guid'] . '</a></li>';

      }

      $message .= '</ol>';
      $message .= '</body></html>';

      //print $message;

      return mail($to, $subject, $message, $headers);      

    }

    public function getMessageForm($m) {

      $form = '';
      $form .= '<p>Send your match an introduction.  Tell them how you found this site and include some interesting details about yourself.</p>';
      $form .= '<form id="message">';
      $form .= '<textarea name="message"></textarea>';
      $form .= '<input type="button" name="submitMessage" id="submitMessage" value="Send!" />';
      $form .= '<input type="hidden" name="receiverGuid" value="' . $m . '" />';
      $form .= '<input type="hidden" name="callerGuid" value="' . $_SESSION['guid'] . '" />';
      $form .= '</form>';

      return $form;      

    }

    public function submitMessage($message,$receiver,$caller) {

      //insert message into db

      $hash = $this->makeHash();

      $sql = $this->_db->prepare("INSERT INTO messages (message,caller_guid,receiver_guid,hash) VALUES (?,?,?,?)");
      $sql->execute(array($message,$caller,$receiver,$hash));

      //send validation email to caller
      $sql = $this->_db->prepare("SELECT * FROM visitors WHERE guid = ?");
      $sql->execute(array($caller));
      if($sql->rowCount() > 0) {
        $res = $sql->fetch(PDO::FETCH_ASSOC);
        $callerEmail = $res['email'];

        $host = $_SERVER['HTTP_HOST'];

        $to = $callerEmail;
        $subject = 'One last step before your message is sent.';

        $from = 'Do-Not-Reply@color.com';

        $headers = "From: " . $from . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

        $message = '<html><body>';
        $message .= '<p>Click the link to send your message.</p>';
        $message .= '<p><a href="http://' . $host .'/php/handleColors.php/?h=' . $hash . '&c=' . $caller . '&r=' . $receiver .'">http://' . $host .'/send/?h=' . $hash . '&c=' . $caller . '&r=' . $receiver .'</a></p>';

        $message .= '</body></html>';

        return mail($to, $subject, $message, $headers);

      }      

    }

    public function validateMessage($hash,$receiver,$caller) {

      $sql = $this->_db->prepare("SELECT * FROM messages WHERE hash=? AND receiver_guid=? AND caller_guid=?");
      $sql->execute(array($hash,$receiver,$caller));
      if($sql->rowCount() > 0) {  //a message was found
        $res = $sql->fetch(PDO::FETCH_ASSOC);
        $message = $res['message'];
        $mid = $res['id'];
        if($res['sent'] == 1) { //the message was already sent
          print '<p>You have already sent this message, please wait for a response from them.</p>';
          exit;
        }
        else { //the message has not been sent yet
          //retrieve sender and receiver emails via their guids

          $sql = $this->_db->prepare("SELECT * FROM visitors WHERE guid=? OR guid=?");
          $sql->execute(array($receiver,$caller));
          if($sql->rowCount() == 2) {  //a message was found
            $visitors = $sql->fetchAll(PDO::FETCH_ASSOC);
            foreach($visitors AS $v) {
              if($v['guid'] == $receiver) {
                $email_r = $v['email'];
              }
              elseif($v['guid'] == $caller) {
                $email_c = $v['email'];
              }

            }

            if(isset($email_c) && isset($email_r)) {
              //if both emails are set send the message

              $to = $email_r;

              $subject = 'Someone has sent you a message!';
              $from = 'Do-Not-Reply@color.com';

              $headers = "From: " . $from . "\r\n";
              $headers .= "MIME-Version: 1.0\r\n";
              $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
              $body = '<html><body>';

              $body .= '<p>' . $message . '</p>';

              $body .= '<p>Our work here is done!  If you&rsquo;r interested in replying to this message you can reach this person at: <a href="mailto:' . $email_c . '">' . $email_c . '</a>';

              $body .= '</body></html>';

              $mail = mail($to, $subject, $body, $headers);

              print '<pre>r: '; print_r($mail); print '</pre>';

              if($mail) {
                //if the email was successfully sent update the message status to sent so that it cannot be sent multiple times
                $sql = $this->_db->prepare("UPDATE messages SET sent=1 WHERE id = ?");
                $sql->execute(array($mid));
              }
              else {
                print '<p>Something went wrong and your email was not delivered.</p>';
              }

              return $mail;

            }
            else { //the query should always return exactly 2 records
              print '<p>An error has occurred.</p>';
              $res = $sql->fetchAll(PDO::FETCH_ASSOC);
              print '<pre>res: '; print_r($res); print '</pre>';
              exit;
            }
            
          }

        }

      }
      else {
        print '<p>Your message was not found, please try again later.</p>';
        exit;
      }

    }

    public function makeHash() {
      $hash = md5(rand(0,1000));
      return $hash;
    }

    public function randGuid() {
      $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
      $guid = '';
      for ($i = 0; $i < 32; $i++) {
          $guid .= $characters[rand(0, strlen($characters) - 1)];
      }
      return $guid;
    }

  }

?>