<?php

if (isset($argv[2]) && $argv[1] == 'test') {
	require_once  '../Config/Config.php';
	require_once  APP_HOME . 'Utility/Http.php';
}
require_once APP_HOME . 'Parser/ProductAbstract.php';
require_once APP_HOME . 'Utility/ContentFilter.php';
require_once APP_HOME . 'Crawler/Http.php';

class Parser_ProductBook99read extends Parser_ProductAbstract
{
	private $_shopName = '�þ����';
	private $_shopId = 18;

	private $_categoryTree;

	public function __construct($categoryTree)
	{
		$this->_categoryTree = $categoryTree;
	}

	public function parseSummary($document)
	{
		$document = iconv("UTF-8", "GB18030//IGNORE", $document);
		$itemList = array();
		if (preg_match('|<img id="ImageShow" src="([^\"]+)"|', $document, $paragraph)) {
			#$itemList['smallImg'] = $this->_saveProductImage($url);
			if (strpos($paragraph[1], 'nobook-') === false) {
				$itemList['IMAGE_SRC'] = $paragraph[1];
			}
		}
		if (preg_match('|<td class="main-name">([^<]+)</td>|', $document, $paragraph)) {
			$itemList['Title'] = trim($paragraph[1]);
			$itemList['��Ʒ����'] = trim($paragraph[1]);// Ŀ����Ϊ���ø�ʽ�͵��Ӳ�Ʒ�Ᵽ��һ��
		} else {
			return false;
		}
		if (preg_match('|��99�ۡ���<span [^\>]+>([.0-9]*)</span>Ԫ</div>|', $document, $paragraph)) {
			$itemList['Price'] = $paragraph[1];
		}
		if (strpos($document, '<img src="images/zanshiquehuo.gif" id="IMG1"') !== false) {
			$itemList['STOCK'] = 0;
		} else {
			$itemList['STOCK'] = 1;
		}
		if (preg_match('|<td class="main-name">.*<!-- ������Ϣ end-->|Ums', $document, $paragraph)) {
			if (preg_match('|�����ߡ���</span>.*<a[^\>]*>([^\<]+)</a>|Us', $paragraph[0], $match)) {
				$itemList['����'] = trim($match[1]);
			}
			if (preg_match('|��I  S  B  N����</span><span class="main-property-content">(.+)</span>|Us', $paragraph[0], $match)) {
				$itemList['isbn'] = trim($match[1]);
				$itemList['��Ʒë��'] = trim($match[1]);// Ŀ����Ϊ���ø�ʽ�͵��Ӳ�Ʒ�Ᵽ��һ��
			}
			if (preg_match('|�������硿��</span>.*<span[^\>]*>([^\<]+)<|Ums', $paragraph[0], $match)) {
				$itemList['������'] = trim($match[1]);
				$itemList['Ʒ��'] = trim($match[1]);// Ŀ����Ϊ���ø�ʽ�͵��Ӳ�Ʒ�Ᵽ��һ��
				$itemList['��������'] = trim($match[1]);// Ŀ����Ϊ���ø�ʽ�͵��Ӳ�Ʒ�Ᵽ��һ��
			}
			$itemList['��Ʒ����'] = '';// Ŀ����Ϊ���ø�ʽ�͵��Ӳ�Ʒ�Ᵽ��һ��
			if (preg_match('|���������ڡ���</span>[^>]*>([^\<]+)<|Us', $paragraph[0], $match)) {
				$itemList['����ʱ��'] = $match[1];
				$itemList['�ϼ�ʱ��'] = $match[1];// Ŀ����Ϊ���ø�ʽ�͵��Ӳ�Ʒ�Ᵽ��һ��
				if (preg_match('|��(\d+)��|', $paragraph[0], $match)) {
					$itemList['���'] = $match[1];
				}
				if (preg_match('|��(\d+)��|', $paragraph[0], $match)) {
					$itemList['ӡ��'] = $match[1];
				}
			}
			if (preg_match('|���� ҳ ������<span class="main-property-content">(\d+)|Us', $paragraph[0], $match)) {
				$itemList['ҳ��'] = $match[1];
			}
			if (preg_match('|��װ����֡����</span><span class="main-property-content">([^<]+)<|Us', $paragraph[0], $match)) {
				$itemList['��װ'] = $match[1];
			}
			if (preg_match('|��������������</span><span class="main-property-content">([^<]+)<|Us', $paragraph[0], $match)) {
				$itemList['����'] = $match[1];
			}
		}
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
	private function _getProductPrice($priceUrl)
	{
		$priceContent = Crawler_Http::getHttpFileContent($priceUrl);
		if (preg_match('/FFE5([\d\.]+)",/', $priceContent, $paragraph)) {
			return $paragraph[1];
		} else {
			return 0;
		}
	}
	public function parseDetails($document)
	{
		return array();
	}

	public function parseComments($document)
	{
		$itemList = array();
		#Updater(escape("/AjaxControls/ProductComentList"), "dProductCommentList",null,{ name:"prd", value:'884451' });
		if (preg_match('|Updater\(escape\("/AjaxControls/ProductComentList"\), "dProductCommentList",null,\{ name:"prd", value:\'(\d+)\'|', $document, $v)) {
			$document = Crawler_Http::getHttpFileContent('http://www.99read.com/AjaxHelper/AjaxHelper.aspx?AjaxTemplate=/AjaxControls/ProductComentList&prd='.$v[1]);
			$document = iconv("UTF-8", "GB18030//IGNORE", $document);
			$c = preg_match_all('|<table class="comment-item"[^>]*>.*<a [^>]* class="comment-title">(.*)</a>.*<span class="comment-author">.*<a class="red_link" href="http://club\.99read\.com/my/UserIndex\.aspx\?mid=.*">(.+)</a>(.*)����</span>.*<span class="comment-content">(.*)</span>|Us', $document, $matches);
			for ($i=0; $i<$c; $i++) {
				$itemList[] = array(
					'URL' => 'http://www.99read.com/AjaxHelper/AjaxHelper.aspx?AjaxTemplate=/AjaxControls/ProductComentList&prd='.$v[1],
					'USERNAME' => Utility_ContentFilter::filterHtmlTags($matches[2][$i], true),
					'TITLE' => Utility_ContentFilter::filterHtmlTags($matches[1][$i], true),
					'SUMMARY' => Utility_ContentFilter::filterHtmlTags($matches[4][$i], true),
					'POST_TIME' => Utility_ContentFilter::filterHtmlTags($matches[3][$i], true)
					);
			}
		}
		return $itemList;
	}
	public function parseFromInfo ($document, $productUrl)
	{
		$id = '0';
		$components = parse_url($productUrl);
		#http://99read.com/product/detail.aspx?proid=872336&20110304-99SY-XPDH
		if (preg_match('|proid=([^&]+)|', $components['query'], $matches)) {
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
