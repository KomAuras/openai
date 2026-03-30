<?php

class shopOpenaiPluginTestCli extends waCliController
{
    public function execute()
    {
        $temp_path = wa()->getTempPath('openai', 'shop');
        $pid_file = $temp_path . '/TEST_process.pid';

        if (!is_dir($temp_path)) {
            waFiles::create($temp_path);
        }

        if (file_exists($pid_file)) {
            $pid = (int)trim(file_get_contents($pid_file));
            if ($pid > 0 && $this->isProcessRunning($pid)) {
                echo 'already running' . PHP_EOL;
                return;
            } else {
                @unlink($pid_file);
            }
        }

        try {
            echo 'run' . PHP_EOL;

            if ($this->isLinux()) {
                $command = '/opt/php82/bin/php '
                    . escapeshellarg(wa()->getConfig()->getRootPath() . '/cli.php')
                    . ' shop OpenaiPluginTest2 > /dev/null 2>&1 & echo $!';

                exec($command, $output);
                $pid = isset($output[0]) ? (int)$output[0] : 0;
            } else {
                $pid = $this->startWindowsProcess();
            }

            if ($pid <= 0) {
                throw new Exception('Не удалось получить PID');
            }

            file_put_contents($pid_file, $pid);

            echo 'started: ' . $pid . PHP_EOL;

        } catch (Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }
    }

    private function startWindowsProcess(): int
    {
        $phpExe = 'C:\\sys\\php\\php.exe';
        $cliPhp = wa()->getConfig()->getRootPath() . '\\cli.php';

        $psCommand =
            '$p = Start-Process -FilePath ' . $this->psQuote($phpExe) .
            ' -ArgumentList @(' .
            $this->psQuote($cliPhp) . ',' .
            $this->psQuote('shop') . ',' .
            $this->psQuote('OpenaiPluginTest2') .
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
            return is_dir("/proc/$pid");
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