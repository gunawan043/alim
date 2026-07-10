<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\Prohibitable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'db:safe-wipe')]
class SafeWipeCommand extends Command
{
    use ConfirmableTrait, Prohibitable;

    protected $name = 'db:safe-wipe';
    protected $description = 'Drop all tables in small batches to avoid MySQL deadlocks';

    public function handle()
    {
        if ($this->isProhibited() || ! $this->confirmToProceed()) {
            return Command::FAILURE;
        }

        $database = $this->input->getOption('database');
        $conn = $this->laravel['db']->connection($database);

        $tables = $conn->select('SHOW TABLES');
        $names = [];
        foreach ($tables as $t) {
            $names[] = array_values((array) $t)[0];
        }

        if (empty($names)) {
            $this->components->info('No tables to drop.');
            return 0;
        }

        $conn->statement('SET FOREIGN_KEY_CHECKS=0');

        foreach (array_chunk($names, 5) as $i => $chunk) {
            $sql = 'DROP TABLE IF EXISTS ' . implode(',', array_map(fn ($t) => '`' . $t . '`', $chunk));
            try {
                $conn->statement($sql);
            } catch (\Throwable $e) {
                foreach ($chunk as $t) {
                    try {
                        $conn->statement('DROP TABLE IF EXISTS `' . $t . '`');
                    } catch (\Throwable $e2) {
                        // last resort
                    }
                }
            }
        }

        $conn->statement('SET FOREIGN_KEY_CHECKS=1');
        $this->components->info('Dropped all tables successfully.');

        return 0;
    }

    protected function getOptions()
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
        ];
    }
}
