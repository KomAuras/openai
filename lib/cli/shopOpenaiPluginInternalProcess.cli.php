<?php

class shopOpenaiPluginInternalProcessCli extends waCliController
{
    const FILE_LOG = 'shop/plugins/openai/openai.cli.log';
    private shopCategoryModel $category;
    private string $status_file;

    public function __construct()
    {
        $this->category = new shopCategoryModel();
        $temp_path = wa()->getTempPath('openai', 'shop');
        $this->status_file = $temp_path . '/process_status.json';
    }

    public function execute()
    {
        $data = $this->getCategories();
        $total = $data->count();

        if ($data->count() == 0) {
            return;
        }

        waLog::log("Запуск обработки категорий (" . $data->count() . " шт)", $this::FILE_LOG);

        try {
            $class = new shopOpenaiPluginBase();
        } catch (Exception $e) {
            waLog::log("Исключение при получении класса: " . $e->getMessage(), $this::FILE_LOG);
            $this->setError();
            die();
        }

        file_put_contents($this->status_file, json_encode([
            'total' => $total,
            'processed' => 0,
            'status' => 'running',
        ]));

        $i = 1;
        foreach ($data as $row) {
            //TODO: УБРАТЬ ПОСЛЕ ТЕСТИРОВАНИЯ ==========================================================
            usleep(50000); // 0.05 сек на запись

            $name = $row['name'];
            $url = "https://myjewels.ru/category/" . $row['url'];

            // обрабатываем ссылку в openai по шаблону
            try {
                $result = $class->getCategoryResponce($url, $name);
                echo "обработали: " . $row['id'] . " " . $url . "\n";
            } catch (Exception $e) {
                waLog::log("Исключение при обращении к openai: " . $e->getMessage(), $this::FILE_LOG);
                $this->setError();
                die();
            }

            // обрабатываем ошибки получения запроса
            if ($result['error'] != "") {
                waLog::log("Ошибка при получении запроса: " . $result['error'], $this::FILE_LOG);
                $this->setError();
                die();
            }

            $description = $result['json'];

            try {
                $data = array(
                    'description' => $description,
                );
                $this->category->update($row['id'], $data);
                waLog::log("Сохранили: {$row['id']}", $this::FILE_LOG);
            } catch (waDbException $e) {
                waLog::log("Исключение при записи категории: " . $e->getMessage(), $this::FILE_LOG);
                $this->setError();
                die();
            }

            // Обновляем статус
            file_put_contents($this->status_file, json_encode([
                'total' => $total,
                'processed' => $i,
                'status' => 'running',
            ]));

            $i++;
        }

        waLog::log('Обработка категорий завершена', $this::FILE_LOG);

        // Завершение процесса
        file_put_contents($this->status_file, json_encode([
            'total' => $total,
            'processed' => $total,
            'status' => 'completed',
        ]));

    }

    protected function setError(): void
    {
        file_put_contents($this->status_file, json_encode([
            'total' => 0,
            'processed' => 0,
            'status' => 'error',
        ]));
    }

    protected function getCategories(): waDbResultSelect
    {
        $sql = <<<EOF
            SELECT
                c.id,
                c.name,
                c.url
            FROM
                shop_category c
        EOF;
        $data = $this->category->query($sql);
        return $data;
    }

}