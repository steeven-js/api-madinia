<?php

namespace App\Http\Controllers;

use App\Models\Bd;
use App\Models\BdRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Contrôleur pour gérer la communication avec le terminal de lecture de badges
 *
 * Pour exécuter :
 * 1. Assurez-vous que le terminal est allumé et connecté au réseau
 * 2. Vérifiez que l'adresse IP (192.168.0.193) et le port (2000) sont corrects
 * 3. Lancez la commande : php artisan terminal:listen
 * 4. Le terminal est prêt à lire les badges
 * 5. Pour arrêter, utilisez Ctrl+C
 */
class TerminalController extends Controller
{
    private $socket;
    private $isConnected = false;

    private function extractCardId($rawData)
    {
        try {
            // Extrait la partie pertinente (fb1f3a01)
            $cardData = substr($rawData, 10, 8);

            // Inverse l'ordre des octets par paires de 2
            $bytesList = str_split($cardData, 2);
            $bytesList = array_reverse($bytesList);

            // Reconstruit la chaîne
            return strtoupper(implode('', $bytesList));
        } catch (\Exception $e) {
            throw new \Exception("Erreur d'extraction de l'ID: " . $e->getMessage());
        }
    }

    public function listenCardData()
    {
        try {
            // Création du client TCP
            $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if ($this->socket === false) {
                throw new \Exception("Échec de la création du socket");
            }

            // Paramètres de connexion
            $ip = "192.168.0.193";
            $port = 2000;

            // Connexion au terminal
            $result = socket_connect($this->socket, $ip, $port);
            if ($result === false) {
                throw new \Exception("Échec de la connexion au terminal");
            }

            $this->isConnected = true;
            print("Connecté au terminal sur {$ip}:{$port}\n");

            // Boucle d'écoute des données
            while (true) {
                // Réception des données
                $data = socket_read($this->socket, 1024);
                if ($data === false) {
                    throw new \Exception("Erreur de lecture du socket");
                }

                if (!empty($data)) {
                    // Conversion en hexadécimal
                    $hexData = bin2hex($data);
                    print("Données reçues : {$hexData}\n");

                    try {
                        // Extraction et conversion de l'ID de la carte
                        $cardId = $this->extractCardId($hexData);
                        print("ID de la carte : {$cardId}\n");

                        // Recherche ou création du badge
                        $badge = Bd::firstOrCreate(
                            ['badge_id' => $cardId],
                            [
                                'status' => Bd::STATUS_ACTIVE,
                                'raw_data' => $hexData
                            ]
                        );

                        // Enregistrement de la lecture
                        BdRead::create([
                            'badge_id' => $badge->id,
                            'raw_data' => $hexData,
                            'status' => BdRead::STATUS_SUCCESS,
                            'message' => 'Badge lu avec succès',
                            'read_at' => now()
                        ]);

                        // Mise à jour des statistiques du badge
                        $badge->update([
                            'last_read_at' => now(),
                            'read_count' => $badge->read_count + 1
                        ]);

                        print("Badge enregistré avec succès\n");
                        print("----------------------------\n");

                    } catch (\Exception $e) {
                        // Enregistrement de l'erreur
                        BdRead::create([
                            'badge_id' => null,
                            'raw_data' => $hexData,
                            'status' => BdRead::STATUS_ERROR,
                            'message' => 'Erreur de lecture du badge',
                            'read_at' => now()
                        ]);

                        print("Erreur de lecture du badge\n");
                        print("----------------------------\n");
                    }
                }

                // Petit délai pour éviter de surcharger le CPU
                usleep(100000); // 100ms
            }

        } catch (\Exception $e) {
            if ($this->isConnected && $this->socket) {
                socket_close($this->socket);
                $this->isConnected = false;
            }

            print("Erreur fatale : " . $e->getMessage() . "\n");
            throw $e;
        }
    }

    public function __destruct()
    {
        if ($this->isConnected && $this->socket) {
            socket_close($this->socket);
            $this->isConnected = false;
            print("Connexion au terminal fermée\n");
        }
    }
}
