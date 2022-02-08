<!DOCTYPE html>
<html lang = "ko">
	<head>
		<meta http-equiv = "content-type" content = "text/html" charset = "utf-8">
		<meta name = "viewport" content = "width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, viewport-fit=cover">
		<title> ytdown </title>
		<meta name = "description" content = "유튜브 동영상/오디오 다운로드">
		<meta property = "og:title" content = "ytdown">
		<meta property = "og:description" content = "유튜브 동영상/오디오 다운로드">
		<link rel = "stylesheet" href = "assets/style.css" type = "text/css">
		<script src="assets/jquery.min.js"></script>
	</head>
	
	<body>
		
		<h1> 유튜브 동영상/오디오 다운로드 </h1>
		
		<div id = "url_input_form">
			<input id = "url_input" type = "text" required>
			<br>
			<input id = "format" name = "select_format" type = "radio" value = "mp4" required> mp4
			<input id = "format" name = "select_format" type = "radio" value = "mp3" required> mp3
			<br><br>
			<input id = "url_send" type = "submit" value = "다운로드">
			<span id = "init_getinfo"></span>
		</div>
		
		<div id = "video_info" style = "display: none;">
			<iframe id = "download_url" style = "display:none;"></iframe>
			<img id = "thumbnail">
			<span id = "video_title"></span>
			<br>
			<span id = "init_download"></span>
		</div>
		
	
		<script type = "text/javascript">
		
		function download_url(file_path, file_name) {
			
			var download = document.createElement("a");
			download.href = file_path;
			//download.target = "_blank";
			download.download = file_name;
			document.body.appendChild(download);
			download.click();
			window.URL.revokeObjectURL(download);
			
			return;
		}
		
		function get_video_path(file_name) {
			
			var xhttp = new XMLHttpRequest();
			
			var set_url = $("#url_input").val();
			var set_format = $("input[name='select_format']:checked").val();
			var params = "url=" + set_url + "&format=" + set_format;
			
			xhttp.open("POST", "download.php?do=download", true);
 			xhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
 			xhttp.onreadystatechange = function() {
 				if (xhttp.readyState == 4 && xhttp.status == 200) {
					console.log(JSON.parse(this.responseText));
					var file_path = JSON.parse(this.responseText).file;
					console.log("Download URL: tmp/" + file_path);
					download_url("tmp/" + file_path, file_name);
				}
			}
			xhttp.send(params);
			
			return;
		}

		$(document).ready(function() {
			$("#url_send").click(function() {
				
				var xhttp = new XMLHttpRequest();
				
				var set_url = $("#url_input").val();
				var set_format = $("input[name='select_format']:checked").val();
				
				if (set_url.length === 0 || set_format.length === 0) {
					$("#init_getinfo").text("동영상 URL과 다운로드 포맷을 선택해주세요.");
					return;
				}
				
				var params = "url=" + set_url + "&format=" + set_format;
				
				$("#init_getinfo").text("다운로드 준비중...");
				
				xhttp.open("POST", "download.php?do=getinfo", true);
				xhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
				xhttp.onreadystatechange = function() {
					if (xhttp.readyState == 4 && xhttp.status == 200) {	
						
						if (typeof JSON.parse(this.responseText).error === "undefined") {
							var title = JSON.parse(this.responseText).title;
							var thumbnail = JSON.parse(this.responseText).thumbnail;
							
							$("#url_input_form").css("display", "none");
							$("#video_info").css("display", "block");
							$("#init_download").text("파일을 다운로드하는중... 잠시만 기다려 주세요.");
							$("#video_title").text(title);
							$("#thumbnail").attr("src", thumbnail);
							
							var file_name = title + "." + set_format;
							
							if (typeof JSON.parse(this.responseText).url !== "undefined" && set_format == "mp4") {
								console.log("Download URL: " + JSON.parse(this.responseText).url);
								download_url(JSON.parse(this.responseText).url, file_name);
							} else {
								get_video_path(file_name);
							}
						} else {
							console.log("ERROR: " + JSON.parse(this.responseText).error);
							$("#init_download").text("ERROR: " + JSON.parse(this.responseText).error);
						}
					}
				}
				xhttp.send(params);
			});
		});
		</script>
	</body>
</html>
