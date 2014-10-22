<?php
  //Start session
  session_start();
  $group = $_SESSION['SESS_GROUP'];
  echo $group;
  exit();

?>