<?php
  //Start session
  session_start();
  $watch_later = mysql_escape_string($_POST['data']);
  $channel = $_POST['channel'];
	ini_set( 'default_charset', 'UTF-8' );
	
	//Include database connection details
	require_once('config.php');

	//Connect to mysql server
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	if(!$link) {
		die('Failed to connect to server: ' . mysql_error());
	}
	
	//Selecting database for the user
	$db = mysql_select_db(DB_DATABASE);
	if(!$db) {
		die("Unable to select database");
	}
	$member_id = $_SESSION['SESS_MEMBER_ID'];

	mysql_query("UPDATE content SET $channel = '$watch_later' WHERE member_id = '$member_id'");

?>