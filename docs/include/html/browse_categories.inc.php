<?php
/****************************************************************/
/* ATutor														*/
/****************************************************************/
/* Copyright (c) 2002-2003 by Greg Gay & Joel Kronenberg        */
/* Adaptive Technology Resource Centre / University of Toronto  */
/* http://atutor.ca												*/
/*                                                              */
/* This program is free software. You can redistribute it and/or*/
/* modify it under the terms of the GNU General Public License  */
/* as published by the Free Software Foundation.		*/
/****************************************************************/
if (!defined('AT_INCLUDE_PATH')) { exit; }

$sql = "SELECT * FROM ".TABLE_PREFIX."course_cats ORDER BY cat_name ";
$result = mysql_query($sql, $db);
if (mysql_num_rows($result) == 0) {
	$infos[] = AT_INFOS_NO_CATEGORIES ;
} else {
	while($row = mysql_fetch_assoc($result)){
		$current_cats[$row['cat_id']] = $row['cat_name'];
		$parent_cats[$row['cat_id']]  = $row['cat_parent'];
		$cat_cats[$row['cat_id']]     = $row['cat_id'];
	}
}

// count the number of courses in each category
$sql = "SELECT cat_id from ".TABLE_PREFIX."courses WHERE hide=0";
$result = mysql_query($sql, $db);

	while($row = mysql_fetch_array($result)){
		$cat_count[$row['cat_id']][$i] = $i++;
	}

function print_parent_cats($parent_cat_id, &$cats, $cat_row) {
	$my_cats = $cats[$parent_cat_id];
	$new_depth = ($old_depth - $depth);
	global $cat_count;
	echo '<ul>';
	foreach ($my_cats as $cat) {
		echo '<li><a href="'.$_SERVER['PHP_SELF'].'?current_cat='.$cat['cat_id'].SEP.'show_courses='.$cat['cat_id'].'">';
		if($cat['cat_id'] != $cat_row){
			echo $cat['cat_name'];
		}else{
			echo '<strong>'.$cat['cat_name'].'</strong>';
		}
		echo '</a> <small>';
		echo '( '.count($cat_count[$cat['cat_id']]).' )</small>';

		if (is_array($cats[$cat['cat_id']]) && ($cat['cat_id'] !== 0) ) {
			print_parent_cats($cat['cat_id'], $cats,  $cat_row, $depth+1);
		}
		echo '</li>'."\n";
	}
	echo '</ul>';
}

$cat_path_len = (strlen($current_cats[$_GET['prev_cat']]));
$path_len = strlen($_GET['cat_path']);
if($cat_path_len != ''){
	$cat_path_len = ($cat_path_len + 1);
}

if ($_GET['current_cat'] == 0){
	$_GET['cat_path'] = '';
}

$cat_path = $_GET['cat_path'];


$sql = "SELECT * FROM ".TABLE_PREFIX."course_cats ORDER BY cat_name ";
$result = mysql_query($sql, $db);

print_errors($errors);

?>

<a href="<?php echo substr($_my_uri, 0, strlen($_my_uri)-1); ?>#category">
<img src="images/clr.gif" height="1" width="1" border="0" alt="<?php echo _AT('cats_go_to_category'); ?>: ALT-c" /></a>

<table cellspacing="1" cellpadding="2" border="0" class="bodyline" summary="" width="95%" align="center">
<tr>
	<th width="50%"><?php echo _AT('cats_categories'); ?></th>
<?php
	echo '<th width="50%">'._AT('courses').': ';
	if($_GET['current_cat']){
		echo $current_cats[$_GET['current_cat']];
	}else if($_POST['cat_id']){
		echo $current_cats[$_POST['cat_id']];
	}else{
		echo  _AT('cats_uncategorized');
	}

	echo '</th>';
?>
</tr>
<tr>
	<td class="row1" width="50%" valign="top"><?php

		if ($_SESSION['s_is_super_admin']) {
			echo '<a href="'.$_SERVER['PHP_SELF'].'?add=1">'. _AT('cats_add_categories').'</a><br />';
		}

