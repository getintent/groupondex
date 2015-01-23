<?php
/**
 * Интерфейс для тестирования
 */

error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once('../vendor/autoload.php');

/** Идентификатор API у групона */
const GROUPON_API_ID = 0;

/** Токен API у групона */
const GROUPON_API_TOKEN = '';

if (
    !isset($_GET['cities']) ||
    !is_array($cities = explode(',', $_GET['cities'])) ||
    empty($cities)
) {
    die('No cities specified');
}

try {
    // Объект запроса к Groupon API
    $request = new GetIntent\Groupondex\Request(GROUPON_API_ID, GROUPON_API_TOKEN);
    // Объект перевода PHP-шных массивов со структурой групон-апи в нечто yml-подобное
    $encoder = new GetIntent\Groupondex\Encoder();
    
    foreach ($cities as $cityId) {
        $cityId = (int)$cityId;

        // Выгребаем инфу по городу чтобы узнать его название и пропихнуть в качестве постфикса категорий
        $city = $request->getCity($cityId);
        // Получаем все офферы в городе
        $offers = $request->getOffersByCity($cityId);

        // Добавляем эти офферы в yml
        $encoder->addOffers($offers, $city['name']);
    }

    header('Content-Type: application/xml;charset=utf-8');
    die($encoder->getFeed());
} catch (GetIntent\Groupondex\Exception $e) {
   die($e->getMessage());
}