<?php

class shopOpenaiPluginTest2Cli extends waCliController
{
    public function execute()
    {

        // Время окончания работы (текущее время  + 60 секунд)
        $endTime = time() + 20;

        echo "Скрипт запущен. Будет работать 1 минуту.\n";

        while (time() < $endTime) {
            echo "Работает... " . date('H:i:s') . "\n";

            // Пауза 1 секунда
            sleep(1);
        }

        echo "Скрипт завершил работу.\n";
    }

}