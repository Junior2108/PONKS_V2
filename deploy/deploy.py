#!/usr/bin/env python3
# =====================================================================
# Mise en ligne du site sur l'hébergement GoDaddy, par FTP chiffré (FTPS).
#
#   python3 deploy/deploy.py            -> simulation : affiche ce qui serait envoyé
#   python3 deploy/deploy.py --envoyer  -> envoie vraiment les fichiers
#
# Fichiers envoyés : ceux suivis par Git (git ls-files), sauf la liste
# EXCLUS ci-dessous, + includes/config.production.php envoyé sous le
# nom includes/config.php.
# Un fichier déjà en ligne avec la même taille n'est pas renvoyé.
#
# Identifiants : lus dans ftp.txt (lignes "client : ..." et "pass : ..."),
# jamais affichés.
# =====================================================================

import ftplib
import ssl
import subprocess
import sys
from pathlib import Path

# Serveur GoDaddy. Le certificat est au nom *.prod.sxb1.secureserver.net
# et non à celui de l'IP : on se connecte à l'IP, et on vérifie le
# certificat avec ce nom.
SERVEUR_IP = '92.204.28.237'
SERVEUR_NOM = 'ftp.prod.sxb1.secureserver.net'
DOSSIER_DISTANT = 'public_html'

# Fichiers et dossiers du dépôt à NE PAS mettre en ligne
EXCLUS = (
    '.gitignore',
    '.vscode/',
    'front/',            # sources React : seul js/react/ (compilé) est utile
    'deploy/',
    'database/',         # schéma et scripts en ligne de commande
    'includes/config.example.php',
    'README.md',
    'index.txt',
    'page.html',
    'template_carousel.html',
)

RACINE = Path(__file__).resolve().parent.parent


def lire_identifiants():
    champs = {}
    for ligne in (RACINE / 'ftp.txt').read_text().splitlines():
        if ':' in ligne:
            cle, valeur = ligne.split(':', 1)
            champs[cle.strip()] = valeur.strip()
    return champs['client'], champs['pass']


def fichiers_a_envoyer():
    """Liste de (chemin local, chemin distant)."""
    suivis = subprocess.run(
        ['git', 'ls-files'], cwd=RACINE, capture_output=True, text=True, check=True
    ).stdout.splitlines()

    fichiers = [
        (f, f) for f in suivis
        # "._xxx" : fichiers parasites créés par macOS sur les clés USB
        if not f.startswith(EXCLUS) and not Path(f).name.startswith('._')
    ]
    fichiers.append(('includes/config.production.php', 'includes/config.php'))
    return fichiers


def verifier_avant_envoi():
    """Arrête tout si un oubli rendrait le site en ligne cassé."""
    config = (RACINE / 'includes/config.production.php').read_text()
    if 'METS_ICI_LE_MOT_DE_PASSE' in config:
        sys.exit('Arrêt : remplis le mot de passe dans includes/config.production.php')

    # Les composants React compilés doivent être plus récents que leurs sources
    compile = (RACINE / 'js/react/islands.js').stat().st_mtime
    for source in (RACINE / 'front/src').rglob('*'):
        if source.is_file() and source.stat().st_mtime > compile:
            sys.exit(f'Arrêt : {source.relative_to(RACINE)} modifié, lance "npm run build" dans front/')


class FTPS(ftplib.FTP_TLS):
    """FTP_TLS qui se connecte à l'IP mais vérifie le certificat avec SERVEUR_NOM."""

    def connect(self, *args, **kwargs):
        reponse = super().connect(*args, **kwargs)
        # ftplib vérifie le certificat avec self.host : on le remplace par le bon nom
        self.host = SERVEUR_NOM
        return reponse


def connexion():
    utilisateur, mot_de_passe = lire_identifiants()
    ftp = FTPS(context=ssl.create_default_context(), timeout=60)
    ftp.connect(SERVEUR_IP, 21)
    ftp.login(utilisateur, mot_de_passe)   # login() chiffre la connexion avant d'envoyer le mot de passe
    ftp.prot_p()                           # chiffre aussi le contenu des fichiers
    return ftp


def tailles_distantes(ftp, dossier, resultat):
    """Parcourt le serveur : {chemin relatif: taille} pour tous les fichiers."""
    for nom, infos in ftp.mlsd(f'{DOSSIER_DISTANT}/{dossier}'.rstrip('/'), facts=['type', 'size']):
        chemin = f'{dossier}{nom}'
        if infos['type'] == 'file':
            resultat[chemin] = int(infos['size'])
        elif infos['type'] == 'dir':
            tailles_distantes(ftp, f'{chemin}/', resultat)
    return resultat


def creer_dossiers(ftp, chemin_distant, deja_crees):
    parties = Path(chemin_distant).parent.parts
    for i in range(1, len(parties) + 1):
        dossier = '/'.join(parties[:i])
        if dossier not in deja_crees:
            try:
                ftp.mkd(f'{DOSSIER_DISTANT}/{dossier}')
            except ftplib.error_perm:
                pass   # le dossier existe déjà
            deja_crees.add(dossier)


def main():
    envoyer = '--envoyer' in sys.argv
    if envoyer:
        verifier_avant_envoi()

    ftp = connexion()
    en_ligne = tailles_distantes(ftp, '', {})

    a_envoyer = [
        (local, distant) for local, distant in fichiers_a_envoyer()
        if en_ligne.get(distant) != (RACINE / local).stat().st_size
    ]

    taille_totale = sum((RACINE / local).stat().st_size for local, _ in a_envoyer)
    print(f'{len(a_envoyer)} fichier(s) à envoyer ({taille_totale / 1e6:.1f} Mo)')
    for local, distant in a_envoyer:
        print(f'  {distant}' + (f'   (depuis {local})' if local != distant else ''))

    # Fichiers en ligne qui ne viennent pas du dépôt : signalés, jamais supprimés
    attendus = {distant for _, distant in fichiers_a_envoyer()}
    inconnus = sorted(f for f in en_ligne if f not in attendus and not f.startswith('uploads/works/'))
    if inconnus:
        print('\nEn ligne mais pas dans le dépôt (non supprimés) :')
        for f in inconnus:
            print(f'  {f}')

    if not envoyer:
        print('\nSimulation uniquement. Pour envoyer : python3 deploy/deploy.py --envoyer')
        ftp.quit()
        return

    dossiers = set()
    for n, (local, distant) in enumerate(a_envoyer, 1):
        creer_dossiers(ftp, distant, dossiers)
        with open(RACINE / local, 'rb') as f:
            ftp.storbinary(f'STOR {DOSSIER_DISTANT}/{distant}', f)
        print(f'[{n}/{len(a_envoyer)}] {distant}')

    ftp.quit()
    print('\nTerminé.')


if __name__ == '__main__':
    main()
