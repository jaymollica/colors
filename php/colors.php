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

      print '<pre>'; print_r($parts); print '</pre>';

      $sql = $this->_db->prepare("SELECT * FROM visitors WHERE email = ?");
      $sql->execute(array($email));
        if($sql->rowCount() > 0) {
          $statuses = $sql->fetchAll(PDO::FETCH_ASSOC);

        }
        else {
          $sql = $this->_db->prepare("INSERT INTO visitors (session_id,email,num_entries,gender,looking_for) VALUES (?,?,?,?,?)");
          $sql->execute(array($_SESSION['visit_id'],$email,1,$parts[0],$parts[1]));
        }


      exit;

    }

  }

?>