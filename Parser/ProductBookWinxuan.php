<?php

if (isset($argv[2]) && $argv[1] == 'test') {
	require_once  '../Config/Config.php';
	require_once  APP_HOME . 'Utility/Http.php';
}
require_once APP_HOME . 'Parser/ProductAbstract.php';
require_once APP_HOME . 'Utility/ContentFilter.php';
require_once APP_HOME . 'Crawler/Http.php';

class Parser_ProductBookWinxuan extends Parser_ProductAbstract
{
	private $_shopName = '������';
	private $_shopId = 23;

	private $_categoryTree;

	public function __construct($categoryTree)
	{
		$this->_categoryTree = $categoryTree;
	}

	public function parseSummary($document)
	{
		$p = strpos($document, '<div id="cont_main">');
		if ($p !== false) {
			$document = substr($document, $p);
		}
		$p = strpos($document, '<div class="wd">');
		if ($p !== false) {
			$document = substr($document, 0, $p);
		}
		$p = strpos($document, '<div class="title">');
		if ($p !== false) {
			$document = substr($document, 0, $p);
		}
		$document = iconv("UTF-8", "GB18030//IGNORE", $document);
		$itemList = array();
		if (preg_match('|<img .*src="(.+)"|Us', $document, $paragraph)) {
			#$itemList['smallImg'] = '';
			$itemList['IMAGE_SRC'] = $paragraph[1];
			if (strpos($paragraph[1], '_blank.jpg') !== false) {
				unset($itemList['IMAGE_SRC']);
			}
		}
		if (preg_match('|<h4>(.+)<|Us', $document, $paragraph)) {
			$itemList['Title'] = trim($paragraph[1]);
			$itemList['��Ʒ����'] = trim($paragraph[1]);// Ŀ����Ϊ���ø�ʽ�͵��Ӳ�Ʒ�Ᵽ��һ��
		} else {
			return null;
		}
		if (preg_match('|<td class="jd">�ּۣ�<span class="font_red" >��(.+)</span>|Us', $document, $paragraph)) {
			$itemList['Price'] = $paragraph[1];
		}
		if (strpos($document, '�����л�') === false) {
			$itemList['STOCK'] = 0;
		} else {
			$itemList['STOCK'] = 1;
		}
		if (preg_match('|ISBN��(.+)<|Us', $document, $match)) {
			$itemList['isbn'] = trim($match[1]);
			$itemList['��Ʒë��'] = trim($match[1]);
		}
		if (preg_match('|���ߣ�(.+)<|Us', $document, $match)) {
			$itemList['����'] = trim($match[1]);
		}
		if (preg_match('|�����磺(.+)</|Us', $document, $match)) {
			$itemList['������'] = trim($match[1]);
			$itemList['Ʒ��'] = trim($match[1]);// Ŀ����Ϊ���ø�ʽ�͵��Ӳ�Ʒ�Ᵽ��һ��
			$itemList['��������'] = trim($match[1]);// Ŀ����Ϊ���ø�ʽ�͵��Ӳ�Ʒ�Ᵽ��һ��
		}
		foreach($itemList as $key => $value) {
			$itemList[$key] = Utility_ContentFilter::filterHtmlTags($value);
		}
		return $itemList;
	}
	public function parseDetails($document)
	{
		$p = strpos($document, '<div id="cont_main">');
		if ($p !== false) {
			$document = substr($document, $p);
		}
		$p = strpos($document, '<div class="wd">');
		if ($p !== false) {
			$document = substr($document, 0, $p);
		}
		$document = iconv("UTF-8", "GB18030//IGNORE", $document);
		$itemList = array();

		$itemList['��ϸ��Ϣ'] = array();
		if (preg_match('|<h5>�༭�Ƽ�</h5>.*<div class="cont_info">(.+)</div>|Us', $document, $match)) {
			$itemList['��ϸ��Ϣ']['�༭�Ƽ�'] = Utility_ContentFilter::filterHtmlTags($match[1]);
		}
		if (preg_match('|<h5>���ݼ��</h5>.*<div class="cont_info">(.+)</div>|Us', $document, $match)) {
			$itemList['��ϸ��Ϣ']['���ݼ��'] = Utility_ContentFilter::filterHtmlTags($match[1]);
		}
		if (preg_match('|<h5>���߼��</h5>.*<div class="cont_info">(.+)</div>|Us', $document, $match)) {
			$itemList['��ϸ��Ϣ']['���߼��'] = Utility_ContentFilter::filterHtmlTags($match[1]);
		}
		if (preg_match('|<h5>��ժ</h5>.*<div class="cont_info">(.+)</div>|Us', $document, $match)) {
			$itemList['��ϸ��Ϣ']['��ժ'] = Utility_ContentFilter::filterHtmlTags($match[1]);
		}
		if (preg_match('|<h5>ý������</h5>.*<div class="cont_info">(.+)</div>|Us', $document, $match)) {
			$itemList['��ϸ��Ϣ']['ý������'] = Utility_ContentFilter::filterHtmlTags($match[1]);
		}
		if (preg_match('|<h5>Ŀ¼</h5>.*<div class="cont_info">(.+)</div>|Us', $document, $match)) {
			$itemList['��ϸ��Ϣ']['Ŀ¼'] = Utility_ContentFilter::filterHtmlTags($match[1]);
		}
		if (count($itemList['��ϸ��Ϣ']) < 1) {
			unset($itemList['��ϸ��Ϣ']);
		}
		return $itemList;
	}

