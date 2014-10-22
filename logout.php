<?php
	//Start session
	session_start();
	
	//Unset the variables stored in session
	unset($_SESSION['SESS_MEMBER_ID']);
	unset($_SESSION['SESS_FIRST_NAME']);
	unset($_SESSION['SESS_LAST_NAME']);
	unset($_SESSION['SESS_GROUP']);
	if(isset($_SESSION['FACEBOOKID'])) {
		unset($_SESSION['FACEBOOKID']);
	}

	//header("location: index.html");
	exit();
?>
