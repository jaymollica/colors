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

      $parts = explode("4",$status);

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
      $matches = $this->emailMatches($matchedEmails,$email);

      print '<p>Thank you, an email with any potential matches should arrive soon.</p>';

      exit;

    }

    public function getMatchesA($uid) {

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
       
        if(count($ids) > 0) {
          $counts = array_count_values($ids);
        
          ksort($counts);

          $i = 0;
          foreach($counts AS $key => $val) {

            $sql = $this->_db->prepare("SELECT * FROM visitors WHERE id = ?");
            $sql->execute(array($key));

            $res = $sql->fetch(PDO::FETCH_ASSOC);

            $matches[] = $res['email'];

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

    public function emailMatches($emails,$visitorEmail) {



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