import yt_dlp
import sys
import os

def telecharger_playlist(url_playlist, max_resolution='720p'):
    """
    Télécharge toutes les vidéos d'une playlist YouTube.

    Args:
        url_playlist (str): L'URL complète de la playlist YouTube.
    """
    
    # Options de yt-dlp
    # Documentation complète : https://github.com/yt-dlp/yt-dlp#usage-and-options
    ydl_opts = {
        'format': f'bestvideo[height<={max_resolution.replace("p", "")}][ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]',
        #'format': 'bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]', # Format vidéo/audio préféré
        'outtmpl': os.path.join(
            'YouTube_Downloads', # Dossier racine pour tous les téléchargements
            'Playlists', 
            '%(playlist_title)s', # Nom du sous-dossier (nom de la playlist)
            '%(playlist_index)s - %(title)s.%(ext)s' # Format du nom de fichier
        ),
        'ignoreerrors': True, # Continue même si une vidéo échoue
        'restrictfilenames': True, # Assure que les noms de fichiers sont sûrs
        'progress_hooks': [lambda d: print(f"Téléchargement : {d.get('_percent_str', 'N/A')} de {d.get('_total_bytes_str', 'N/A')} pour {d.get('filename', 'Vidéo en cours...')}") if d['status'] == 'downloading' else None],
    }

    print(f"Tentative de téléchargement de la playlist : {url_playlist}")
    
    try:
        with yt_dlp.YoutubeDL(ydl_opts) as ydl:
            # ydl.download() gère automatiquement les playlists, chaînes, ou vidéos simples.
            ydl.download([url_playlist])
            print("\n✅ Téléchargement de la playlist terminé.")
            
    except Exception as e:
        print(f"\n❌ Une erreur est survenue lors du téléchargement : {e}")
        
# --- Point d'entrée du script ---

if __name__ == "__main__":
    playlist_url = "https://m.youtube.com/playlist?list=PLFKkMcixrKrbG4-7A9PLJv1ukA95oB673"
    telecharger_playlist(playlist_url)