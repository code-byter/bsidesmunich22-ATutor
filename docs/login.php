<?php
/****************************************************************/
/* ATutor														*/
/****************************************************************/
/* Copyright (c) 2002-2005 by Greg Gay & Joel Kronenberg        */
/* Adaptive Technology Resource Centre / University of Toronto  */
/* http://atutor.ca												*/
/*                                                              */
/* This program is free software. You can redistribute it and/or*/
/* modify it under the terms of the GNU General Public License  */
/* as published by the Free Software Foundation.				*/
/****************************************************************/
// $Id$

$page	 = 'login';
$_user_location	= 'public';
define('AT_INCLUDE_PATH', 'include/');
require (AT_INCLUDE_PATH.'vitals.inc.php');


if (isset($_POST['cancel'])) {
	header('Location: about.php');
	exit;
}

// check if we have a cookie
if (!$msg->containsFeedbacks()) {
	if (isset($_COOKIE['ATLogin'])) {
		$cookie_login = $_COOKIE['ATLogin'];
	}
	if (isset($_COOKIE['ATPass'])) {
		$cookie_pass  = $_COOKIE['ATPass'];
	}
}

if (isset($cookie_login, $cookie_pass) && !isset($_POST['submit'])) {
	/* auto login */
	$this_login		= $cookie_login;
	$this_password	= $cookie_pass;
	$auto_login		= 1;
	$used_cookie	= true;
	$_SESSION['session_test'] = true;
} else if (isset($_POST['submit'])) {
	/* form post login */
	$this_login		= $_POST['form_login'];
	$this_password  = $_POST['form_password'];
	$auto_login		= intval($_POST['auto']);
	$used_cookie	= false;
}

if (isset($this_login, $this_password) && !isset($_SESSION['session_test'])) {
	$msg->addError('SESSION_COOKIES');
} else if (isset($this_login, $this_password)) {
	unset($_SESSION['session_test']);

	if ($_GET['course'] != '') {
		$_POST['form_course_id'] = intval($_GET['course']);
	} else {
		$_POST['form_course_id'] = intval($_POST['form_course_id']);
	}
	$this_login    = $addslashes($this_login);
	$this_password = $addslashes($this_password);

	if ($used_cookie) {
		// check if that cookie is valid
		$sql = "SELECT member_id, login, preferences, PASSWORD(password) AS pass, language, status FROM ".TABLE_PREFIX."members WHERE login='$this_login' AND PASSWORD(password)='$this_password'";

	} else {
		$sql = "SELECT member_id, login, preferences, PASSWORD(password) AS pass, language, status FROM ".TABLE_PREFIX."members WHERE login='$this_login' AND PASSWORD(password)=PASSWORD('$this_password')";
	}

	$result = mysql_query($sql, $db);
	if (($row = mysql_fetch_assoc($result)) && ($row['status'] == AT_STATUS_UNCONFIRMED)) {
		$msg->addError('NOT_CONFIRMED');
	} else if ($row && $row['status'] == AT_STATUS_DISABLED) {
		$msg->addError('ACCOUNT_DISABLED');
	} else if ($row) {
		$_SESSION['login']		= $row['login'];
		$_SESSION['valid_user'] = true;
		$_SESSION['member_id']	= intval($row['member_id']);
		assign_session_prefs(unserialize(stripslashes($row['preferences'])));
		$_SESSION['is_guest']	= 0;
		$_SESSION['lang']		= $row['language'];
		$_SESSION['course_id']  = 0;

		if ($auto_login == 1) {
			$parts = parse_url($_base_href);
			// update the cookie.. increment to another 2 days
			$cookie_expire = time()+172800;
			setcookie('ATLogin', $this_login, $cookie_expire, $parts['path'], $parts['host'], 0);
			setcookie('ATPass',  $row['pass'],  $cookie_expire, $parts['path'], $parts['host'], 0);
		}

		$msg->addFeedback('LOGIN_SUCCESS');
		header('Location: bounce.php?course='.$_POST['form_course_id']);
		exit;
	} else {
		// check if it's an admin login.
		$sql = "SELECT login, `privileges`, language FROM ".TABLE_PREFIX."admins WHERE login='$this_login' AND PASSWORD(password)=PASSWORD('$this_password') AND `privileges`>0";
		$result = mysql_query($sql, $db);

		if ($row = mysql_fetch_assoc($result)) {
			$sql = "UPDATE ".TABLE_PREFIX."admins SET last_login=NOW() WHERE login='$this_login'";
			mysql_query($sql, $db);

			$_SESSION['login']		= $row['login'];
			$_SESSION['valid_user'] = true;
			$_SESSION['course_id']  = -1;
			$_SESSION['privileges'] = intval($row['privileges']);
			$_SESSION['lang'] = $row['language'];

			write_to_log(AT_ADMIN_LOG_UPDATE, 'admins', mysql_affected_rows($db), $sql);

			$msg->addFeedback('LOGIN_SUCCESS');

			header('Location: admin/index.php');
			exit;

		} else {
			$msg->addError('INVALID_LOGIN');
		}
	}
}

if (isset($_SESSION['member_id'])) {
	$sql = "DELETE FROM ".TABLE_PREFIX."users_online WHERE member_id=$_SESSION[member_id]";
	$result = @mysql_query($sql, $db);
}

unset($_SESSION['login']);
unset($_SESSION['valid_user']);
unset($_SESSION['member_id']);
unset($_SESSION['is_admin']);
unset($_SESSION['course_id']);

$_SESSION['session_test'] = true;

/*****************************/
/* template starts down here */

$onload = 'document.form.form_login.focus();';

$savant->assign('course_id', $_GET['course']);

if (isset($_GET['course'])) {
	$savant->assign('title',  ' '._AT('to1').' '.$system_courses[$_GET['course']]['title']);
} else {
	$savant->assign('title',  ' ');
}

header('P3P: CP="IDC DSP COR CURa ADMa OUR IND PHY ONL COM STA"');
$savant->display('login.tmpl.php');
?>