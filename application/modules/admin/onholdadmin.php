<?php
session_start();
//make sure that the user can not send the session variable through as a get on the url
if(!session_is_registered("userId") || !session_is_registered("administrator"))
{
    echo("<script language='javascript'>location.href='login.php'</script>");
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<!-- Created on: 14/07/2009 -->
<head>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1252" />
<title>Content Management System</title>
<link href="style.css" rel="stylesheet" type="text/css" /> 
</head>
<body>
<?php include('includes/header.php'); ?>
	<div id="main">
		<div class="content">

<div id="headtitle"><p class="blocktitle">WEBSITE ADMINISTRATION </p></div>
<p>Welcome to the new CMS (Content Management System) by <a href='http://atlanticcanadaschoice.com'>Dave Dinsmore Web Design</a>.</p>
<p><b><u>Brief overview and instructions:</u></b></p>
<p><b>Manage Menu System:</b> Here you administer the menu system. You can add a link in the main menu or add the page link to the footer (bottom) of the website and also select the navigation order of your links in the main menu.</p>
<p>You can add any <b><i>specialty pages</i></b> to your website by selecting to add those links to the header and footer of the website.</p>
 		

<?php include('includes/footer.php'); ?>
</body>
</html>