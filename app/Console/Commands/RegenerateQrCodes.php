<?php

namespace App\Console\Commands;

use App\Models\EventOrder;
use Illuminate\Console\Command;

class RegenerateQrCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:regenerate-qrcodes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Régénère les QR codes pour toutes les commandes payées';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Début de la régénération des QR codes...');

        $orders = EventOrder::where('status', EventOrder::STATUS_PAID)->get();
        $bar = $this->output->createProgressBar(count($orders));
        $bar->start();

        foreach ($orders as $order) {
            try {
                $order->generateQrCode();
                $bar->advance();
            } catch (\Exception $e) {
                $this->error("Erreur pour la commande {$order->id}: {$e->getMessage()}");
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('Régénération des QR codes terminée !');
    }
}
