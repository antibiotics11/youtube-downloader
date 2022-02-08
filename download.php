<?php

	/** index.php의 Ajax 요청을 처리하는 파일 */
	
	if (!isset($_GET["do"]) || empty($_GET["do"])) {
		echo json_encode(array("error" => "Unknown Request"));
	}
	
	$video_url = $_POST["url"];
	$video_format = $_POST["format"];
	
	include "youtube.php";
	$youtube = new YouTube($video_url);
	
	if ($_GET["do"] == "getinfo") {                                      // 동영상 정보만 요청받은 경우
		$youtube->get_video_info();
		$tmp_video_info = $youtube->target_video_info();
		$video_info = array(
			"title" => $tmp_video_info["title"], 
			"thumbnail" => $tmp_video_info["thumbnail"]
		);
		if (isset($tmp_video_info["formats"][0]->url)) {
			$video_info["url"] = $tmp_video_info["formats"][0]->url;
		}
		echo json_encode($video_info);
		
	} else if ($_GET["do"] == "download") {                              // 다운로드 요청받은 경우
		$youtube->get_video_info();
		if ($download = $youtube->save_video()) {
			if (strtolower(trim((string)$video_format)) == "mp3") {
				$youtube->convert_mp3();
			}
			$path = pathinfo($youtube->target_video_path());
			echo json_encode(array("file" => $path["basename"]));
		} else {
			echo json_encode(array("error" => "Download Failed"));
			exit(0);
		}
	}
