<?php
  //Start session
  session_start();
  $group = $_POST['group'];
  $_SESSION['SESS_GROUP'] = $group;
  echo $group;

?>