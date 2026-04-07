<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class ServeRealtime extends Command
{
    protected $signature = 'serve:realtime
        {--host=127.0.0.1 : Host for the Laravel development server}
        {--port=8000 : Port for the Laravel development server}
        {--tries=10 : Number of alternate ports to try for the Laravel development server}
        {--no-reload : Do not reload the Laravel development server when the environment file changes}
        {--reverb-host=0.0.0.0 : Host for the Reverb websocket server}
        {--reverb-port=8080 : Port for the Reverb websocket server}';

    protected $description = 'Serve the application and Reverb together for local development';

    /**
     * Buffered child-process output keyed by process name.
     *
     * @var array<string, string>
     */
    protected array $outputBuffers = [
        'server' => '',
        'reverb' => '',
    ];

    public function handle(): int
    {
        $serverProcess = $this->makeArtisanProcess($this->serverArguments());
        $reverbProcess = $this->makeArtisanProcess($this->reverbArguments());

        $stopProcesses = function (?int $signal = null) use ($serverProcess, $reverbProcess): void {
            $this->stopProcess($serverProcess, $signal);
            $this->stopProcess($reverbProcess, $signal);
        };

        $this->registerSignalHandlers($stopProcesses);

        $this->components->info(sprintf(
            'Starting Laravel on http://%s:%s and Reverb on %s:%s',
            $this->option('host'),
            $this->option('port'),
            $this->option('reverb-host'),
            $this->option('reverb-port'),
        ));
        $this->comment('  Press Ctrl+C to stop both processes.');

        $serverProcess->start(fn (string $type, string $buffer) => $this->streamOutput('server', $type, $buffer));
        $reverbProcess->start(fn (string $type, string $buffer) => $this->streamOutput('reverb', $type, $buffer));

        while ($serverProcess->isRunning() || $reverbProcess->isRunning()) {
            if (! $serverProcess->isRunning()) {
                $this->flushBufferedOutput();

                if ($reverbProcess->isRunning()) {
                    $this->warn('Laravel development server stopped. Stopping Reverb...');
                    $this->stopProcess($reverbProcess);
                }

                return $serverProcess->getExitCode() ?? self::FAILURE;
            }

            if (! $reverbProcess->isRunning()) {
                $this->flushBufferedOutput();

                if ($serverProcess->isRunning()) {
                    $this->warn('Reverb stopped. Stopping Laravel development server...');
                    $this->stopProcess($serverProcess);
                }

                return $reverbProcess->getExitCode() ?? self::FAILURE;
            }

            usleep(200000);
        }

        $this->flushBufferedOutput();

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    protected function serverArguments(): array
    {
        $arguments = [
            'serve',
            '--host='.$this->option('host'),
            '--port='.$this->option('port'),
            '--tries='.$this->option('tries'),
        ];

        if ($this->option('no-reload')) {
            $arguments[] = '--no-reload';
        }

        return $arguments;
    }

    /**
     * @return array<int, string>
     */
    protected function reverbArguments(): array
    {
        return [
            'reverb:start',
            '--host='.$this->option('reverb-host'),
            '--port='.$this->option('reverb-port'),
        ];
    }

    /**
     * @param  array<int, string>  $arguments
     */
    protected function makeArtisanProcess(array $arguments): Process
    {
        return new Process(
            array_merge([PHP_BINARY, 'artisan'], $arguments),
            base_path(),
        );
    }

    protected function streamOutput(string $name, string $type, string $buffer): void
    {
        $this->outputBuffers[$name] .= str_replace("\r", "\n", $buffer);
        $lines = explode("\n", $this->outputBuffers[$name]);

        $this->outputBuffers[$name] = array_pop($lines) ?? '';

        foreach ($lines as $line) {
            $line = rtrim($line);

            if ($line === '') {
                continue;
            }

            $this->writeOutputLine($name, $type, $line);
        }
    }

    protected function flushBufferedOutput(): void
    {
        foreach ($this->outputBuffers as $name => $buffer) {
            $line = trim($buffer);

            if ($line === '') {
                continue;
            }

            $this->writeOutputLine($name, Process::OUT, $line);
            $this->outputBuffers[$name] = '';
        }
    }

    protected function writeOutputLine(string $name, string $type, string $line): void
    {
        $output = sprintf('[%s] %s', $name, $line);

        if ($type === Process::ERR) {
            $this->error($output);

            return;
        }

        $this->line($output);
    }

    protected function stopProcess(Process $process, ?int $signal = null): void
    {
        if (! $process->isRunning()) {
            return;
        }

        $process->stop(3, $signal);
    }

    protected function registerSignalHandlers(callable $stopProcesses): void
    {
        $signals = array_values(array_filter([
            defined('SIGINT') ? constant('SIGINT') : null,
            defined('SIGTERM') ? constant('SIGTERM') : null,
            defined('SIGHUP') ? constant('SIGHUP') : null,
            defined('SIGQUIT') ? constant('SIGQUIT') : null,
        ]));

        if ($signals === []) {
            return;
        }

        $this->trap($signals, function (int $signal) use ($stopProcesses): void {
            $stopProcesses($signal);
            exit(self::SUCCESS);
        });
    }
}
