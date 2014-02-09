<!DOCTYPE html5>
	<html>
		<head>
			<title>Vague Spaces</title>
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

					$(document).on('click', '#submit', function(){

						var url = 'php/handleColors.php';

						var o = {'signUp': true};

						var a = $('#signUp').serializeArray();
	
						$.each(a, function() {
							if (o[this.name] !== undefined) {
								if (!o[this.name].push) {
									o[this.name] = [o[this.name]];
								}
								o[this.name].push(this.value || '');
							} else {
								o[this.name] = this.value || '';
							}
						});

						var requestData = o;

						$.post(url,requestData, function(data) {
			  			$("#schemes").empty().append(data);
						});
					});

				});

			</script>

		</head>

		<body>
			<div id="container">

				<div id="schemes"></div>

			</div>
		</body>
	</html>