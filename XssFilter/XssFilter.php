<?php
/**
 * XssFilter
 * @version 0.1
 * @author foxmeder
 *
 * @example
 * $xss = new XssFilter($tag, $attr, $keyword);
 * $str = $xss->Process($str);
 *
 */
class XssFilter
{

	/**
	 *
	 * @var <Array>
	 */
	protected $tagBlackList;
	/**
	 *
	 * @var <Array>
	 */
	protected $attrBlackList;
	/**
	 *
	 * @var <Array>
	 */
	protected $attrKeywordList;
	/**
	 *
	 * @var <String>
	 */
	protected $regTag;
	/**
	 *
	 * @var <String>
	 */
	protected $regAttr;

	/**
	 * constructor
	 * @param <Array> $tagArray
	 * @param <Array> $attrArray
	 * @param <Array> $attrKeywordArray
	 */
	function  __construct($tagArray = array(), $attrArray = array(), $attrKeywordArray = array())
	{
		$tagDef = array('applet', 'body', 'bgsound', 'base', 'basefont', 'embed', 'frame', 'frameset', 'head', 'html', 'id', 'iframe', 'ilayer', 'layer', 'link', 'meta', 'name', 'object', 'script', 'style', 'title', 'xml');
		$attrDef = array('action', 'background', 'codebase', 'dynsrc', 'lowsrc');
		$attrKeywordDef = array('expression', 'javascript\:', 'behavior\:', 'vbscript\:', 'mocha\:', 'livescript\:');
		$this->tagBlackList = $this->GetMerge($tagDef, $tagArray);
		$this->attrBlackList = $this->GetMerge($attrDef, $attrArray);
		$this->attrKeywordList = $this->GetMerge($attrKeywordDef, $attrKeywordArray);
		$this->GetReg();
	}

	/**
	 * processer
	 * @param <String> $str
	 * @return <String>
	 */
	public function Process($str)
	{
		$str = $this->Decode($str);
		var_dump($str);
		$str = preg_replace($this->regTag, '', $str);
		while($str != ($str = preg_replace($this->regTag, '', $str)))
		{

		}
		$str = preg_replace($this->regAttr, '\1\2', $str);
		while($str != ($str = preg_replace($this->regAttr, '\1\2', $str)))
		{

		}
		return $str;
	}

	/**
	 * merge 2 array
	 * @param <Array> $arr1
	 * @param <Array> $arr2
	 * @return <Array>
	 */
	protected function GetMerge($arr1, $arr2)
	{
		return array_unique(array_merge((array)$arr1, (array)$arr2));
	}

	/**
	 * generate patterns
	 */
	protected function GetReg()
	{
		$tag = '(?:\w+)';
		$sptag = count($this->tagBlackList) > 0 ? ('(?:'.implode('|', $this->tagBlackList).')') : $tag;
		$attrNormal = '(?:\s+[\w-]+(?:\s*=\s*[\'\"]?[^>\r\n]*[\'\"]?)?)';
		$attrFilter = count($this->attrBlackList) > 0 ? ('(?:'.implode('|', $this->attrBlackList).'|(?:on[\w-]+))') : 'on[\w-]+';
		$spattr1 = '\s+'.$attrFilter.'\s*=\s*(?:\'[^>\']*?\'|\"[^>\"]*?\"|[^>\'\"]*?)';
		$attrKeywordFilter = '(?:'.implode('|', $this->attrKeywordList).')';
		$spattr2 = '\s+[\w-]+\s*=\s*(?:\'[^>\']*'.$attrKeywordFilter.'[^>\'*]*\'|\"[^?\"]*'.$attrKeywordFilter.'[^>\"]*\"|[^>\'\"]*'.$attrKeywordFilter.'[^>\'\"]*)';
		$spattr = "(?:$spattr1|$spattr2)+";
		$regall = "<\/?$tag$attrNormal*\s*\/?>";
		$regtag = "<\/?$sptag$attrNormal*\s*\/?>";
		$regattr = '(<\/?'.$tag.'[^>]*?)'.$spattr.'([^>]*\/?>)';
		$this->regTag = "/$regtag/i";
		$this->regAttr = "/$regattr/i";
	}

