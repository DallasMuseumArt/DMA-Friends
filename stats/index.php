<?php

	
	include("adodb/adodb.inc.php");
	
	$db = NewADOConnection('mysql');
	$db->Connect("friends.dma.org", "frweb", "DMAfr13nd5", "wordpress");
	//$result = $db->Execute("SELECT * FROM wp_users");
	//if ($result === false) die("failed"); 
	//echo $result->RecordCount();
	 
	// while (!$result->EOF) {
	// 		for ($i=0, $max=$result->FieldCount(); $i < $max; $i++)
	// 			print $result->fields[$i].' ';
	// 			$result->MoveNext();
	// 			print "<br>\n";
	// 	}

	// Total Users
	
	$totalusers = $db->Execute('select COUNT(user_login) FROM wp_users');
	if ($totalusers === false) die("failed");
	echo $totalusers.'<br />';
	
	$wpdb = "wp_";
	$q = "SELECT u.ID, u.user_email, u.display_name
	FROM wp_users u
	INNER JOIN wp_usermeta m ON m.user_id = u.ID
	WHERE m.meta_key = 'wp_capabilities'
	AND m.meta_value LIKE '%subscriber%'
	AND u.user_email NOT LIKE '%dma.org%'
	AND u.user_email NOT LIKE '%dm-art.org%'
	AND u.user_email NOT LIKE '%dallasmuseumofart.org%'
	AND u.display_name LIKE 'FR%'
	ORDER BY u.user_registered";
	
	$meta = $db->Execute($q);
	
	echo $meta->RecordCount().'<br />';
		
	// Check In Total Count
	
	// Activity Counts
	
	// Badge Counts
	
	// Rewards Counts

?>