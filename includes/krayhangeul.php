<?
// 제목 : 크레이한글 클래스 ( UTF-8 )
// 설명 : 한글 자소 분리, 한글 조합을 자유롭게 수행합니다.
// 제작자 : 크레이 ( 이용운 )
// 첫오픈 : 2020. 10. 10
// 블로그 : 크레이의 IT 탐구 / https://itadventure.tistory.com
// 주석만 삭제하지 않으면 자유롭게 사용하셔도 좋습니다.
class CrayHangulClass {
	private $cho_start=0xac00; // 초성이 시작되는 코드
	private $cho_length=588; //  초성간 간격
	private $jung_length=28; //  중성간 간격

	// 초성 19글자
	private $cho_char=array(
		"ㄱ", "ㄲ", "ㄴ", "ㄷ", "ㄸ",
		"ㄹ", "ㅁ", "ㅂ", "ㅃ", "ㅅ",
		"ㅆ", "ㅇ", "ㅈ", "ㅉ", "ㅊ",
		"ㅋ", "ㅌ", "ㅍ", "ㅎ");

	// 중성 21글자
	private $jung_char=array(
		"ㅏ", "ㅐ", "ㅑ", "ㅒ", "ㅓ",
		"ㅔ", "ㅕ", "ㅖ", "ㅗ", "ㅘ",
		"ㅙ", "ㅚ", "ㅛ", "ㅜ", "ㅝ",
		"ㅞ", "ㅟ", "ㅠ", "ㅡ", "ㅢ",
		"ㅣ"
	);

	// 중성 27글자 + 공백1개 ( 받침이 없는 경우 )
	private $jong_char=array(
		"", "ㄱ", "ㄲ", "ㄳ", "ㄴ",
		"ㄵ", "ㄶ", "ㄷ", "ㄹ", "ㄺ",
		"ㄻ", "ㄼ", "ㄽ", "ㄾ", "ㄿ",
		"ㅀ", "ㅁ", "ㅂ", "ㅄ", "ㅅ",
		"ㅆ", "ㅇ", "ㅈ", "ㅊ", "ㅋ",
		"ㅌ", "ㅍ", "ㅎ"
	);

	// 중성 - 겹자음 자소글자를 두 글자로 나눈 것
	private $jong_char2=array(
		"", "ㄱ", "ㄲ", "ㄱㅅ", "ㄴ",
		"ㄴㅈ", "ㄴㅎ", "ㄷ", "ㄹ", "ㄹㄱ",
		"ㄹㅁ", "ㄹㅂ", "ㄹㅅ", "ㄹㅌ", "ㄹㅍ",
		"ㄹㅎ", "ㅁ", "ㅂ", "ㅂㅅ", "ㅅ",
		"ㅆ", "ㅇ", "ㅈ", "ㅊ", "ㅋ",
		"ㅌ", "ㅍ", "ㅎ"
	);

