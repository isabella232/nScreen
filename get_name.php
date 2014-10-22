<?php
  //Start session
  session_start();

  if(isset($_SESSION['SESS_FIRST_NAME'])) {
      $session_name = mysql_real_escape_string($_SESSION['SESS_FIRST_NAME']);
      echo $session_name;
      exit();
  }

?>