<?php

require_once __DIR__ . '/vendor/autoload.php';

class shopOpenaiPluginBase
{
    private $proxy_login;
    private $proxy_password;
    private $proxy_address;
    private $proxy_port;
    private $api_key;
    private $openai_model;
    private $request_template;
    private $category_template;
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
        $this->checkVar($s, $this->category_template, 'category_template', "Установите шаблон категории");

        waLog::dump("http://{$this->proxy_login}:{$this->proxy_password}@{$this->proxy_address}:{$this->proxy_port}");

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
     * @param $image string ссылка на картинку товара
     * @param $template string текст шаблона
     * @param $template string список характеритик товара
     * @return array
     * - response - сырой результат запроса к openai
     * - json - результат декодированный из JSON
     * - error - текст ошибки если была
     */
    public function getProductResponce(string $url, string $image = "", string $template = "", string $characters = ""): array
    {
        $result = [
            'response' => "",
            'json' => "",
            'error' => ""
        ];

        $text = $this->getTextFromProductTemplate($url, $template, $characters);

        $data = [
            'model' => $this->openai_model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $text],
                        ['type' => 'image_url', 'image_url' => ['url' => $image]],
                    ],
                ],
            ],
        ];

        try {
            $response = $this->client->chat()->create($data);

            $result['response'] = $response->choices[0]->message->content;
            try {
                $json = json_decode($result['response'], true);
                if (json_last_error()) {
                    throw new Exception("Ошибка декодирования JSON: " . json_last_error_msg());
                }
                $result['json'] = $json;
            } catch (Exception $e) {
                $result['error'] = $e->getMessage();
            }

        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
        }
        return $result;
    }

    /**
     * Получаем результат от openai по шаблону на категорию
     * @param $url string ссылка на категорию
     * @param $name string название категории
     * @param $template string текст шаблона
     * @return array
     * - response - сырой результат запроса к openai
     * - json - результат декодированный из JSON
     * - error - текст ошибки если была
     */
    public function getCategoryResponce(string $url, string $name = "", string $template = ""): array
    {
        $result = [
            'response' => "",
            'json' => "",
            'error' => ""
        ];

        $text = $this->getTextFromCategoryTemplate($url, $name, $template);

        $data = [
            'model' => $this->openai_model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $text],
                    ],
                ],
            ],
        ];
        waLog::dump($data);

        try {
            $response = $this->client->chat()->create($data);
            waLog::dump($response);

            $result['response'] = $response->choices[0]->message->content;
            try {
                $result['json'] = $result['response'];
            } catch (Exception $e) {
                $result['error'] = $e->getMessage();
            }

        } catch (Exception $e) {
            waLog::dump($e->getMessage());
            $result['error'] = $e->getMessage();
        }
        return $result;
    }

    /**
     * Получение текста из ссылки и шаблона товара
     * @param string $url
     * @param string $template
     * @param string $characters
     * @return array|string|string[]
     */
    public function getTextFromProductTemplate(string $url, string $template, string $characters): string|array
    {
        $result = ($template == "" ? $this->request_template : $template);
        $result = str_ireplace('@url', $url, $result);
        $result = str_ireplace('@characters', $characters, $result);
        return $result;
    }

    /**
     * Получение текста из ссылки и шаблона категории
     * @param string $url
     * @param string $name
     * @param string $template
     * @return array|string|string[]
     */
    public function getTextFromCategoryTemplate(string $url, string $name, string $template): string|array
    {
        $result = ($template == "" ? $this->category_template : $template);
        $result = str_ireplace('@url', $url, $result);
        $result = str_ireplace('@name', $name, $result);
        return $result;
    }
}