	public function parseComments($document)
	{
		$p = strpos($document, '<div class="book_pin_body">');
		if ($p !== false) {
			$document = substr($document, $p);
		}
		$p = strpos($document, '<div class="wd">');
		if ($p !== false) {
			$document = substr($document, 0, $p);
		}
		$document = iconv("UTF-8", "GB18030//IGNORE", $document);
		$itemList = array();
		$c = preg_match_all('|<span class="lh24">(.+)</span>.*<span class="f12_b">(.+)</span>.*toDate\(\'(.+)\'\)<ul class="book_pin_txt1">(.+)</ul>|Us', $document, $matches);
		for ($i=0; $i<$c; $i++) {
			$itemList[] = array(
				'USERNAME' => Utility_ContentFilter::filterHtmlTags($matches[1][$i], true),
				'TITLE' => Utility_ContentFilter::filterHtmlTags($matches[2][$i], true),
				'SUMMARY' => Utility_ContentFilter::filterHtmlTags($matches[4][$i], true),
				'POST_TIME' => Utility_ContentFilter::filterHtmlTags($matches[3][$i], true)
			);
		}
		return $itemList;
	}
	public function parseFromInfo ($document, $productUrl)
	{
		$id = '0';
		#http://www.winxuan.com/product/book_1_10550910.html
		if (preg_match('|_(\d+)\.htm|', $productUrl, $matches)) {
			$id = $matches[1];
		}
		$fromInfo = array(
			'shopName'  => $this->_shopName,
			'shopId'    => $this->_shopId,
			'fromId'    => $id,
			'fromUrl'   => $productUrl,
		);
		return $fromInfo;
	}

	public function toXml($productInfo, $comments=null)
	{
		$xmlData = '<?xml version="1.0" encoding="GB2312"?>';
		$xmlData .= '<Product>';
		$xmlData .= '<' . $this->_categoryTree[0]
				 . '><' . $this->_categoryTree[1]
				 . '><' . $this->_categoryTree[2] . '>';

		$xmlData .= '<��Դ��Ϣ>';
		foreach ($productInfo['fromInfo'] as $key => $value) {
			$line = '<' . $key . '>' . $value . '</' . $key . '>';
			$xmlData .= $line;
		}
		$xmlData .= '</��Դ��Ϣ>';
		
		$xmlData .= '<��Ʒ����>';
		foreach ($productInfo['summary'] as $key => $value) {
			$line = '<' . $key . '>' . $value . '</' . $key . '>';
			$xmlData .= $line;
		}
		$xmlData .= '</��Ʒ����>';

		$xmlData .= '<������>';
		foreach ($productInfo['details'] as $key => $value) {
			$section = '<' . $key . '>';
			foreach ($value as $subKey => $subValue) {
				$line = '<' . $subKey . '>' . $subValue . '</' . $subKey . '>';
				$section .= $line;
			}
			$section.= '</' . $key . '>';
			$xmlData .= $section;
		}
		$xmlData .= '</������>';
		if ($comments) {
			$xmlData .= $this->toCommentXml($comments);
		}

		$xmlData .= '</' . $this->_categoryTree[2]
				 . '></' . $this->_categoryTree[1]
				 . '></' . $this->_categoryTree[0] . '>';
		$xmlData .= '</Product>';

		return $xmlData;
	}
	public function toCommentXml($comments)
	{
		//$xmlData = '< ? xml version="1.0" encoding="GB2312" ? >';
		$xmlData = "\n<COMMENTS>\n";
		foreach ($comments as $comment) {
			$section = "<COMMENT>\n";
			foreach ($comment as $key => $value) {
				$line = "<" . $key . "><![CDATA[" . $value . "]]></" . $key . ">\n";
				$section .= $line;
			}
			$section.= "</COMMENT>\n";
			$xmlData .= $section;
		}
		$xmlData .= "</COMMENTS>\n";
		return $xmlData;
	}
}
