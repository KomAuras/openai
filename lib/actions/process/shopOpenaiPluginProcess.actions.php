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
        if (file_exists($pid_file)) {
            $pid = (int)trim(file_get_contents($pid_file));
            if ($pid > 0 && $this->isProcessRunning($pid)) {
                return $this->response = ['status' => 'already_running', 'pid' => $pid];
            } else {
                @unlink($pid_file);
            }
        }

        // Запускаем CLI-скрипт в фоне
        try {

            if ($this->isLinux()) {
                $command = '/opt/php82/bin/php '
                    . escapeshellarg(wa()->getConfig()->getRootPath() . '/cli.php')
                    . ' shop OpenaiPluginInternalProcess > /dev/null 2>&1 & echo $!';

                exec($command, $output);
                $pid = isset($output[0]) ? (int)$output[0] : 0;
            } else {
                $pid = $this->startWindowsProcess();
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

        } catch (\Exception $e) {

            // Инициализируем статус
            file_put_contents($status_file, json_encode([
                'total' => 0,
                'processed' => 0,
                'status' => 'error',
                'started_at' => time()
            ]));
        }
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

    private function startWindowsProcess(): int
    {
        $phpExe = 'php.exe';
        $cliPhp = wa()->getConfig()->getRootPath() . '\\cli.php';

        $psCommand =
            '$p = Start-Process -FilePath ' . $this->psQuote($phpExe) .
            ' -ArgumentList @(' .
            $this->psQuote($cliPhp) . ',' .
            $this->psQuote('shop') . ',' .
            $this->psQuote('OpenaiPluginInternalProcess') .
            ') -WindowStyle Hidden -PassThru; ' .
            '$p.Id';

        $cmd = 'powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -Command ' . escapeshellarg($psCommand);

        $output = [];
        exec($cmd, $output);

        return isset($output[0]) ? (int)trim($output[0]) : 0;
    }

    private function psQuote(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }

    private function isProcessRunning($pid): bool
    {
        $pid = (int)$pid;
        if ($pid <= 0) {
            return false;
        }

        if ($this->isLinux()) {
            //return is_dir("/proc/$pid");
            return posix_getpgid($pid) !== false;
        }

        $output = [];
        exec('tasklist /FI "PID eq ' . $pid . '" /FO CSV /NH 2>NUL', $output);

        if (empty($output)) {
            return false;
        }

        $line = trim($output[0]);

        if ($line === '') {
            return false;
        }

        if (stripos($line, 'No tasks are running') !== false) {
            return false;
        }

        if (stripos($line, 'Информация:') !== false) {
            return false;
        }

        return strpos($line, '"') === 0;
    }

    protected function isLinux()
    {
        return !(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
    }

}