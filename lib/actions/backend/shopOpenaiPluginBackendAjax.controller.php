<?php

class shopOpenaiPluginBackendAjaxController extends waJsonController
{
    public function execute(): array
    {
        $testUrl = $_GET['testUrl'];
        if ($testUrl == "") {
            return $this->setResult("", "Установите тестовую ссылку");
        }

        $testRequest = $_GET['testRequest'];
        if ($testRequest == "") {
            return $this->setResult("", "Установите шаблон");
        }

        try {
            $class = new shopOpenaiPluginBase();
            $result = $class->getDataResponce($testUrl, $testRequest);
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