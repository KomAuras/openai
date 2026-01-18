<?php

class shopOpenaiPluginProcessCli extends waCliController
{
    const FILE_LOG = 'shop/plugins/openai/openai.cli.log';

    public function execute()
    {
        $lockFile = wa()->getTempPath() . "openai.lock";
        $fp = fopen($lockFile, 'w'); // Открываем файл (создаст, если не существует)
        if (flock($fp, LOCK_EX | LOCK_NB)) {
            //sleep(25);
            $this->runProcess();
            flock($fp, LOCK_UN);
            fclose($fp);
            unlink($lockFile);
        } else {
            echo "Файл $lockFile уже занят другим процессом. Завершаем работу.\n";
            fclose($fp);
            exit(1);
        }
    }

    public function runProcess()
    {
        $products = new shopProductModel();
        $pf = new shopProductFeaturesModel();

        /*
        SELECT
            p.id,
            p.name,
            p.meta_title,
            p.description,
            pf.feature_value_id
        FROM
            shop_product as p
            JOIN shop_product_features as pf on pf.product_id = p.id
            JOIN shop_feature as f on f.code = 'openai' and f.id = pf.feature_id
        WHERE
	        pf.feature_value_id = 1
        */

        $sql = <<<EOF
            SELECT
                p.id as product_id,
                p.url
            FROM
                shop_product as p
                JOIN shop_product_features as pf on pf.product_id = p.id
                JOIN shop_feature as f on f.code = 'openai' and f.id = pf.feature_id
            WHERE
                pf.feature_value_id = 1
        EOF;

        $data = $products->query($sql);
        if ($data->count() == 0) {
            return;
        }
        waLog::log("Запуск (" . $data->count() . " шт)", $this::FILE_LOG);

        try {
            $class = new shopOpenaiPluginBase();
        } catch (Exception $e) {
            waLog::log("Исключение при получении класса: " . $e->getMessage(), $this::FILE_LOG);
            die();
        }

        foreach ($data as $row) {

            $productID = $row['product_id'];

            // получаем ссылку на товар
            $product = new shopProduct($productID);

            // получаем ссылку на изображение товара
            $imageUrl = "";
            if (count($product->images)) {
                waLog::dump($product->images);
                foreach ($product->images as $image) {
                    $imageUrl = 'https://' . wa()->getRouting()->getDomains()[0] . shopImage::getUrl($image);
                    break;
                }
            }

            /**
             * Cнимем галку с характеристики openai до того как обратимся к openai
             * что бы в случае невозможности ее снятия не скрипт не долбился в
             * openai до бесконечности
             */
            $features = ['openai' => 0];
            try {
                $pf->setData($product, $features);
            } catch (waDbException $e) {
                waLog::log("Исключение при обновлении характеристики товара: " . $e->getMessage(), $this::FILE_LOG);
                die();
            }

            $url = $product->getProductUrl(true, true, false);
            // обрабатываем ссылку в openai по шаблону
            try {
                $result = $class->getDataResponce($url, $imageUrl);
                echo "обработали: " . $productID . " " . $url . "\n";
            } catch (Exception $e) {
                waLog::log("Исключение при обращении к openai: " . $e->getMessage(), $this::FILE_LOG);
                die();
            }

            // обрабатываем ошибки получения запроса
            if ($result['error'] != "") {
                waLog::log("Ошибка при получении запроса: " . $result['error'], $this::FILE_LOG);
                die();
            }
            // берем первый элемент
            $json = $result['json'][0];

            try {
                if (!isset($json['name']) || $json['name'] == "") {
                    waLog::log("Отсутствует поле name", $this::FILE_LOG);
                    continue;
                }
                if (!isset($json['description']) || $json['description'] == "") {
                    waLog::log("Отсутствует поле description", $this::FILE_LOG);
                    continue;
                }
                if (!isset($json['characters']) || $json['characters'] == "") {
                    waLog::log("Отсутствует поле characters", $this::FILE_LOG);
                    continue;
                }
            } catch (Exception $e) {
                waLog::log("Исключение при попытке читать JSON: " . $e->getMessage(), $this::FILE_LOG);
            }

            // обновляем некоторые поля товара
            $product = new shopProduct($row['product_id']);
            $description = $this->getDescription($json);
            $data = [
                'name' => $json['name'],
                'description' => $description
            ];
            try {
                $product->save($data);
                waLog::log("Сохранили: {$row['product_id']}", $this::FILE_LOG);
            } catch (waDbException $e) {
                waLog::log("Исключение при записи товара: " . $e->getMessage(), $this::FILE_LOG);
                die();
            }
        }

        waLog::log('Обработка завершена (cron)', $this::FILE_LOG);
    }

    /**
     * Возвращает примерно такое описание товара
     *
     * Кольцо из благородного золота 585 пробы обворожит своей изящной формой и сиянием граней, подчёркивая утончённость образа. Лаконичный дизайн с плавными линиями нежно обтекает палец, придавая ему особую изысканность. Искусно огранённые фианиты дарят мягкое мерцание при каждом движении, завершая совершенный ансамбль. Это украшение гармонично дополнит как повседневный, так и вечерний образ, отражая безупречный вкус своей владелицы.
     * <p></p>
     * <p><strong>Характеристики</strong></p>
     * <ul>
     * <li>Вставка: фианит</li>
     * <li>Проба: 585</li>
     * <li>Вес: 1,78 г</li>
     * <li>Размер: 16.5</li>
     * <li>Коллекция: Кабаровский</li>
     * <li>Бренд: Кабаровский</li>
     * <li>Артикул: 1994206</li>
     * </ul>
     */
    private function getDescription($json)
    {
        $result = $json['description'];
        $characters = explode('@', $json['characters']);
        if (count($characters) > 1) {
            $result .= "\n<p></p>\n<p><strong>Характеристики</strong></p>\n<ul>\n";
            foreach ($characters as $character) {
                $result .= "<li>{$character}</li>\n";
            }
            $result .= "</ul>\n";
        }
        return $result;
    }
}