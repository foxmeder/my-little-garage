<?php
/**
 * XssFilter
 * @version 0.1
 * @author yangxiangliang <foxmeder@126.com>
 *
 * @example
 * $xss = new XssFilter($tag, $attr, $keyword);
 * $str = $xss->Process($str);
 *
 */
class XssFilter
{

	/**
	 * tag black list
	 * @var <Array>
	 */
	protected $tagBlackList;
	/**
	 * attribute black list
	 * @var <Array>
	 */
	protected $attrBlackList;
	/**
	 * attribute value keyword list
	 * @var <Array>
	 */
	protected $attrKeywordList;
	/**
	 * regexp pattern for tag
	 * @var <String>
	 */
	protected $regTag;
	/**
	 * regexp pattern for attribute
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
	 * ��һ������תΪunicode��ͨ�ú��������κο⣬�ͱ���Զ��庯��������
	 * ���������ļ��Լ�������������Ӧ����utf��8���룬��ȻҪ�Ӻ���ת��
	 * ��ʵ������ױ�д����ת���ĺ��������������ں��֣����Ϊʲôphpû���ֳɺ���
	 * @author xieye
	 *
	 * @param {string} $word ������һ�����֣����?�ֵ�һ������(��str_split�и��)
	 * @return {string} һ��ʮ����unicode�룬��4f60����?�� ���㡱
	 */
	static function getUnicodeFromOneUTF8($word) {
		//��ȡ���ַ���ڲ������ʾ�����Ա��ļ�Ӧ��utf-8���룡
		if (is_array( $word))
			$arr = $word;
		else
			$arr = str_split($word);
		//��ʱ��$arrӦ����array(228, 189, 160)
		//����һ�����ַ�洢
		$bin_str = '';
		//ת��������ת�ɶ������ַ��������������
		foreach ($arr as $value)
			$bin_str .= decbin(ord($value));
		//��ʱ��$bin_strӦ����111001001011110110100000,����Ǻ���"��"
		//�����ȡ
		$bin_str = preg_replace('/^.{4}(.{4}).{2}(.{6}).{2}(.{6})$/','$1$2$3', $bin_str);
		// ��ʱ�� $bin_strӦ����0100111101100000,����Ǻ���"��"
		//return bindec($bin_str); //��������20320�� ����"��"
		return dechex(bindec($bin_str)); //���뷵��ʮ�����4f60�������
	}

	/**
	 * �����ֽ���UNICODE����
	 * @param <String> $name
	 * @return <String>
	 */
	static function UnicodeEncode($name) {
		$name = iconv('UTF-8', 'UCS-2', $name);
		$len = strlen($name);
		$str = '';
		for ($i = 0; $i < $len - 1; $i = $i + 2)
		{
			$c = $name[$i];
			$c2 = $name[$i + 1];
			if (ord($c) > 0)
			{   //�����ֽڵ�����
				$str .= '\u'.base_convert(ord($c), 10, 16).str_pad(base_convert(ord($c2), 10, 16), 2, 0, STR_PAD_LEFT);
			}
			else
			{
				$str .= $c2;
			}
		}
		return $str;
	}

	/**
	 * ��UNICODE���������ݽ��н���
	 * @param <String> $name
	 * @return <String>
	 */
	static function UnicodeDecode($name) {
		//ת�����룬��Unicode����ת���ɿ��������utf-8����
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
//�������� [\u4E00-\u9FA5]
?>