	/**
	 * decode entities,decimal notation and hex notation to normal html code
	 * @param <String> $source
	 * @return <String>
	 */
	protected function Decode($source)
	{
		// url decode
		$source = html_entity_decode($source, ENT_QUOTES, "ISO-8859-1");
		// convert decimal
		$source = preg_replace('/&#(\d+);/me',"chr(\\1)", $source);				// decimal notation
		// convert hex
		$source = preg_replace('/&#x([a-f0-9]+);/mei',"XssFilter::UnicodeDecode('\u\\1')", $source);	// hex notation
		return $source;
	}

	/**
	 * 把一个汉字转为unicode的通用函数，不依赖任何库，和别的自定义函数，但有条件
	 * 条件：本文件以及函数的输入参数应该用utf－8编码，不然要加函数转换
	 * 其实亦可轻易编写反向转换的函数，甚至不局限于汉字，奇怪为什么php没有现成函数
	 * @author xieye
	 *
	 * @param {string} $word 必须是一个汉字，或代表汉字的一个数组(用str_split切割过)
	 * @return {string} 一个十进制unicode码，如4f60，代表汉字 “你”
	 */
	static function getUnicodeFromOneUTF8($word) {
		//获取其字符的内部数组表示，所以本文件应用utf-8编码！
		if (is_array( $word))
			$arr = $word;
		else
			$arr = str_split($word);
		//此时，$arr应类似array(228, 189, 160)
		//定义一个空字符串存储
		$bin_str = '';
		//转成数字再转成二进制字符串，最后联合起来。
		foreach ($arr as $value)
			$bin_str .= decbin(ord($value));
		//此时，$bin_str应类似111001001011110110100000,如果是汉字"你"
		//正则截取
		$bin_str = preg_replace('/^.{4}(.{4}).{2}(.{6}).{2}(.{6})$/','$1$2$3', $bin_str);
		// 此时， $bin_str应类似0100111101100000,如果是汉字"你"
		//return bindec($bin_str); //返回类似20320， 汉字"你"
		return dechex(bindec($bin_str)); //如想返回十六进制4f60，用这句
	}

	static function UnicodeEncode($name) {
		$name = iconv('UTF-8', 'UCS-2', $name);
		$len = strlen($name);
		$str = '';
		for ($i = 0; $i < $len - 1; $i = $i + 2)
		{
			$c = $name[$i];
			$c2 = $name[$i + 1];
			if (ord($c) > 0)
			{   //两个字节的文字
				$str .= '\u'.base_convert(ord($c), 10, 16).str_pad(base_convert(ord($c2), 10, 16), 2, 0, STR_PAD_LEFT);
			}
			else
			{
				$str .= $c2;
			}
		}
		return $str;
	}

	//将UNICODE编码后的内容进行解码
	static function UnicodeDecode($name) {
		//转换编码，将Unicode编码转换成可以浏览的utf-8编码
		var_dump($name);
		$pattern = '/([\w]+)|(\\\u([0-9a-f]{4}))/i';
		preg_match_all($pattern, $name, $matches);
		if (!empty($matches))
		{
			$name = '';
			for ($j = 0; $j < count($matches[0]); $j++)
			{
				$str = $matches[0][$j];
				if (strpos($str, '\\u') === 0)
				{
					$code = base_convert(substr($str, 2, 2), 16, 10);
					$code2 = base_convert(substr($str, 4), 16, 10);
					$c = chr($code).chr($code2);
					$c = iconv('UCS-2', 'UTF-8', $c);
					$name .= $c;
				}
				else
				{
					$name .= $str;
				}
			}
		}
		return $name;
	}
}
//汉字正则 [\u4E00-\u9FA5]
?>