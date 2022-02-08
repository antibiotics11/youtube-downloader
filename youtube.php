<?php
	
	/** 다운로드한 동영상이 저장될 임시 디렉터리 */
	define("__DOWNLOADDIR", __DIR__."/tmp");
	
	class YouTube {
		
		/** 다운로드할 동영상 URL */
		private $video_url;
		
		/** 다운로드할 동영상 id 값 */
		private $video_id;
		
		/** 다운로드한 동영상 경로 */
		private $video_file_path;
		
		/** 다운로드할 동영상 정보가 저장될 배열 */
		private $video_info = array();
		
		/** 동영상 id 값이 제외된 YouTube의 기본적인 동영상 URL */
		private $youtube_video_url = "https://www.youtube.com/watch?v=";
		
		/** YouTube API URL */
		private $youtube_api_url = "https://www.youtube.com/youtubei/v1/player?key=";
		
		/** YouTube API Key */
		private $youtube_api_key = "AIzaSyAO_FJ2SlqU8Q4STEHLGCilw_Y9_11qcW8";
		
		
		/** 동영상 URL에서 id 값을 추출. */
		private function get_video_id(string $video_url): string {
			preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $video_url, $match);
			return $match[1];
		}
		
		
		/** YouTube API에 전송할 postfield */
		private function set_api_postfield(): string {
			return "
			{  
				\"context\": {
					\"client\": {
						\"hl\": \"en\",      
						\"clientName\": \"WEB\",      
						\"clientVersion\": \"2.20210721.00.00\",
						\"clientFormFactor\": \"UNKNOWN_FORM_FACTOR\",
						\"clientScreen\": \"WATCH\",      
						\"mainAppWebInfo\": {
							\"graftUrl\": \"/watch?v=".$this->video_id."\",           
						}    
					}, 
					\"user\": {
						\"lockedSafetyMode\": false    
					},    
					\"request\": {
						\"useSsl\": true,      
						\"internalExperimentFlags\": [],      
						\"consistencyTokenJars\": []    
					}  
				},  
				\"videoId\": \"".$this->video_id."\",  
				\"playbackContext\": {    
					\"contentPlaybackContext\": {
						\"vis\": 0,      
						\"splay\": false,      
						\"autoCaptionsDefaultOn\": false,      
						\"autonavState\": \"STATE_NONE\",      
						\"html5Preference\": \"HTML5_PREF_WANTS\",
						\"lactMilliseconds\": \"-1\"    
					}  
				},  
				\"racyCheckOk\": false,  
				\"contentCheckOk\": false
			}
			";
		}
		
		
		private function decode_cipher_signature(string $signature): string {
			
		}
		
		
		/** YouTube 클래스 생성자. 클래스 호출 시 반드시 동영상 URL이 입력되어야 함. */
		public function __construct(string $video_url) {
			$this->video_url = $video_url;
			$this->video_id = $this->get_video_id($video_url);
		}
		
		
		public function target_video_id(): string {
			return $this->video_id;
		}
		
		
		public function target_video_info(): array {
			return $this->video_info;
		}
		
		
		public function target_video_path(): string {
			return $this->video_file_path;
		}

		
		/** YouTube API에서 동영상 정보를 json 형태로 받아온다. */
		public function get_video_info(): void {
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $this->youtube_api_url.$this->youtube_api_key);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->set_api_postfield());
			curl_setopt($ch, CURLOPT_ENCODING, "gzip, deflate");
			$http_header = array("Content-Type: application/json",);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $http_header);
			$tmp_video_info = curl_exec($ch);
			
			$tmp_video_info = json_decode($tmp_video_info);
			$this->video_info["formats"] = $tmp_video_info->streamingData->formats;
			$this->video_info["title"] = $tmp_video_info->videoDetails->title;
			$this->video_info["description"] = $tmp_video_info->videoDetails->shortDescription;
			$this->video_info["thumbnail"] = $tmp_video_info->videoDetails->thumbnail->thumbnails[3]->url;
			
			curl_close($ch);
		}
		
		
		/** 원본 동영상 파일을 다운로드해서 임시 디렉터리에 저장. */
		public function save_video(): bool {
			$video_origin_url = "";
			
			if (isset($this->video_info["formats"][0]->url)) {                       // 원본 URL 공개되어있는 경우 
				$video_origin_url = $this->video_info["formats"][0]->url;
			} else {                                                                 // 원본 URL 암호화되어있는 경우
				return false;
				
				//$signature = "https://www.youtube.com/?".$this->video_info["formats"][0]->signatureCipher;
				//parse_str(parse_url($signature, PHP_URL_QUERY), $parse_signature);
				//$signature_decoded = decode_cipher_signature($parse_signature["s"]);
				//$video_origin_url = $parse_signature["url"]."&sig=".$signature_decoded;
			}
			
			if (empty($video_origin_url)) return false;
			
			$this->video_file_path = __DOWNLOADDIR.DIRECTORY_SEPARATOR.$this->video_info["title"].".mp4";
			$file_contents = file_get_contents($video_origin_url);
			
			$download = file_put_contents($this->video_file_path, $file_contents);
			return $download ? true : false;
		}
		
		
		/** mp4 파일을 mp3 오디오 파일로 변환. PHP-FFMpeg 라이브러리 사용. */
		public function convert_mp3(): bool {
			include __DIR__.DIRECTORY_SEPARATOR."vendor/autoload.php";
			
			$ffmpeg = FFMpeg\FFMpeg::create();
			$mp3_format = new FFMpeg\Format\Audio\Mp3();
			
			$mp4_origin_video = $this->video_file_path;
			$mp4_origin_info = pathinfo($mp4_origin_video);
			$mp3_file_path = $mp4_origin_info["dirname"].DIRECTORY_SEPARATOR.$mp4_origin_info["filename"].".mp3";
			$this->video_file_path = $mp3_file_path;
			
			$create_mp3 = $ffmpeg->open($mp4_origin_video);
			$create_mp3->save($mp3_format, $mp3_file_path);
			
			return file_exists($mp3_file_path) ? true : false;
		}
		
		
		/** 파일을 브라우저에서 다운로드할 수 있도록 헤더값 설정하고 다운로드 실행. */
		public function download(): void {
			header("Content-Type:application/octet-stream");
			header("Content-Disposition:attachment;filename=".iconv('UTF-8', 'CP949', $this->video_file_path)."");
			header("Content-Transfer-Encoding:binary");
			header("Content-Length:".filesize($this->video_file_path));
			header("Cache-Control:cache,must-revalidate");
			header("Pragma:no-cache");
			header("Expires:0");
			
			ob_clean();
			flush();
			readfile($this->video_file_path);
		}
		
	};
