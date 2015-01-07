<?php
  //Start session
  session_start();
  $min_id = 1;
  $max_id = 1500;

  //$starting_id = "10";
  $starting_id = rand($min_id,$max_id);
  $starting_id = (string)$starting_id; //stringifying (something like that)
  $tag = $_POST['tag'];

  $api_key = "xbsdfg4uhxf6prsp8c7adrty";

  $number_talks = "10";

  $url = "http://api.ted.com/v1/talks.json?api-key=$api_key&filter=id:>$starting_id&limit=$number_talks&offset=0&fields=media_profile_uris,photo_urls,speakers,speaker_ids,tags&tags=$tag";

  $json = file_get_contents($url);

  echo $json;
  exit();

?>