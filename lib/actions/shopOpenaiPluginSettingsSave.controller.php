<?php

class shopOpenaiPluginSettingsSaveController extends waJsonController
{
    public function execute()
    {
        try {
            $post = warequest::post('settings');

            $settings = array(
                'proxy_address' => $post['proxy_address'],
                'proxy_port' => $post['proxy_port'],
                'proxy_login' => $post['proxy_login'],
                'proxy_password' => $post['proxy_password'],
                'api_key' => $post['api_key'],
                'openai_model' => $post['openai_model'],
                'request_template' => $post['request_template'],
                'test_url' => $post['test_url'],
            );

            wa()->getplugin('openai')->savesettings($settings);
            $this->response = array('msg' => _wp('Saved'));
        } catch (waException $e) {
            $this->setError($e->getMessage());
        }
    }
}