	// 한글 한글자를 3개의 초중종성 글자 배열로 분할해줍니다. ( 3바이트 UTF8 기준 )
	// 입력)
	//   $char : UTF8 한글 한글자
	//   $jong_split : 종성이 2개의 자소인 경우 분리할지 여부, ㄳ => ㄱ, ㅅ ( 기본 = false )
	// 리턴)
	//   한글자소를 배열로 리턴합니다.
	public function split_han($char, $jong_split=false)
	{
		// 1바이트 코드인 경우
		if(strlen($char)<=1)return array($char);

		// UTF8 코드표 주소 추출
		$c1=substr($char, 0, 1);
		$c2=substr($char, 1, 1);
		$c3=substr($char, 2, 1);
		$p=ord($c1) * 65536 + ord($c2) * 256 + ord($c3); 

		// 한글이 아닌 경우
		if($p<0xeab080 || $p>0xed9ea3 )return array($char);
		
		// 1110XXXX 10XXXXXX 10XXXXXX
		// UNICODE 코드 추출
		$unicode = 
			(( ord($c1) & 15 ) << 12) +
			(( ord($c2) & 0x3f ) << 6) +
			(( ord($c3) & 0x3f ));

		// 한글 인덱스
		$hanindex = $unicode - $this->cho_start;

		// 초성 추출
		$cho=floor($hanindex / $this->cho_length);
		$hanindex-=$cho * $this->cho_length;
		$jung=floor($hanindex / $this->jung_length);
		$hanindex-=$jung * $this->jung_length;
		$jong=$hanindex;

		if($jong_split==false)
			$jongarr = $this->jong_char[$jong];
		else 
			$jongarr = $this->jong_char2[$jong];

		// echo $unicode." ( $cho / $jung / $jong ) <br/>";

		if(strlen($jongarr)==6)
			$jong_array=array(
				substr($jongarr,0,3),
				substr($jongarr,3,3)
			);
		else if(strlen($jongarr)==3)
			$jong_array=array($jongarr);
		else
			$jong_array=array();

		return array_merge(
			array(
				$this->cho_char[$cho],
				$this->jung_char[$jung]
			),
			$jong_array
		);
	}

	// 한글 조합, 한글 자소 배열을 입력, 합체된 한글 한글자를 얻습니다.
	// 입력)
	//   $chars : 한글 자소 배열. 예) array("ㄱ", "ㅏ", "ㄹ");
	// 리턴)
	//   합쳐진 한글 한글자를 리턴합니다.
	public function join_han($chars)
	{
		if(count($chars)<=1)return implode("", $chars);
		// 초성이 없으면 그냥 원본 리턴
		$cho=array_search($chars[0], $this->cho_char);
		if($cho===false)return implode("", $chars);

		// 중성이 없으면 그냥 원본 리턴
		$jung=array_search($chars[1], $this->jung_char);
		$jung2=array_search($chars[2], $this->jung_char);

		$jong_start=2;

		// 겹모음 예외 처리
		if($chars[1]=="ㅗ" && $chars[2]=="ㅏ"){ 
			$jung=array_search("ㅘ", $this->jung_char); 
			$jong_start++; 
		}
		else if($chars[1]=="ㅗ" && $chars[2]=="ㅐ"){ 
			$jung=array_search("ㅙ", $this->jung_char); 
			$jong_start++; 
		}
		else if($chars[1]=="ㅗ" && $chars[2]=="ㅣ"){ 
			$jung=array_search("ㅚ", $this->jung_char); 
			$jong_start++; 
		}
		else if($chars[1]=="ㅜ" && $chars[2]=="ㅓ"){ 
			$jung=array_search("ㅝ", $this->jung_char); 
			$jong_start++; 
		}
		else if($chars[1]=="ㅜ" && $chars[2]=="ㅔ"){ 
			$jung=array_search("ㅞ", $this->jung_char); 
			$jong_start++; 
		}
		else if($chars[1]=="ㅡ" && $chars[2]=="ㅣ"){ 
			$jung=array_search("ㅢ", $this->jung_char); 
			$jong_start++; 
		}


		if($jung===false)return implode("", $chars);
		// 종성은 합쳐서 조사
		$jongstr="";		
		for($i=$jong_start;$i<count($chars);++$i)
			$jongstr.=$chars[$i];
		$jong=array_search($jongstr, $this->jong_char);
		// 종성글자가 나눠졌을 수 있으니 한번 더 조사
		if($jong===false)$jong=array_search($jongstr, $this->jong_char2);
		// 종성이 있는데도 못 찾은 경우
		if(strlen($jongstr)>0 && $jong===false)
		{
			// 종성 한글자만 찾아서 넣는다.
			$jong=array_search($chars[$jong_start], $this->jong_char);
			$addret="";
			for($i=$jong_start+1;$i<count($chars);++$i)
				$addret.=$chars[$i];
		}

		$unicode=$this->cho_start + $cho*$this->cho_length + $jung*$this->jung_length + $jong;
		// $unicode=chr($unicode >> 8).chr($unicode & 0xff);
		// XXXX XXXX XX XXXXXX
		// 1110XXXX 10XXXX XX 10XXXXXX
		$utf8code= 
			( ( ($unicode & 0xf000) << 4 ) | 0xe00000 ) +
			( ( ($unicode & 0x0fc0) << 2 ) | 0x008000 ) + 
			( ( ($unicode & 0x003f) ) | 0x00080 );
		$utf8=chr($utf8code>>16).chr(($utf8code>>8)&0xff).chr($utf8code&0xff);
		return $utf8.$addret;
	}

