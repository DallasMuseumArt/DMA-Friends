<?php

require_once('dmaprint.class.php');

if ($_POST && isset($_POST['printtype']))
{
	switch($_POST['printtype'])
	{
		case "idcard":
			$idcard = new dmaprintid($_POST['name'], $_POST['number'], $_POST['printer']);
			$idcard->doPrint();
			break;
		case "coupon":
			$coupon = new dmaprintcoupon($_POST['text'], $_POST['expires'], $_POST['number'], $_POST['printer']);
			$coupon->doPrint();
			break;
	}
}

?>
<html>
	<head>
		<title>Printing POC</title>
	</head>
	<body>
		<h2>ID Card</h2>

		<form method="post">
			<input type="hidden" name="printtype" value="idcard" />
		
			<label for="name">User Name</label>
			<input type="text" name="name"></input><br/>

			<label for="number">User ID Number</label>
			<input type="text" name="number"></input><br/>

			<label for="printer">Printer</label>
			<select name="printer">
				<option></option>
				<option value="192.168.91.89">192.168.91.89</option>
			</select><br/>
			
			<button type="submit">Print ID Card</button>
		</form>


		<h2>Coupon</h2>

		<form method="post">
			<input type="hidden" name="printtype" value="coupon" />

			<label for="text">Coupon Text</label>
			<textarea name="text"></textarea><br/>

			<label for="expires">Expiration Date</label>
			<input type="text" name="expires"></input><br/>

			<label for="code">Coupon Code</label>
			<input type="text" name="number"></input><br/>
			
			<label for="printer">Printer</label>
			<select name="printer">
				<option></option>
				<option value="192.168.91.89">192.168.91.89</option>
			</select><br/>
			
			<button type="submit">Print Coupon</button>
		</form>

		</body>
</html>
