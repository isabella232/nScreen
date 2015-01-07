<?php
  //Start session
  session_start();

  $api_key = "xbsdfg4uhxf6prsp8c7adrty";
  $id =  $_POST['id'];

  $url = "http://api.ted.com/v1/talks.json?api-key=$api_key&filter=id:$id&fields=media_profile_uris,photo_urls,speakers,speaker_ids,tags";

  $json = file_get_contents($url);

  echo $json;
  exit();

?>