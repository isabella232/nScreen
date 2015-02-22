<?php
  //Start session
  session_start();

  $api_key = "xbsdfg4uhxf6prsp8c7adrty";
  $id =  $_POST['id'];

  $url = "http://api.ted.com/v1/speakers.json?api-key=$api_key&filter=id:$id&fields=photo_url,talks";

  $json = file_get_contents($url);

  echo $json;
  exit();

?>