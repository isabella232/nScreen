<?php
	//Start session
	session_start();
	
	//Include database connection details
	require_once('config.php');
	
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
	
	if(isset($_SESSION['SESS_MEMBER_ID'])){
		$login = $_SESSION['SESS_MEMBER_ID'];
		//Create query
		$qry="SELECT * FROM members WHERE login='$login'";
		$result=mysql_query($qry);   

		//Check whether the query was successful or not
		if($result) {
			if(mysql_num_rows($result) == 1) {
				//Login Successful
				session_regenerate_id();
				$member = mysql_fetch_assoc($result);
				$_SESSION['SESS_MEMBER_ID'] = $member['member_id'];
				$_SESSION['SESS_FIRST_NAME'] = utf8_decode($member['firstname']);
				$_SESSION['SESS_LAST_NAME'] = $member['lastname'];
				session_write_close();
				header("location: member-index.php");
				exit();
			}else {
				//Login failed
				header("location: login-failed.php");
				exit();
			}
		}else {
			die("Query failed");
		}
	}

	else{
		//Sanitize the POST values
		$username = utf8_encode($_POST['login']);
		$password = utf8_encode($_POST['password']);
		
		// //Input Validations
		// if($nick1 == '') {
		// 	//$errmsg_arr[] = 'Login ID missing';
		// 	$errflag = true;
		// }
		// if($password == '') {
		// 	//$errmsg_arr[] = 'Password missing';
		// 	$errflag = true;
		// }
		
		// //If there are input validations, redirect back to the login form
		// if($errflag) {
		// 	$_SESSION['ERRMSG_ARR'] = $errmsg_arr;
		// 	session_write_close();
		// 	header("location: login-failed.php");;
		// 	exit();
		// }
		
		//Create query
		$qry="SELECT * FROM members WHERE login='$username' AND passwd='".md5($_POST['password'])."'";
		$result=mysql_query($qry);   

		//Check whether the query was successful or not
		if($result) {
			if(mysql_num_rows($result) == 1) {
				//Login Successful
				session_regenerate_id();
				$member = mysql_fetch_assoc($result);
				$_SESSION['SESS_MEMBER_ID'] = $member['member_id'];
				$_SESSION['SESS_FIRST_NAME'] = utf8_encode($member['firstname']);
				$_SESSION['SESS_LAST_NAME'] = $member['lastname'];
				session_write_close();
				header("location: member-index.php");
				exit();
			}else {
				//Login failed
				$errmsg_arr[] = 'Username or password not correct';
				$errflag = true;
				header("location: login-failed.php");
				exit();
			}
		}else {
			die("Query failed");
		}
	}
?>