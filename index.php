<?php
/**
 * Интерфейс для тестирования
 */

error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once('Groupondex/Request.php');
require_once('Groupondex/Encoder.php');

/** Путь к папке, куда буду сохраняться фиды (если используется вариант с сохранением на сервере) */
const XML_SAVE_PATH = '/tmp/';

/** Имя экспортируемого файла */
const XML_FILENAME = 'groupon.xml';

/** Идентификатор API у групона */
const GROUPON_API_ID = 0;

/** Токен API у групона */
const GROUPON_API_TOKEN = '';

/** Флаг, показывающий куда экспортировать фид: false - на сервер, true - на клиент */
const EXPORT_TO_CLIENT = false;

try {
    // Объект запроса к Groupon API
    $request = new Groupondex\Request(GROUPON_API_ID, GROUPON_API_TOKEN);

    $selCities = [];

    // Если нам отправили форму и всё правильно
    if (isset($_POST['cities']) && is_array($_POST['cities']) && !empty($_POST['cities'])) {
        // Объект перевода PHP-шных массивов со структурой групон-апи в нечто yml-подобное
        $encoder = new Groupondex\Encoder();
        $selCities = $_POST['cities'];

        foreach ($selCities as $cityId) {
            $cityId = (int)$cityId;

            // Выгребаем инфу по городу чтобы узнать его название и пропихнуть в качестве постфикса категорий
            $city = $request->getCity($cityId);
            // Получаем все офферы в городе
            $offers = $request->getOffersByCity($cityId);

            // Добавляем эти офферы в yml
            $encoder->addOffers($offers, $city['name']);
        }

        // Экспорт на клиентскую сторону
        if (EXPORT_TO_CLIENT) {
            header('Content-Type: application/xml;charset=utf-8');
            header('Content-Disposition: attachment; filename="' . XML_FILENAME . '"');
    
            die($encoder->getFeed());
        }
        
        // Экспорт на сервер
        $redirectUrl = $_SERVER['PHP_SELF'] . '?success=';
        $redirectUrl .= ($encoder->save(XML_SAVE_PATH . XML_FILENAME) ? '1' : '0');
        
        header('Location: ' . $redirectUrl);
        die;
    }

    // Выгребаем все города для формы
    $allCities = $request->getAllCities();
} catch (Groupondex\Exception $e) {
   die($e->getMessage());
}

header('Content-Type: text/html;charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Групоноэкспорт: тестовый интерфейс</title>

    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
  </head>

  <body>
  
    <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
    <div class="alert alert-success">Файл успешно сохранён на сервере</div>
    <?php elseif (isset($_GET['success']) && $_GET['success'] == '0'): ?>
    <div class="alert alert-danger">Не удалось сохранить файл на сервере</div>
    <?php endif; ?>

    <div class="container">
      <form role="form" action="" method="post">
        <h2>Укажите города для формирования фида</h2>

          <?php foreach ($allCities as $city): ?>
          <label class="checkbox-inline">
              <input type="checkbox" name="cities[]" value="<?= (int)$city['id'] ?>" <?= (in_array((int)$city['id'], $selCities) ? 'checked' : '') ?>>
              <?= htmlspecialchars($city['name']) ?>
          </label>
          <?php endforeach; ?>

        <button class="btn btn-lg btn-primary btn-block" type="submit">Вперёд!</button>
      </form>

    </div>
  </body>
</html>