<?php

namespace App\Console\Commands;

use App\Http\Controllers\TerminalController;
use Illuminate\Console\Command;

class ListenTerminal extends Command
{
    protected $signature = 'terminal:listen';
    protected $description = 'Démarre l\'écoute du terminal de badges';

    public function handle()
    {
        $this->info('Démarrage de l\'écoute du terminal...');

        try {
            $terminal = new TerminalController();
            $terminal->listenCardData();
        } catch (\Exception $e) {
            $this->error("Erreur : " . $e->getMessage());
            return 1;
        }
    }
}
