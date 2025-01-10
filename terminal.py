import socket

def extract_card_id(raw_data):
    # Extrait la partie pertinente (fb1f3a01)
    card_data = raw_data[10:18]

    # Inverse l'ordre des octets par paires de 2
    bytes_list = [card_data[i:i+2] for i in range(0, len(card_data), 2)]
    bytes_list.reverse()

    # Reconstruit la chaîne
    card_id = ''.join(bytes_list)

    return card_id.upper()

def listen_card_data():
    # Création du client TCP
    client = socket.socket(socket.AF_INET, socket.SOCK_STREAM)

    # Paramètres de connexion
    ip = "192.168.0.193"
    port = 2000

    try:
        # Connexion au terminal
        client.connect((ip, port))
        print(f"Connecté au terminal sur {ip}:{port}")

        # Boucle d'écoute des données
        while True:
            # Réception des données
            data = client.recv(1024)
            if data:
                # Conversion en hexadécimal
                hex_data = data.hex()
                # Affichage des données brutes reçues
                print(f"Données reçues : {hex_data}")
                # Extraction et conversion de l'ID de la carte
                try:
                    card_id = extract_card_id(hex_data)
                    print(f"ID de la carte : {card_id}")
                except IndexError:
                    print("Format de données invalide")

    except Exception as e:
        print(f"Erreur : {e}")
    finally:
        client.close()

if __name__ == "__main__":
    listen_card_data()
