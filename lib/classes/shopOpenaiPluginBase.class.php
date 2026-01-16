<?php

require('vendor/autoload.php');

class shopOpenaiPluginBase
{
    private $proxy_login;
    private $proxy_password;
    private $proxy_address;
    private $proxy_port;
    private $api_key;
    private $openai_model;
    private $request_template;
    private \OpenAI\Client $client;

    public function __construct()
    {
        $s = wa('shop')->getPlugin('openai')->getSettings();

        $this->checkVar($s, $this->proxy_login, 'proxy_login', "Настройте логин прокси");
        $this->checkVar($s, $this->proxy_password, 'proxy_password', "Настройте пароль прокси");
        $this->checkVar($s, $this->proxy_address, 'proxy_address', "Настройте адрес прокси");
        $this->checkVar($s, $this->proxy_port, 'proxy_port', "Настройте порт прокси");
        $this->checkVar($s, $this->api_key, 'api_key', "Установите OpenAI ApiKey");
        $this->checkVar($s, $this->openai_model, 'openai_model', "Установите OpenAI model");
        $this->checkVar($s, $this->request_template, 'request_template', "Установите шаблон");

        $this->client = \OpenAI::factory()
            ->withApiKey($this->api_key)
            ->withHttpClient($client = new \GuzzleHttp\Client([
                'proxy' => "http://{$this->proxy_login}:{$this->proxy_password}@{$this->proxy_address}:{$this->proxy_port}",
            ]))
            ->make();
    }

    private function checkVar(mixed $s, mixed &$varVar, string $varName, string $varError)
    {
        if (!isset($s[$varName]) || $s[$varName] == "") {
            throw new Exception($varError);
        } else {
            $varVar = $s[$varName];
        }
    }

    /**
     * Получаем результат от openai по шаблону
     * @param $url string ссылка на товар
     * @param $template string текст шаблона
     * @return array
     * - response - сырой результат запроса к openai
     * - json - результат декодированный из JSON
     * - error - текст ошибки если была
     */
    public function getDataResponce($url, $template = "")
    {
        $text = $this->getTextFromTemplate($url, $template);

        $response = $this->client->responses()->create([
            'model' => $this->openai_model,
            'input' => $text,
        ]);

        $result = [
            'response' => $response->outputText,
            'json' => "",
            'error' => "",
        ];

        try {
            $json = json_decode($response->outputText, true);
            if (json_last_error()) {
                throw new Exception("Ошибка декодирования JSON: " . json_last_error_msg());
            }
            $result['json'] = $json;
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Получение текста из ссылки и шаблона
     * @param string $url
     * @param string $template
     * @return array|string|string[]
     */
    public function getTextFromTemplate(string $url, string $template): string|array
    {
        return str_ireplace('@url', $url, ($template == "" ? $this->request_template : $template));
    }
}


