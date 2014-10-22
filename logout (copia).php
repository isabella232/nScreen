<?php
	//Start session
	session_start();
	
	if(!empty($_SESSION['SESS_FIRST_NAME'])) {
    echo $_SESSION['SESS_FIRST_NAME'];
}
?>