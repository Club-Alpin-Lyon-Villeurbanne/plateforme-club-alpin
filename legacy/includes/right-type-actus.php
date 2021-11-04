<div id="right1">
    <div class="right-light">
        &nbsp; <!-- important -->
        <?php
        // PRESENTATION DE LA COMMISSINO
        inclure('presentation-'.($current_commission ?: 'general'), 'right-light-in');

        // SLIDER PARTENAIRES
        include __DIR__.'/../includes/droite-partenaires.php';

        // RECHERCHE
        include __DIR__.'/../includes/recherche.php';
        ?>
    </div>


    <div class="right-green">
        <div class="right-green-in">

            <?php
            // actus sur fond vert
            include __DIR__.'/../includes/droite-actus.php';
            ?>

        </div>
    </div>

</div>
