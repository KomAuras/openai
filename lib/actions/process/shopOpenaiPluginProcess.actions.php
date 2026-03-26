<?php

class shopOpenaiPluginProcessActions extends waJsonActions
{
    const FILE_LOG = 'shop/plugins/openai/openai.log';

    public function startCategoryProcessAction(): array
    {

        $temp_path = wa()->getTempPath('openai', 'shop');
        $pid_file = $temp_path . '/process.pid';
        $status_file = $temp_path . '/process_status.json';

        // Проверяем, не запущен ли уже процесс
        if ($this->isLinux() && file_exists($pid_file)) {
            $pid = file_get_contents($pid_file);
            if (posix_kill($pid, 0)) {
                return $this->response = ['status' => 'already_running', 'pid' => $pid];
            } else {
                unlink($pid_file); // Удаляем старый PID, если процесс не существует
            }
        }

        // Запускаем CLI-скрипт в фоне
        $command = 'php ' . wa()->getConfig()->getRootPath() . '/cli.php shop OpenaiPluginInternalProcess > /dev/null 2>&1 & echo $!';
        exec($command, $output);
        if ($this->isLinux()) {
            $pid = (int)$output[0];
        } else {
            $pid = 0;
        }

        // Сохраняем PID
        file_put_contents($pid_file, $pid);

        // Инициализируем статус
        file_put_contents($status_file, json_encode([
            'total' => 0,
            'processed' => 0,
            'status' => 'running',
            'started_at' => time()
        ]));

        return $this->response = ['status' => 'started', 'pid' => $pid];
    }

    public function getCategoryProcessStatusAction(): array
    {
        $temp_path = wa()->getTempPath('openai', 'shop');
        $status_file = $temp_path . '/process_status.json';

        if (!file_exists($status_file)) {
            return $this->response = ['status' => 'not_started'];
        }

        $data = json_decode(file_get_contents($status_file), true);

        return $this->response = ['status' => $data['status'], 'processed' => $data['processed'], 'total' => $data['total']];
    }

    protected function isLinux()
    {
        return !(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
    }
}