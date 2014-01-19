<!DOCTYPE html5>
	<html>
		<head>
			<title>Color</title>
			<link rel="stylesheet" type="text/css" href="css/colors.css" />
			<script src="js/jquery-1.9.1.min.js"></script>
			<script>
				$(document).ready(function(){

					var url = 'php/handleColors.php';

					$.post(url, function(data) {
			  		$("#schemes").empty().append(data);
					});

					$(document).on('click', '.scheme', function(){
						var schemeId = $(this).attr('id');

						var url = 'php/handleColors.php';

						var requestData = {'id':schemeId};

						$.post(url,requestData, function(data) {
			  			$("#schemes").empty().append(data);
						});

					});

				});

			</script>


		</head>

		<body>
			<div id="container">

				<p>Choose your favorite color scheme...</p>

				<div id="schemes"></div>

			</div>
		</body>
	</html>