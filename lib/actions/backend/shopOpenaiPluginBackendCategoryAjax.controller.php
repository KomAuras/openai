<?php

class shopOpenaiPluginBackendCategoryAjaxController extends waJsonController
{
    const FILE_LOG = 'shop/plugins/openai/openai.log';

    public function execute(): array
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