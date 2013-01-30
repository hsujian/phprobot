<?php

require_once APP_HOME . 'Parser/ProductAbstract.php';
require_once APP_HOME . 'Utility/ContentFilter.php';
require_once APP_HOME . 'Crawler/Http.php';

class Parser_ProductLafaso extends Parser_ProductAbstract
{
	private $_shopName = '�ַ�';
	private $_shopId = 14;

	private $_categoryTree;

	public function __construct($categoryTree)
	{
		$this->_categoryTree = $categoryTree;
	}

	public function parseSummary($document)
	{
		$document = iconv('UTF-8', 'GB18030//IGNORE', $document);
		$itemList = array();

		if (strpos($document, '<title>��Ʒ������ʾҳ') !== false) {
			return $itemList;
		}
		if (preg_match('|<img id="smallpic" class="jqzoom" src="([^"]+)"|', $document, $paragraph)) {
			#$itemList['smallImg'] = $this->_saveProductImage($paragraph[1]);
			$itemList['IMAGE_SRC'] = $paragraph[1];
		}
		if (preg_match('|<span class="fl zi" id="pname">(.+)</span>|', $document, $paragraph)) {
			$itemList['Title'] = $paragraph[1];
			$itemList['��Ʒ����'] = $paragraph[1];// Ŀ����Ϊ���ø�ʽ�͵��Ӳ�Ʒ�Ᵽ��һ��
		}
		if (preg_match('|<p class="fl detailpriceboxLin" id="puserprice">(.+)Ԫ</p>|', $document, $paragraph)) {
			$itemList['Price'] = $paragraph[1];
		}
		if (preg_match('|<img src="http://images.lafaso.com/images/sitev5/news_37.jpg" />.*<a[^>]*>(.+)</a>|Ums', $document, $match)) {
			$itemList['Ʒ��'] = trim($match[1]);
		}
		if (preg_match('|<h4 class="h4current">��Ʒ��Ϣ</h4>.*<div class="clear">(.+)</div>|Ums', $document, $match)) {
			$itemList['��Ʒ��Ϣ'] = trim($match[1]);
		}

		if (strpos($document, '<span>Ŀǰ�л�</span>') !== false) {
			$itemList['STOCK'] = 1;
		} else {
			$itemList['STOCK'] = 0;
		}

	/*
		if (preg_match('|<div class="book_detailed" name="__Property_pub">.*<\/ul>|Ums', $document, $paragraph)) {
			if (preg_match('|>([^\<]+)</a>  ��</p>|', $paragraph[0], $match)) {
				$itemList['����'] = trim($match[1]);
			}
			if (preg_match('|<span>I S B N��(\d+)</span>|', $paragraph[0], $match)) {
				$itemList['isbn'] = trim($match[1]);
				$itemList['��Ʒë��'] = trim($match[1]);// Ŀ����Ϊ���ø�ʽ�͵��Ӳ�Ʒ�Ᵽ��һ��
			}
			$itemList['��Ʒ����'] = '';// Ŀ����Ϊ���ø�ʽ�͵��Ӳ�Ʒ�Ᵽ��һ��
			if (preg_match('|<p>����ʱ�䣺([^\<]+)</p>|', $paragraph[0], $match)) {
				$itemList['����ʱ��'] = $match[1];
				$itemList['�ϼ�ʱ��'] = $match[1];// Ŀ����Ϊ���ø�ʽ�͵��Ӳ�Ʒ�Ᵽ��һ��
			}
			if (preg_match('|<ul class="clearfix">(.*)</ul>|Ums', $paragraph[0], $match)) {
				preg_match_all('|<span>([^\��]+)��([^<]+)</span>|', $match[1], $matches, PREG_PATTERN_ORDER);
				if (count($matches[1]) > 0) {
					$keyArray = array();
					foreach($matches[1] as $item) {
						$item = str_replace("��", "", $item);
						$keyArray[] = $item;
					}
					$fieldArray = array_combine($keyArray, $matches[2]);
					foreach($fieldArray as $field => $value) {
						$itemList[$field] = $value;
					}
				}
			}
		}
	*/
		foreach($itemList as $key => $value) {
			$itemList[$key] = Utility_ContentFilter::filterHtmlTags($value);
		}
		return $itemList;
	}
	private function _saveProductImage($imgUrl)
	{
		$imgName = basename($imgUrl);
		$savePath = '../images/s' . $this->_shopId . '/product/' . substr($imgName, 0, 4);
		if (!is_dir($savePath)) {
			@mkdir($savePath, 0744, true);
		}
		$saveFullName = $savePath . '/' . $imgName;
		// not save again
		if (!is_file($saveFullName)) {
			$imgContent = Crawler_Http::getHttpFileContent($imgUrl);
			file_put_contents($saveFullName, $imgContent);
		}
		return $saveFullName;
	}
	private function _saveProductXml()
	{
	}
	public function parseDetails($document)
	{
		$document = iconv('UTF-8', 'GB18030//IGNORE', $document);
		$itemList = array();
		if (preg_match('|<h4 class="h4current">��Ʒ���</h4>(.*)<div id="cont2">|', $document, $paragraph)) {
			$c = preg_match_all('|>(.+)��(.+)<|Us', $paragraph[1], $matches);
			for ($i=0; $i<$c; $i++) {
				$n = Utility_ContentFilter::filterHtmlTags($matches[1][$i]);
				$v = Utility_ContentFilter::filterHtmlTags($matches[2][$i]);
				$itemList[$n] = $v;
			}
		}
		return $itemList;
	}

	public function parseComments($document)
	{
		$itemList = array();
		$p = strpos($document, '<div class="reviewscont drygincontent">');
		if ($p === false) {
			return $itemList;
		}
		$document = substr($document, $p);
		$document = iconv("UTF-8", "GB18030//IGNORE", $document);
		$c = preg_match_all('#<div class="reviewscont drygincontent">.*<p class="top"><strong class="red01 fs14">([^<]+)</strong><Br />����ʱ�䣺(.+)</p>\s*<p class="bottom">(.*)<a#Us', $document, $m);
		for ($i=0; $i<$c; $i++) {
			$itemList[] = array(
				'USERNAME' => Utility_ContentFilter::filterHtmlTags($m[1][$i], true),
				'SUMMARY' => Utility_ContentFilter::filterHtmlTags($m[3][$i], true),
				'POST_TIME' => Utility_ContentFilter::filterHtmlTags($m[2][$i], true)
				);
		}
		return $itemList;
	}
	public function parseFromInfo ($document, $productUrl)
	{
		$id = '0';
		$components = parse_url($productUrl);
		#http://www.lafaso.com/product/33400.html
		if (preg_match('|product/(\d+).html|', $components['path'], $matches)) {
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

		$xmlData .= '<������><������Ϣ>';
		$filterArray = array(
			'smallImg',
			'Title',
			'Price',
			'��������',
			'��Ʒë��',
			'��Ʒ����',
			'�ϼ�ʱ��',
			'Ʒ��',
		);
		foreach ($productInfo['summary'] as $key => $value) {
			if (in_array($key, $filterArray)) {
				continue;
			}
			$line = '<' . $key . '>' . $value . '</' . $key . '>';
			$xmlData .= $line;
		}
		$xmlData .= '</������Ϣ></������>';

		$xmlData .= '<��Ʒ����>';
		foreach ($productInfo['details'] as $key => $value) {
			$section = '<' . $key . '>';
			$line = '<![CDATA[' . $value . ']]>';
			$section .= $line;
			$section.= '</' . $key . '>';
			$xmlData .= $section;
		}
		$xmlData .= '</��Ʒ����>';
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
