<?php

class shopOpenaiPluginSettingsAction extends waViewAction
{
    public function execute()
    {
        $_settings = wa('shop')->getPlugin('openai')->getSettings();

        $settings = [
            'proxy_address' => ($_settings['proxy_address'] ?? ""),
            'proxy_port' => ($_settings['proxy_port'] ?? ""),
            'proxy_login' => ($_settings['proxy_login'] ?? ""),
            'proxy_password' => ($_settings['proxy_password'] ?? ""),
            'api_key' => ($_settings['api_key'] ?? ""),
            'openai_model' => ($_settings['openai_model'] ?? "gpt-5.1"),
            'request_template' => ($_settings['request_template'] ?? ""),
            'test_url' => ($_settings['test_url'] ?? ""),
            'test_image' => ($_settings['test_image'] ?? ""),
            'test_characters' => ($_settings['test_characters'] ?? ""),
        ];

        $this->view->assign('cron', '[путь до интерпретатора]php ' . wa()->getConfig()->getRootPath() . '/cli.php shop OpenaiPluginProcess');
        $this->view->assign('settings', $settings);
    }
}