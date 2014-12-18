<?php
  //Start session
  session_start();
  $min_id = 1;
  $max_id = 1587;

  //$starting_id = "10";
  $starting_id = rand($min_id,$max_id);
  $starting_id = (string)$starting_id; //stringifying (something like that)

  $api_key = "xbsdfg4uhxf6prsp8c7adrty";

  $number_talks = "10";

  $url = "http://api.ted.com/v1/talks.json?api-key=$api_key&filter=id:>$starting_id&limit=$number_talks&offset=0";
  //$ur = urlencode($url);

  //$path = parse_url($url, PHP_URL_PATH);
  //$url = substr_replace($url, '/' . urlencode(substr($path, 1)), strpos($url, $path), strlen($path));
  //$api_request_url = urlencode($api_request_url);
  //$api_request_url = html_entity_decode($api_request_url);

  $json = file_get_contents($url);

  echo $json;
  exit();

?>