<?php

namespace Groupondex;

require_once('Exception.php');

/**
 * Encoder of Groupon API structures to kind of YML format
 * 
 * @package Groupondex
 * @author Ivan Kornoukhov
 */
class Encoder
{
    const SHOP_NAME = 'Groupon';
    
    const SHOP_URL = 'http://groupon.ru/';
    
    const CATEGORY_POSTFIX_DELIMITER = '_';
    
    const CATEGORY_SPACE_REPLACEMENT = '_';
    
    /**
     * @var \DOMDocument $dom
     */
    private $dom;

    /**
     * @var \DOMElement $offers
     */
    private $offers;

    public function __construct() {
        $imp = new \DOMImplementation();
        $dtd = $imp->createDocumentType('yml_catalog', '', 'shops.dtd');

        $dom = $imp->createDocument("", "", $dtd);
        $dom->encoding = 'UTF-8';

        $root = $dom->createElement('yml_catalog');
        $root->setAttribute('date', date('Y-m-d H:i'));

        $shop = $dom->createElement('shop');
        
        // ShopName
        $shop
            ->appendChild($dom->createElement('name'))
            ->appendChild($dom->createTextNode(self::SHOP_NAME));
        
        // ShopUrl
        $shop
            ->appendChild($dom->createElement('url'))
            ->appendChild($dom->createTextNode(self::SHOP_URL));
        
        // Offers
        $offers = $dom->createElement('offers');
        $shop->appendChild($offers);

        $root->appendChild($shop);
        $dom->appendChild($root);

        $this
            ->setDom($dom)
            ->setOffers($offers);
    }

    /**
     * @param \DOMDocument $dom
     * @return $this
     */
    private function setDom(\DOMDocument $dom)
    {
        $this->dom = $dom;

        return $this;
    }

    /**
     * @return \DOMDocument
     */
    private function getDom()
    {
        return $this->dom;
    }

    /**
     * @param \DOMElement $offers
     * @return $this
     */
    private function setOffers(\DOMElement $offers)
    {
        $this->offers = $offers;

        return $this;
    }

    /**
     * @return \DOMElement
     */
    private function getOffers()
    {
        return $this->offers;
    }

    /**
     * @param $string
     * @return mixed
     */
    private static function transliterate($string) {
        static $cyr = array(
            'А','Б','В','Г','Д','Е','Ё','Ж','З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У','Ф',
            'Х','Ц','Ч','Ш','Щ','Ы','Э','Ю','Я','Ь','Ъ','а','б','в','г','д','е','ё','ж','з','и','й','к',
            'л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ы','э','ю','я','ь','ъ','№'
        );
        
        static $translit = array(
            'A','B','V','G','D','E','Yo','Zh','Z','I','J','K','L','M','N','O','P','R','S','T','U',
            'F','Kh','Ts','Ch','Sh','Sch','Y','E','Yu','Ya','','','a','b','v','g','d','e','yo',
            'zh','z','i','j','k','l','m','n','o','p','r','s','t','u','f','kh','ts','ch','sh','sch',
            'y','e','yu','ya','','','#'
        );
        
        return str_replace($cyr, $translit, $string);
    }

    /**
     * @param string $name
     * @param string $cityPostfix
     * @return mixed
     */
    private static function getCategoryName($name, $cityPostfix) {
        return str_replace(
            ' ',
            self::CATEGORY_SPACE_REPLACEMENT,
            self::transliterate($name . self::CATEGORY_POSTFIX_DELIMITER . $cityPostfix)
        );
    }
    
    /**
     * @param array $offer
     * @param string $cityPostfix
     * @return \DOMElement|bool
     */
    public function addOffer(array $offer, $cityPostfix) {
        $deal = $offer['deal'];
        
        // Excluding offers with empty options or images
        if (empty($deal['options']) || empty($deal['images'])) {
            return false;
        }
        
        $dom = $this->getDom();
        $offersEl = $this->getOffers();
        
        $option = $deal['options'][0];

        $offerEl = $dom->createElement('offer');
        $offerEl->setAttribute('id', $offer['id']);
        $offerEl->setAttribute('available', 'true');
        
        // URL
        $offerEl
            ->appendChild($dom->createElement('url'))
            ->appendChild($dom->createTextNode($offer['url']));

        // Price
        $offerEl
            ->appendChild($dom->createElement('price'))
            ->appendChild($dom->createTextNode($option['price']));

        // Old Price
        $offerEl
            ->appendChild($dom->createElement('oldprice'))
            ->appendChild($dom->createTextNode($option['usual_price']));

        // Category Id
        $offerEl
            ->appendChild($dom->createElement('categoryId'))
            ->appendChild($dom->createTextNode(self::getCategoryName($deal['sub_category']['name'], $cityPostfix)));

        // Picture
        $offerEl
            ->appendChild($dom->createElement('picture'))
            ->appendChild($dom->createTextNode($deal['images'][0]['promo']));
        
        // Name
        $offerEl
            ->appendChild($dom->createElement('name'))
            //->appendChild($dom->createTextNode($deal['title']));
            ->appendChild($dom->createTextNode(self::offerName2Text($deal['delivery_title'])));

        // Description
        $offerEl
            ->appendChild($dom->createElement('description'))
            ->appendChild($dom->createTextNode($option['title']));

        $offersEl->appendChild($offerEl);
        
        return $offersEl;
    }

    /**
     * @param $html
     * @return string
     */
    private static function offerName2Text($html) {
        // Awesome logic... :(
        
        // Cut <b> element with contents
        $text = preg_replace('/<b>([\s\S]*?)<\/b>/', '', $html);
        
        // Strip remaining tags
        $text = strip_tags($text);
        
        // Process htmlentities back to text
        $text = html_entity_decode($text, ENT_QUOTES | ENT_XHTML, 'UTF-8');
        
        // Trim remaining spaces
        $text = trim($text);
        
        return $text;
    }

    /**
     * @param array $offers
     * @param string $cityPostfix
     * @return \DOMElement
     */
    public function addOffers(array $offers, $cityPostfix) {
        foreach ($offers as $offer) {
            $this->addOffer($offer, $cityPostfix);
        }

        return $this->getOffers();
    }

    /**
     * @return string
     */
    public function getFeed() {
        return $this->getDom()->saveXML();
    }

    /**
     * @param string $path
     * @return int|bool
     */
    public function save($path) {
        return $this->getDom()->save($path);
    }
}