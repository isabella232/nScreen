<?php
	//Start session
	session_start();

	//Include database connection details
	require_once('config.php');
	

	//RETRIEVE VALUE FACEBOOK ID
	$facebook_id = $_SESSION['FACEBOOKID'];
	
	//Connect to mysql server
	$link = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
	if(!$link) {
		die('Failed to connect to server: ' . mysql_error());
	}
	
	//Select database
	$db = mysql_select_db(DB_DATABASE);
	if(!$db) {
		die("Unable to select database");
	}
	
	// ### LOGIN ###
	$qry="SELECT * FROM members WHERE facebook_id='$facebook_id'";
	$result=mysql_query($qry);
	//Check whether the query was successful or not
	if($result) {
		if(mysql_num_rows($result) == 1) {
			//Login Successful
			session_regenerate_id();
			$member = mysql_fetch_assoc($result);
			$_SESSION['SESS_MEMBER_ID'] = $member['member_id'];
			$_SESSION['SESS_FIRST_NAME'] = mysql_real_escape_string($member['firstname']);
			$_SESSION['SESS_LAST_NAME'] = mysql_real_escape_string($member['lastname']);
			session_write_close();
			//header("location: member-index.php");
			//exit();
		}else {
			//Login failed
			header("location: login-failed.php");
			exit();
		}
	}else {
		die(mysql_error());
	}
?>