if (is_array($current_cats) || $_SESSION['s_is_super_admin']){
	$sql = "SELECT * FROM ".TABLE_PREFIX."course_cats ORDER BY cat_name ";
	$result4 = mysql_query($sql, $db);
	$cats = array();
	$cats[0][] = array('cat_id' => 0, 'cat_name' => _AT('cats_uncategorized'), 'cat_parent' => 0);
	while($row4 = mysql_fetch_assoc($result4)){
		$cats[$row4['cat_parent']][] = $row4;
	}
	$cat_row = $_GET['current_cat'];
	print_parent_cats(0, $cats, $cat_row);
	echo '</td>';
	echo '<td class="row1" valign="top" width="50%"> <a name="category"></a>&nbsp;';
	if ($_SESSION['s_is_super_admin']) {
		if ($_GET['current_cat'] != 0) {
				echo ' <small>(<a href="'.$_SERVER['PHP_SELF'].'?add=1'.SEP.'current_cat='.$_GET['current_cat'].'">'._AT('cats_add_subcategory').'</a> | <a href="'.$_SERVER['PHP_SELF'].'?edit=1'.SEP.'current_cat='.$_GET['current_cat'].'">'._AT('cats_edit_categories').'</a> | <a href="'.$_SERVER['PHP_SELF'].'?delete=1'.SEP.'current_cat='.$_GET['current_cat'].'">'._AT('cats_delete_category').'</a>)</small><br /><br />';
		}
	}
	if ($_SESSION['s_is_super_admin']) {
		echo '<strong>'._AT('courses').':</strong><br />';
		if($show_courses == ''){
			$show_courses = 0;
		}
	}
	$sql= "SELECT * FROM ".TABLE_PREFIX."courses WHERE hide=0 AND cat_id='$show_courses' ORDER BY title";
	$result = mysql_query($sql, $db);
	if(mysql_num_rows($result) > 0){
		if (!$_SESSION['s_is_super_admin']) {
			if($_GET['current_cat'] == ''){
				$_GET['current_cat'] = 0;
			}
			echo ' <small>(<a href="'.$_SERVER['PHP_SELF'].'?current_cat='.$_GET['current_cat'].SEP.'this_category='.$_GET['current_cat'].SEP.'show_courses='.$_GET['current_cat'].'#browse_top">'._AT('browse_courses').'</a> )</small><br /><br />';
		}
		echo '<ul>';
		if($_SESSION['s_is_super_admin']){
			while ($row = mysql_fetch_array($result)){
				echo '<li><a href="admin/course.php?course='.$row['course_id'].SEP.'this_course='.$row['course_id'].SEP.'show_courses='.$show_courses.SEP.'current_cat='.$show_courses.'">'.$row['title'].'</a></li>';
			}
		}else{
			while ($row = mysql_fetch_array($result)){
				echo '<li><a href="'.$_SERVER['PHP_SELF'].'?course='.$row['course_id'].SEP.'this_course='.$row['course_id'].SEP.'show_courses='.$show_courses.SEP.'current_cat='.$show_courses.'#browse_top">'.$row['title'].'</a></li>';
			}
		}
		while ($row = mysql_fetch_array($result)){
			echo '<li><a href="'.$_SERVER['PHP_SELF'].'?course='.$row['course_id'].SEP.'this_course='.$row['course_id'].SEP.'show_courses='.$show_courses.SEP.'current_cat='.$show_courses.'#browse_top">'.$row['title'].'</a></li>';
		}
		echo '</ul>';
	}else{
		echo _AT('cats_no_course');
	}
	echo '</td>';
}else{
	//$infos[] = _AT('cats_no_categories');
	echo '<td class="row1">'.print_infos($infos).'</td>';
}
echo '</tr></table>';

///////////////
// Display long version course list

if($_GET['this_course'] != '' || $_GET['this_category'] != '' ){
	echo '<a name="browse_top"></a><br />';
	echo '<h3>'._AT('courses').': ';

	if($_GET['current_cat'] == 0){
	echo  _AT('cats_uncategorized');
		echo $current_cats[$_GET['current_cat']];
	}else if($_GET['current_cat'] != ''){
		echo $current_cats[$_GET['current_cat']];
	}else if($_POST['cat_id'] != ''){
		echo $current_cats[$_POST['cat_id']];
	}

	echo '</h3>';
?>
<table cellspacing="1" cellpadding="0" border="0" class="bodyline" width="95%" summary="" align="center">
	<tr>
		<th><?php echo _AT('course_name'); ?></th>
		<th><?php echo _AT('description'); ?></th>
	</tr>
	<tr>
<?php

//echo  $_GET['this_course'];
	if($_GET['this_course'] != ''){
		$sql	= "SELECT * FROM ".TABLE_PREFIX."courses WHERE  hide=0 AND course_id = '$_GET[this_course]' ORDER BY title";
	}else if($_GET['this_category'] != ''){
		$sql	= "SELECT * FROM ".TABLE_PREFIX."courses WHERE  hide=0 AND cat_id = '$_GET[this_category]' ORDER BY title";
	}else{
		$sql	= "SELECT * FROM ".TABLE_PREFIX."courses WHERE  hide=0 ORDER BY title";
	}
	//echo $sql;
	$result = mysql_query($sql,$db);
	$num = mysql_num_rows($result);
	if ($row = mysql_fetch_array($result)) {
		do {
			echo '<tr><td class="row1" width="150" valign="top"><b>';
			echo '<a href="bounce.php?course='.$row[course_id].'">'.$system_courses[$row[course_id]][title].'</a>';

			echo '</b></td><td class="row1" valign="top">';
			echo '<small>';
			echo $row[description];

				echo '<br /><br />&middot; '. _AT('access').': ';
			$pending = '';
			switch ($row['access']){
				case 'public':
					echo _AT('public');
					break;
				case 'protected':
					echo _AT('protected');
					break;
				case 'private':
					echo _AT('private');
					break;
			}
			
			echo '<br />&middot; '. _AT('category').': ';
			if($row['cat_id'] != 0){
				echo $current_cats[$row['cat_id']];

			}else{
				echo _AT('cats_uncategorized');
			}
			$sql	  = "SELECT COUNT(*) FROM ".TABLE_PREFIX."course_enrollment WHERE course_id=$row[course_id] AND approved='y'";
			$c_result = mysql_query($sql, $db);
			$c_row	  = mysql_fetch_array($c_result);

			/* minus 1 because the instructor doesn't count */
			echo '<br />&middot; '._AT('enrolled').': '.max(($c_row[0]-1), 0).'<br />';
			echo '&middot; '. _AT('created').': '.$row[created_date].'<br />';
			echo '&middot; <a href="users/contact_instructor.php?course='.$row[course_id].'">'._AT('contact_instructor_form').'</a>';

			echo '</small></td>';
			echo '</tr>';
			if ($count < $num-1) {
				echo '<tr><td height="1" class="row2" colspan="3"></td></tr>';
			}
			$count++;
		} while ($row = mysql_fetch_array($result));
	} else {
		echo '<tr><td class=row1 colspan=3><i>'._AT('cats_no_course').'</i></td></tr>';
	}
	echo '</table>';
}
?>