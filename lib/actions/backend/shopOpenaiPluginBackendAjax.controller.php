<?php

class shopOpenaiPluginBackendAjaxController extends waJsonController
{
    const FILE_LOG = 'shop/plugins/openai/openai.log';

    public function execute(): array
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
            $result = $class->getProductResponce($testUrl, $testImage, $testRequest, $testCharacters);
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