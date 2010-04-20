<?php
/**
 * XssFilter
 * 
 * @author foxmeder
 * @param Array $tagArray
 * @param Array $attrArray
 * @param Array $attrKeywordArray
 * @return NULL
 *
 * @example
 * $xss = new XssFilter($tag, $attr, $keyword);
 * $str = $xss->Process($str);
 *
 */
class XssFilter
{

	protected $tagBlackList;
	protected $attrBlackList;
	protected $attrKeywordList;
	protected $regTag;
	protected $regAttr;

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

	protected function GetMerge($arr1, $arr2)
	{
		return array_unique(array_merge((array)$arr1, (array)$arr2));
	}

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

	protected function Decode($source)
	{
		// url decode
		$source = html_entity_decode($source, ENT_QUOTES, "ISO-8859-1");
		// convert decimal
		$source = preg_replace('/&#(\d+);/me',"chr(\\1)", $source);				// decimal notation
		// convert hex
		$source = preg_replace('/&#x([a-f0-9]+);/mei',"chr(0x\\1)", $source);	// hex notation
		return $source;
	}
}
?>