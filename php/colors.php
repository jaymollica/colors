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
      $ret = array();

      //get the possible statuses (m4w...)
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

      $ret['statuses'] = $status;

      //get the metro areas
      $sql = $this->_db->prepare("SELECT * FROM metro_areas ORDER BY metro_area ASC");
      $sql->execute();
      if($sql->rowCount() > 0) {
        $metros = $sql->fetchAll(PDO::FETCH_ASSOC);

        $i = 0;

        foreach($metros AS $m) {

          $metro[$i] = $m;

          $i++;

        } 
      }

      $ret['metros'] = $metro;

      return $ret;

    }

    public function signUp($status,$email,$metro) {
      //process signup form

      //validate email

      $email = $this->sanitizeMe($email,'email');
      if (!preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/",$email)) {
        print '<p>An invalid email address was entered.</p>'; exit;        
      }

      //explode status so that m4m splits into m,m
      $parts = explode("4",$status);
      $gender = $parts[0];
      $preference = $parts[1];

      //check to see if the email was already entered, if so update, if not create new entry
      $sql = $this->_db->prepare("SELECT * FROM visitors WHERE email = ?");
      $sql->execute(array($email));
      if($sql->rowCount() > 0) {
        $res = $sql->fetch(PDO::FETCH_ASSOC);
        $uid = $res['id'];
        $_SESSION['guid'] = $res['guid'];
        $sql = $this->_db->prepare("UPDATE visitors SET session_id = ?, num_entries = num_entries + 1, gender = ?, looking_for = ?, metro_id = ? WHERE email = ?");
        $sql->execute(array($_SESSION['visit_id'],$gender,$preference,$metro,$email));
      }
      else {
        while(TRUE) {
          $guid = $this->randGuid();

          $_SESSION['guid'] = $guid;

          $sql = $this->_db->prepare("SELECT * FROM visitors WHERE guid = ?");
          $sql->execute(array($guid));
          if($sql->rowCount() == 0) {
            $sql = $this->_db->prepare("INSERT INTO visitors (session_id,email,num_entries,gender,looking_for,guid,metro_id) VALUES (?,?,?,?,?,?,?)");
            $sql->execute(array($_SESSION['visit_id'],$email,1,$parts[0],$parts[1],$guid,$metro));
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

      $userInfo = array(
        'uid' => $uid,
        'metro_id' => $metro,
        'gender' => $gender,
        'preference' => $preference
      );

      //return an array of matched email addresses
      $matches = $this->getMatchesA($userInfo);

      if(!empty($matches)) {
        $mail = $this->emailMatches($matches,$email);

        if($mail) {
          return '<p>Thank you, an email with any potential matches should arrive soon.</p>';
        }
        else {
           return '<p>A problem has occured, please try again later.</p>';
        }
      }
      else {
        return '<p>We didn&rsquo;t find any matches for you!  Please try again later.</p>';
      }

      exit;

    }

    public function getMatchesA($userInfo) {

      extract($userInfo);

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

          //we need two sets of matches, one for the universal matches regardless of the visitors location or preference, another that considers location and preference
          
          //first the universal matches
          $i = 0;
          foreach($counts AS $key => $val) {

            $sql = $this->_db->prepare("SELECT * FROM visitors WHERE id = ?");
            $sql->execute(array($key));

            $res = $sql->fetch(PDO::FETCH_ASSOC);

            $matches['universal'][] = $res;

            $i++;

            if($i >= 4) {
              break;
            }

          }

          //second set refined to consider preference and location
          $i = 0;
          foreach($counts AS $key => $val) {

            $sql = $this->_db->prepare("SELECT * FROM visitors WHERE id = ? AND gender = ? AND metro_id = ?");
            $sql->execute(array($key,$preference,$metro_id));

            $res = $sql->fetch(PDO::FETCH_ASSOC);

            $matches['refined'][] = $res;

            $i++;

            if($i >= 4) {
              break;
            }

          }

          //now we gotta check to see if the matched people prefer this visitor's gender
          $i = 0;
          foreach($matches['refined'] AS &$r) {
            if($r['looking_for'] != $gender) {
              unset($matches['refined'][$i]);
            }
            $i++;
          }

          //add caller, receiver and hash to table to let messaging happen
          $availableHash = $this->makeAvailable($matches);

          foreach($matches['universal'] AS &$m) {
            $m['availableHash'] = $availableHash;
            $m['caller_guid'] = $_SESSION['guid'];

            $sql = $this->_db->prepare("SELECT * FROM metro_areas WHERE id = ?");
            $sql->execute(array($m['metro_id']));
            $res = $sql->fetch(PDO::FETCH_ASSOC);
            $m['metro_name'] = $res['metro_area'];
          }

          foreach($matches['refined'] AS &$m) {
            $m['availableHash'] = $availableHash;
            $m['caller_guid'] = $_SESSION['guid'];

            $sql = $this->_db->prepare("SELECT * FROM metro_areas WHERE id = ?");
            $sql->execute(array($m['metro_id']));
            $res = $sql->fetch(PDO::FETCH_ASSOC);
            $m['metro_name'] = $res['metro_area'];
          }

          return $matches;

        }
        else {
          print '<p>no match</p>';
        }
        
      }

    }

    //make it possible for people to connect by adding entries to the available_messages table
    public function makeAvailable($matches) {
      $caller_guid = $_SESSION['guid'];
      $hash = $this->makeHash();

      if(!empty($matches['universal'])) {
        foreach($matches['universal'] AS $m) {
          $sql = $this->_db->prepare("INSERT INTO available_messages (caller_guid,receiver_guid,hash) VALUES (?,?,?)");
          $sql->execute(array($caller_guid,$m['guid'],$hash));
        }
      }

      if(!empty($matches['refined'])) {
        foreach($matches['refined'] AS $m) {
          $sql = $this->_db->prepare("INSERT INTO available_messages (caller_guid,receiver_guid,hash) VALUES (?,?,?)");
          $sql->execute(array($caller_guid,$m['guid'],$hash));
        }
      }

      return $hash;

    }

    public function emailMatches($matches,$visitorEmail) {

      $docroot = $_SERVER['DOCUMENT_ROOT'];
      $host = $_SERVER['HTTP_HOST'];

      $to = $visitorEmail;

      $subject = 'Your matches have arrived!';
      $from = 'info@vaguespac.es';

      $headers = "From: " . $from . "\r\n";
      $headers .= "MIME-Version: 1.0\r\n";
      $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

      $message = '<html><body>';
      $message .= '<p>Below is a list of people who best match your preferences.  The links will take you to a form where you can contact them if you wish.</p>';

      //Top matches considering location and preference
      if(!empty($matches['refined'])) {
        $message .= '<p>Here are your top matches in your area:</p>';
        $message .= '<ol>';
        foreach($matches['refined'] AS $m) {

          $message .= '<li><a href = "http://' . $host . '/php/handleColors.php?m=' . $m['guid'] . '&c=' . $m['caller_guid'] . '&h=' . $m['availableHash'] . '">' . $m['guid'] . '</a> (' . $m['gender'] . ' - ' . $m['metro_name'] . ').</li>';

        }
      }
      else {
        $message .= '<p>There were no suitable matches in your area.  Please try again later.</p>';
      }

      $message .= '</ol>';

      //list off the universal matches
      $message .= '<p>Here are your best matches regardless of your location or gender preference:</p>';
      $message .= '<ol>';
      foreach($matches['universal'] AS $m) {

        $message .= '<li><a href = "http://' . $host . '/php/handleColors.php?m=' . $m['guid'] . '&c=' . $m['caller_guid'] . '&h=' . $m['availableHash'] . '">' . $m['guid'] . '</a> (' . $m['gender'] . ' - ' . $m['metro_name'] . ').</li>';

      }

      $message .= '</ol>';



      $message .= '</body></html>';

      //print $message;

      return mail($to, $subject, $message, $headers);      

    }

    public function getMessageForm($m,$c,$h) {

      $ret = array();
      $ret['instructions'] = 'Send your match an introduction.  Tell them how you found this site and include some interesting details about yourself.';
      $ret['m'] = $m;
      $ret['c'] = $c;
      $ret['h'] = $h;

      return $ret;      

    }

    public function submitMessage($message,$receiver,$caller,$hash) {

      //validate message to see if the hash is valid and that it hasn't been used to contact this person before
      $sql = $this->_db->prepare("SELECT * FROM available_messages WHERE caller_guid = ? AND receiver_guid = ? AND hash = ?");
      $sql->execute(array($caller,$receiver,$hash));
      if($sql->rowCount() > 0) {
        //now check if the receiver is still reachable by that caller through that hash
        $res = $sql->fetch(PDO::FETCH_ASSOC);
        $status = $res['status'];
        if($status == 0) {

          //insert message into db

          $message = $this->sanitizeMe($message,'string');

          $sql = $this->_db->prepare("INSERT INTO messages (message,caller_guid,receiver_guid,hash) VALUES (?,?,?,?)");
          $sql->execute(array($message,$caller,$receiver,$hash));

          //now send email to receiver
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
              $from = 'info@vaguespac.es';

              $headers = "From: " . $from . "\r\n";
              $headers .= "MIME-Version: 1.0\r\n";
              $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
              $body = '<html><body>';

              $body .= '<p>' . $message . '</p>';

              $body .= '<p>Our work here is done!  If you&rsquo;re interested in replying to this message you can reach this person at: <a href="mailto:' . $email_c . '">' . $email_c . '</a>';

              $body .= '</body></html>';

              $mail = mail($to, $subject, $body, $headers);

              if($mail) {
                //if the email was successfully sent update the message status to sent so that it cannot be sent multiple times
                $sql = $this->_db->prepare("UPDATE messages SET sent=1 WHERE id = ?");
                $sql->execute(array($mid));

                $sql = $this->_db->prepare("UPDATE available_messages SET status=1 WHERE caller_guid = ? AND receiver_guid = ? AND hash = ?");
                $sql->execute(array($caller,$receiver,$hash));

                return '<p>Youre message was sent!  Our work here is done, now it&rsquo;s up to the person you contacted to get back to you.</p>';

              }
              else {
                return '<p>Something went wrong and your email was not delivered.</p>';
              }

            }
            else {
              return '<p>The person you want to contact is currently unavailable.</p>';
            }

          }
          else {
            return '<p>Your message could not be located.</p>';
          }  
        }
        else {
          return '<p>You have already contacted this person, please wait for a response directly from them.</p>';
        }
      }
      else {
        return '<p>This person is currently unreachable.</p>';
      }
    }

    public function sanitizeMe($var,$type) {
      if($type == 'string') {
        $var = filter_var($var, FILTER_SANITIZE_STRING);
        return $var;
      }
      elseif($type == 'email') {
        $var = filter_var($var, FILTER_SANITIZE_EMAIL);
        if(filter_var($var, FILTER_VALIDATE_EMAIL)) {
          return $var;
        }
        else {
          print '<p>You did not enter a valid email address.</p>';
          exit;
        }
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