	// 한글 풀어쓰기 - UTF8 전용
	// 입력)
	//   $str : 문장 - 한영 혼용 가능
	//   $jong_split : 종성이 2개의 자소인 경우 분리할지 여부, ㄳ => ㄱ, ㅅ ( 기본 = false )
	// 리턴)
	//   풀어쓰기한 자소한글 문장을 리턴합니다.
	public function hangulPuli($str, $jong_split=false)
	{
		$result="";
		for($col=0;$col<strlen($str);++$col)
		{
			$c=substr($str,$col,1);
			if((ord($c)&0x80)==0) // 일반 글자
				$result.=$c;
			else {
				$c.=substr($str, $col+1, 2);
				$col+=2;
				$result.=implode("", $this->split_han($c, $jong_split));
			}
		}
		return $result;
	}

	// 한글 조합 - UTF8 전용
	// 입력)
	//   $str : 문장 - 한글 자소로 구성된 문장
	// 리턴)
	//   모아쓰기한 자소한글 문장을 리턴합니다.
	public function hangulJohap($str)
	{
		$str=$this->hangulPuli($str, true);

		// 자소 단위로 배열에 넣는다
		$chars=array();
		$cut=array();
		for($col=0,$cnt=0;$col<strlen($str);++$col)
		{
			$c=substr($str,$col,1);
			if((ord($c)&0x80)==0){ // 일반 글자
				$chars[$cnt]=$c;
				$cut[$cnt]=true;
				$cnt++;
			}
			else {
				$c.=substr($str, $col+1, 2);
				$col+=2;
				$chars[$cnt]=$c;
				if(in_array($c, $this->jung_char)) { // 모음인 경우
					// 바로 앞글자가 자음인 경우만
					if(in_array($chars[$cnt-1], $this->cho_char)) { 
						$cut[$cnt-1]=true; // 해당 부분 커트 정의
					}
				}
				$cnt++;
			}
		}

		// cut 단위로 문장 재구성
		$result="";
		$cc=array();
		for($i=0;$i<count($chars);++$i)
		{
			if($cut[$i]==true){
				$result.=$this->join_han($cc);
				$cc=array($chars[$i]);
			}
			else {
				$cc[]=$chars[$i];
			}
		}
		$result.=$this->join_han($cc);
		return $result;
	}

	// 뒤에서 자소 1글자를 삭제
	// 입력)
	//   $str : 문장
	// 리턴)
	//   자소 1개를 삭제한 문장을 리턴
	public function str_backspace($str){
		$str=$this->hangulPuli($str, true);

		// 자소 단위로 배열에 넣는다
		$chars=array();
		for($col=0,$cnt=0;$col<strlen($str);++$col)
		{
			$c=substr($str,$col,1);
			if((ord($c)&0x80)==0){ // 일반 글자
				$chars[$cnt]=$c;
				$cnt++;
			}
			else {
				$c.=substr($str, $col+1, 2);
				$col+=2;
				$chars[$cnt]=$c;
				$cnt++;
			}
		}

		// 마지막 글자를 제외하고 모두 합침
		$result="";
		for($i=0;$i<count($chars)-1;++$i)
		{
			$result.=$chars[$i];
		}
		return $this->hangulJohap($result);
	}
};

?>