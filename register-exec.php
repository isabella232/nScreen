<?php
	//Start session
	session_start();
	
	//Include database connection details
	require_once('config.php');
	
	//Array to store validation errors
	$errmsg_arr = array();
	
	//Validation error flag
	$errflag = false;
	
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
	
	//Function to sanitize values received from the form. Prevents SQL injection
	function clean($str) {
		$str = @trim($str);
		if(get_magic_quotes_gpc()) {
			$str = stripslashes($str);
		}
		return mysql_real_escape_string($str);
	}
	
	//Sanitize the POST values
	$fname = utf8_encode($_POST['fname']);
	$lname = utf8_encode($_POST['lname']);
	$login = utf8_encode($_POST['login']);
	$password = utf8_encode($_POST['password']);
	$cpassword = utf8_encode($_POST['cpassword']);
	

	if( strcmp($password, $cpassword) != 0 ) {
		//$errmsg_arr[] = 'Passwords do not match';
		$errflag = true;
	}
	
	//Check for duplicate login ID
	if($login != '') {
		$qry = "SELECT * FROM members WHERE login='$login'";
		$result = mysql_query($qry);
		if($result) {
			if(mysql_num_rows($result) > 0) {
				$errmsg_arr[] = 'Login ID already in use';
				$errflag = true;
				//header("location: register-duplicate.php");
				//exit();
			}
			@mysql_free_result($result);
		}
		else {
			die("Query failed");
		}
	}
	
	//If there are input validations, redirect back to the registration form
	if($errflag) {
		$_SESSION['ERRMSG_ARR'] = $errmsg_arr;
		session_write_close();
		header("location: registration-failed.php");
		exit();
	}

	//Create INSERT query
	$qry = "INSERT INTO members(firstname, lastname, login, passwd) VALUES('$fname','$lname','$login','".md5($_POST['password'])."')";
	$result = @mysql_query($qry);
	
	//Check whether the query was successful or not
	if($result) {
		$_SESSION['SESS_MEMBER_ID'] = $login; //set for login
		//Insert personal suggestions
		$recommendations = file_get_contents("data/recommendations.js");
		$sql = "INSERT INTO content(recommendations) VALUES ('$recommendations')"; //Insert every read line from txt to mysql database
		mysql_query($sql);
		//Insert json template for "RECENTLY VIEWED"
		$recently_viewed = file_get_contents("data/recently_viewed.js");
		$sql2 = "INSERT INTO content(recently_viewed) VALUES ('$recently_viewed')"; //Insert every read line from txt to mysql database
		mysql_query($sql2);
		//Insert json template for "WATCH LATER"
		$watch_later = file_get_contents("data/watch_later.js");
		$sql3 = "INSERT INTO content(watch_later) VALUES ('$watch_later')"; //Insert every read line from txt to mysql database
		mysql_query($sql3);
		//Insert json template for "LIKE & DISLIKE"
		$like_dislike = file_get_contents("data/like_dislike.js");
		$sql4 = "INSERT INTO content(like_dislike) VALUES ('$like_dislike')"; //Insert every read line from txt to mysql database
		mysql_query($sql4);
		//Insert json template for "SHARED BY FRIENDS"
		$shared_by_friends = file_get_contents("data/shared_by_friends.js");
		$sql5 = "INSERT INTO content(shared_by_friends) VALUES ('$shared_by_friends')"; //Insert every read line from txt to mysql database
		mysql_query($sql5);
		header("location: login-exec.php");
		exit();
	}else {
		die("Query failed");
		header("location: registration-failed.php");
	}
?>