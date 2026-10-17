<?php
// =====================================================================
// Lecteur YouTube intégré
//
// Variable à définir AVANT d'inclure ce fichier :
//   $videoWork : l'œuvre (doit avoir un video_url YouTube valide)
// =====================================================================

$videoId = youtube_id($videoWork['video_url']);
?>
<?php if ($videoId): ?>
    <div class="hero_film_frame">
        <!-- youtube-nocookie.com : la même vidéo, mais YouTube ne dépose pas
             de cookie de suivi tant que le visiteur ne lance pas la lecture -->
        <iframe
            width="1024"
            height="576"
            src="https://www.youtube-nocookie.com/embed/<?= e($videoId) ?>"
            title="<?= e($videoWork['title']) ?>"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            referrerpolicy="strict-origin-when-cross-origin"
            allowfullscreen
        ></iframe>
    </div>
<?php endif; ?>
