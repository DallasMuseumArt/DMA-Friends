<?php


	// include("adodb/adodb.inc.php");
	// 
	// 	$db = NewADOConnection('mysql');
	// 	$db->Connect("friends.dma.org", "frweb", "DMAfr13nd5", "wordpress");
	// 
	// 
	// 
	// 	$data = "SELECT wp_users.ID, wp_users.user_email, GROUP_CONCAT(wp_usermeta.meta_value ORDER BY wp_usermeta.meta_key SEPARATOR ' ') AS name
	// 	FROM wp_users
	// 	INNER JOIN wp_usermeta
	// 	ON wp_users.ID = wp_usermeta.user_id
	// 	WHERE (wp_usermeta.meta_key = 'first_name' OR wp_usermeta.meta_key = 'last_name' OR wp_usermeta.meta_key = '_dma_points')
	// 	AND wp_users.user_email NOT LIKE '%dma.org%'
	// 	AND wp_users.user_email NOT LIKE '%dm-art.org%'
	// 	AND wp_users.user_email NOT LIKE '%dallasmuseumofart.org%'
	// 	GROUP BY wp_users.ID
	// 	";
	// 
	// 	$recordSet = $db->Execute($data);
	// 	
	// 	$i = 0;
	// 	
	// 	while (!$recordSet->EOF) {
	// 		$parts = explode(' ', $recordSet->fields[2]);
	// 		$end = count($parts);
	// 		$end -=1;
	// 		$name="";
	// 		if (is_numeric($parts[$end])) {
	// 			$points = $parts[$end];
	// 			$nameStop = $end-1;
	// 		} else {
	// 			$points = 0;
	// 			$nameStop = $end;
	// 		}
	// 		for ($c=0; $c<=$nameStop; $c++) {
	// 			$name = $name.$parts[$c].' ';
	// 		}
	// 		$record[$i]['name'] = $name;
	// 		$record[$i]['points'] = $points;
	// 		$record[$i]['email'] = $recordSet->fields['1'];
	// 		$i++;
	// 		$recordSet->MoveNext();
	// 	}
?>
<!DOCTYPE html>
<html dir="ltr" lang="en-US">
<head>
	<meta charset="UTF-8" />
	<title>DMA Friends Stats</title>
	<link rel="profile" href="http://gmpg.org/xfn/11" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<!-- 1140px Grid styles for IE -->
	<!--[if lte IE 9]><link rel="stylesheet" href="css/ie.css" type="text/css" media="screen" /><![endif]-->

	<!-- The 1140px Grid - http://cssgrid.net/ -->
	<link rel="stylesheet" href="css/1140.css" type="text/css" media="screen" />
	
	<!-- Your styles -->
	<link rel="stylesheet" href="css/styles.css" type="text/css" media="screen" />
	
	<!--css3-mediaqueries-js - http://code.google.com/p/css3-mediaqueries-js/ - Enables media queries in some unsupported browsers-->
	<script type="text/javascript" src="js/css3-mediaqueries.js"></script>
	
	<!--Delete embedded styles, just for example.-->
	<style type="text/css">
	
	body {
	font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
	}
	
	.container p {
	color: #fff;
	line-height: 100px;
	background: #000;
	text-align: center;
	margin: 20px 0 0 0;
	}
	
	</style>
</head>

<body>

	<div class="container">
		<div class="row">
			<div class="twelvecol last">
				<h1 class="header">DMA Friends and Points</h1>
				<table>
					<?php

					foreach ($record as $info) {
						echo "<tr>";
						echo "<td>".$info['name']."</td>";
						echo "<td>".$info['email']."</td>";
						echo "<td>".$info['points']."</td>";
						echo "</tr>";
					}

					?>
				</table>
			</div>
		</div>
	</div>
	


</body>
</html>