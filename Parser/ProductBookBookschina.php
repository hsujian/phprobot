<?php

if (isset($argv[2]) && $argv[1] == 'test') {
	require_once  '../Config/Config.php';
	require_once  APP_HOME . 'Utility/Http.php';
}
require_once APP_HOME . 'Parser/ProductAbstract.php';
require_once APP_HOME . 'Utility/ContentFilter.php';
require_once APP_HOME . 'Crawler/Http.php';

class Parser_ProductBookBookschina extends Parser_ProductAbstract
{
	private $_shopName = '�й�ͼ����';
	private $_shopId = 22;

	private $_categoryTree;

	public function __construct($categoryTree)
	{
		$this->_categoryTree = $categoryTree;
	}

	public function parseSummary($document)
	{
		$itemList = array();
		if (preg_match('|<img src="(http://.+)" alt="(.+)"|Us', $document, $paragraph)) {
			$itemList['Title'] = trim($paragraph[2]);
			$itemList['��Ʒ����'] = trim($paragraph[2]);// Ŀ����Ϊ���ø�ʽ�͵��Ӳ�Ʒ�Ᵽ��һ��
			#$itemList['smallImg'] = '';
			$itemList['IMAGE_SRC'] = $paragraph[1];
			if (strpos($paragraph[1], 'nocover.gif') !== false) {
				unset($itemList['IMAGE_SRC']);
			} else if (preg_match('|(http://.+/)s([^/]+)$|Us', $paragraph[1], $p)) {
				$itemList['IMAGE_SRC'] = $p[1] . $p[2];
			}
		} else {
			return false;
		}
		if (preg_match('|��&nbsp;��&nbsp;�ۣ�<span class=red>(.+)</span>|Us', $document, $paragraph)) {
			$itemList['Price'] = $paragraph[1];
		}
		if (strpos($document, 'outofstock.gif') !== false) {
			$itemList['STOCK'] = 0;
		} else {
			$itemList['STOCK'] = 1;
		}
		if (preg_match('|I&nbsp;S&nbsp;B&nbsp;N ��</td><td[^>]*>(.+)</td>|Us', $document, $match)) {
			$itemList['isbn'] = trim($match[1]);
			$itemList['��Ʒë��'] = trim($match[1]);
		}
		if (preg_match('|��&nbsp;&nbsp;&nbsp;&nbsp;�ߣ�</td><td[^>]*>(.+)</td>|Us', $document, $match)) {
			$itemList['����'] = trim($match[1]);
		}
		if (preg_match('|�� �� �磺</td><td[^>]*><a[^>]*>(.+)</a>|Ums', $document, $match)) {
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
		$itemList = array();

		$itemList['��ϸ��Ϣ'] = array();
		if (preg_match('|<a name=this_intro>.*<div class="section">(.+)</div>|Us', $document, $match)) {
			$itemList['��ϸ��Ϣ']['���ݼ��'] = Utility_ContentFilter::filterHtmlTags($match[1]);
		}
		if (preg_match('|<a name=this_contents>.*<div class="section">(.+)</div>|Us', $document, $match)) {
			$itemList['��ϸ��Ϣ']['����Ŀ¼'] = Utility_ContentFilter::filterHtmlTags($match[1]);
		}
		if (preg_match('|<a name=this_captor>.*<div class="section">(.+)</div>|Us', $document, $match)) {
			$itemList['��ϸ��Ϣ']['���½�ѡ'] = Utility_ContentFilter::filterHtmlTags($match[1]);
		}
		if (preg_match('|<a name=this_author>.*<div class="section">(.+)</div>|Us', $document, $match)) {
			$itemList['��ϸ��Ϣ']['���߽���'] = Utility_ContentFilter::filterHtmlTags($match[1]);
		}
		if (count($itemList['��ϸ��Ϣ']) < 1) {
			unset($itemList['��ϸ��Ϣ']);
		}
		return $itemList;
	}

	public function parseComments($document)
	{
		$itemList = array();
		if (preg_match('|<a name=book_review>(.+)<p|Us', $document, $p)) {
			$c = preg_match_all('|<b>���⣺</b>(.+)����([\d- :]+).*<br><b>���ߣ�</b>(.+)</font>.*<br>&nbsp;&nbsp;&nbsp;&nbsp;(.+)<div align=left>|Us', $p[1], $matches);
			for ($i=0; $i<$c; $i++) {
				$itemList[] = array(
					'USERNAME' => Utility_ContentFilter::filterHtmlTags($matches[3][$i], true),
					'TITLE' => Utility_ContentFilter::filterHtmlTags($matches[1][$i], true),
					'SUMMARY' => Utility_ContentFilter::filterHtmlTags($matches[4][$i], true),
					'POST_TIME' => Utility_ContentFilter::filterHtmlTags($matches[2][$i], true)
				);
			}
		}
		return $itemList;
	}
	public function parseFromInfo ($document, $productUrl)
	{
		$id = '0';
		#http://www.bookschina.com/4138659.htm
		if (preg_match('|/([^/]+)\.htm|', $productUrl, $matches)) {
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
