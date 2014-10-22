<?php
  //Start session
  session_start();

  if(isset($_SESSION['SESS_FIRST_NAME'])) {
      $session_name = utf8_decode($_SESSION['SESS_FIRST_NAME']);
      echo $session_name;
      exit();
  }

?>