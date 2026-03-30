<?php

class shopOpenaiPluginBackendActions extends waJsonActions
{
    const FILE_LOG = 'shop/plugins/openai/openai.backend.log';

    public function getProductDescriptionAction(): array
    {
        $data = waRequest::post();

        $testUrl = $data['testUrl'];
        if ($testUrl == "") {
            return $this->setResult("", "Установите тестовую ссылку");
        }

        $testImage = $data['testImage'];
        if ($testImage == "") {
            return $this->setResult("", "Установите тестовую картинку");
        }

        $testCharacters = $data['testCharacters'];
        if ($testCharacters == "") {
            return $this->setResult("", "Установите характеристики для теста]");
        }

        $testRequest = $data['testRequest'];
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

    public function getCategoryDescriptionAction(): array
    {

        $data = waRequest::post();

        $testUrl = $data['testUrl'];
        if ($testUrl == "") {
            return $this->setResult("", "Установите тестовую ссылку на категорию");
        }

        $testName = $data['testName'];
        if ($testName == "") {
            return $this->setResult("", "Установите наименование категории");
        }

        $testRequest = $data['testRequest'];
        if ($testRequest == "") {
            return $this->setResult("", "Установите шаблон категории");
        }

        try {
            $class = new shopOpenaiPluginBase();
            $result = $class->getCategoryResponce($testUrl, $testName, $testRequest);
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