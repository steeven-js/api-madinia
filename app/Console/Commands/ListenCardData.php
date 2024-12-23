<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Exception;

class ListenCardData extends Command
{
    protected $signature = 'card:listen';
    protected $description = 'Écoute les données du lecteur de carte';

    private $socket;
    private $ip = "192.168.0.193";
    private $port = 2000;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        try {
            // Création du socket
            $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if ($this->socket === false) {
                throw new Exception("Erreur lors de la création du socket: " . socket_strerror(socket_last_error()));
            }

            // Connexion au terminal
            $result = socket_connect($this->socket, $this->ip, $this->port);
            if ($result === false) {
                throw new Exception("Échec de la connexion: " . socket_strerror(socket_last_error()));
            }

            $this->info("Connecté au terminal sur {$this->ip}:{$this->port}");

            // Boucle d'écoute
            while (true) {
                // Réception des données
                $data = socket_read($this->socket, 1024);
                if ($data !== false) {
                    // Conversion en hexadécimal
                    $hexData = bin2hex($data);
                    $this->info("Données reçues : " . $hexData);

                    try {
                        $cardId = $this->extractCardId($hexData);
                        $this->info("ID de la carte : " . $cardId);

                        // Ici vous pouvez ajouter du code pour traiter l'ID de la carte
                        // Par exemple, l'enregistrer dans la base de données

                    } catch (Exception $e) {
                        $this->error("Format de données invalide: " . $e->getMessage());
                    }
                }
            }
        } catch (Exception $e) {
            $this->error("Erreur : " . $e->getMessage());
        } finally {
            if (isset($this->socket)) {
                socket_close($this->socket);
            }
        }
    }

    private function extractCardId(string $rawData): string
    {
        // Extrait la partie pertinente (fb1f3a01)
        $cardData = substr($rawData, 10, 8);

        // Inverse l'ordre des octets par paires de 2
        $bytesList = str_split($cardData, 2);
        $bytesList = array_reverse($bytesList);

        // Reconstruit la chaîne
        return strtoupper(implode('', $bytesList));
    }
}
