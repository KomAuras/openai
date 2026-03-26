<?php

class shopOpenaiPluginBackendActions extends waJsonActions
{
    const FILE_LOG = 'shop/plugins/openai/openai.backend.log';

    public function getProductDescriptionAction(): array
    {

        $testUrl = $_GET['testUrl'];
        if ($testUrl == "") {
            return $this->setResult("", "Установите тестовую ссылку");
        }

        $testImage = $_GET['testImage'];
        if ($testImage == "") {
            return $this->setResult("", "Установите тестовую картинку");
        }

        $testCharacters = $_GET['testCharacters'];
        if ($testCharacters == "") {
            return $this->setResult("", "Установите характеристики для теста]");
        }

        $testRequest = $_GET['testRequest'];
        if ($testRequest == "") {
            return $this->setResult("", "Установите шаблон");
        }

        try {
            $class = new shopOpenaiPluginBase();
            waLog::log("$testUrl\n$testImage\n$testRequest\n$testCharacters\n", self::FILE_LOG);
            $result = $class->getProductResponce($testUrl, $testImage, $testRequest, $testCharacters);
            waLog::dump($result, self::FILE_LOG);
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $this->setResult($result['response'], $result['error']);
    }

    public function getCategoryDescriptionAction(): array
    {

        $testUrl = $_GET['testUrl'];
        if ($testUrl == "") {
            return $this->setResult("", "Установите тестовую ссылку на категорию");
        }

        $testName = $_GET['testName'];
        if ($testName == "") {
            return $this->setResult("", "Установите наименование категории");
        }

        $testRequest = $_GET['testRequest'];
        if ($testRequest == "") {
            return $this->setResult("", "Установите шаблон категории");
        }

        try {
            $class = new shopOpenaiPluginBase();
            waLog::log("$testUrl\n$testName\n$testRequest\n", self::FILE_LOG);
            $result = $class->getCategoryResponce($testUrl, $testName, $testRequest);
            waLog::dump($result, self::FILE_LOG);
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $this->setResult($result['response'], $result['error']);
    }

    private function setResult(string $result, string $error = "")
    {
        return $this->response = ['status_text' => $result, 'error_text' => $error];
    }
}