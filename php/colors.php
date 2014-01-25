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
        $sql = $this->_db->prepare("UPDATE visitors SET session_id = ?, num_entries = num_entries + 1, gender = ?, looking_for = ? WHERE email = ?");
        $sql->execute(array($_SESSION['visit_id'],$parts[0],$parts[1],$email));
      }
      else {
        while(TRUE) {
         $guid = $this->randGuid();

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

        $message .= '<li><a href = "' . $m['guid'] . '">' . $m['guid'] . '</a></li>';

      }

      $message .= '</ol>';
      $message .= '</body></html>';

      print $message;

      return mail($to, $subject, $message, $headers);      

    }

    public function randGuid() {
      $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
      $randomString = '';
      for ($i = 0; $i < 32; $i++) {
          $randomString .= $characters[rand(0, strlen($characters) - 1)];
      }
      return $randomString;
    }

  }

?>