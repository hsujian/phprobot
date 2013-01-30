<?php

if (isset($argv[2]) && $argv[1] == 'test') {
	require_once  '../Config/Config.php';
	require_once  APP_HOME . 'Utility/Http.php';
}

require_once APP_HOME . 'Parser/ProductAbstract.php';
require_once APP_HOME . 'Utility/ContentFilter.php';
require_once APP_HOME . 'Crawler/Http.php';

class Parser_ProductVjia extends Parser_ProductAbstract
{
	private $_shopName = 'Vjia';
	private $_shopId = 6;

	private $_categoryTree;

	public function parseSummary($document, $productUrl) {
		if (preg_match('#^http://item\.vjia\.com/\d+\.html$#i', $productUrl)) {
		} else {
			return FALSE;
		}
		$document = iconv("UTF-8", "GB18030//IGNORE", $document);
		$itemList = array();
		if (preg_match('#<div class="sp-bigImg">\s*<img .* src="([^"]+)"#iUs', $document, $paragraph)) {
			$itemList['IMAGE_SRC'] = $paragraph[1];
		}
		if (preg_match('/<li class="title">(.+)<\/li>/Us', $document, $paragraph)) {
			$itemList['Title'] = $paragraph[1];
			$itemList['��Ʒ����'] = $paragraph[1];
		}
		if (!isset($itemList['Title'])) {
			return false;
		}
		$itemList['Price'] = $this->_getProductPrice($document);
		
		if (preg_match('#<div class="sp-map">.*</div>#Us', $document, $v)) {
			if (preg_match_all('|<a [^>]*>(.*)</a>|Us', $v[0], $nav)) {
				if ($nav[1][0] == '��ҳ') {
					if (isset($nav[1][3])) {
						$this->_categoryTree[0] = $nav[1][1];
						$this->_categoryTree[1] = $nav[1][2];
						$this->_categoryTree[2] = $nav[1][3];
					} else {
						$this->_categoryTree[0] = '';
						$this->_categoryTree[1] = $nav[1][1];
						$this->_categoryTree[2] = $nav[1][2];
					}
				} else {
					$this->_categoryTree[0] = $nav[1][0];
					$this->_categoryTree[1] = $nav[1][1];
					$this->_categoryTree[2] = $nav[1][2];
				}
			}
		}
		
		list($itemList['Ʒ��'],) = explode(' ', $itemList['Title'], 2);

		foreach($itemList as $key => $value) {
			$itemList[$key] = Utility_ContentFilter::filterHtmlTags($value, True);
		}
		return $itemList;
	}
	
	private function _getProductPrice($document) {
		if (preg_match('#<span id="SpecialPrice"[^>]*>\s*(.*)</span>#Us', $document, $paragraph)) {
			if (preg_match('/([\d\.]+)/', $paragraph[1], $matches)) {
				return $matches[1];
			}
		}
		if (preg_match('#<span id="SellPrice"[^>]*>\s*(.*)</span>#Us', $document, $paragraph)) {
			if (preg_match('/([\d\.]+)/', $paragraph[1], $matches)) {
				return $matches[1];
			}
		}
		return 0;
	}
	
	public function parseDetails($document, $productUrl)
	{
		$document = iconv("UTF-8", "GB18030//IGNORE", $document);
		$itemList = array();
		if (preg_match('/��Ʒ����.*<p>\s*(.*)<\/p>/Us', $document, $paragraph)) {
			$itemList['��Ʒ����'] = Utility_ContentFilter::filterHtmlTags($paragraph[1], true);
		}
		return $itemList;
	}

	public function parseComments($document, $productUrl) {
		return array();
	}
	
	public function parseFromInfo ($document, $productUrl) {
		$id = basename($productUrl, '.html');
		$fromInfo = array(
			'shopName'  => $this->_shopName,
			'shopId'    => $this->_shopId,
			'fromId'    => $id,
			'fromUrl'   => $productUrl,
		);
		return $fromInfo;
	}

	public function toXml($productInfo, $comments=null) {
		$xmlData[] = '<?xml version="1.0" encoding="GB2312"?>';
		$xmlData[] = '<Product>';
		$xmlData[] = '<' . $this->_categoryTree[0]
				 . '><' . $this->_categoryTree[1]
				 . '><' . $this->_categoryTree[2] . '>';

		$xmlData[] = '<��Դ��Ϣ>';
		foreach ($productInfo['fromInfo'] as $key => $value) {
			$line = '<' . $key . '>' . $value . '</' . $key . '>';
			$xmlData[] = $line;
		}
		$xmlData[] = '</��Դ��Ϣ>';
		
		$xmlData[] = '<��Ʒ����>';
		foreach ($productInfo['summary'] as $key => $value) {
			$line = '<' . $key . '>' . $value . '</' . $key . '>';
			$xmlData[] = $line;
		}
		$xmlData[] = '</��Ʒ����>';

		$xmlData[] = '<������>';
		foreach ($productInfo['details'] as $key => $value) {
			$section = '<' . $key . '>';
			if (is_array($value)) {
				foreach ($value as $subKey => $subValue) {
					$line = '<' . $subKey . '>' . $subValue . '</' . $subKey . '>';
					$section .= $line;
				}
			} else {
				$section .= $value;
			}
			$section.= '</' . $key . '>';
			$xmlData[] = $section;
		}
		$xmlData[] = '</������>';
		if ($comments) {
			$xmlData[] = $this->toCommentXml($comments);
		}

		$xmlData[] = '</' . $this->_categoryTree[2]
				 . '></' . $this->_categoryTree[1]
				 . '></' . $this->_categoryTree[0] . '>';
		$xmlData[] = '</Product>';

		return $xmlData;
	}
	
	public function toCommentXml($comments) {
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

$pVjia = new Parser_ProductVjia;
$Parser['#^http://item\.vjia\.com/\d+\.html$#i'] = $pVjia;

if (isset($argv[2]) && $argv[1] == 'test') {
	$pVjia->test($argv[2]);
}