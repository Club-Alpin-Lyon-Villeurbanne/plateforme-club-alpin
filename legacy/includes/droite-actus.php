<?php

use App\Legacy\LegacyContainer;

if ($current_commission) {
    echo '<h1 class="actus-h1"><a href="/accueil/' . $current_commission . '.html" title="Afficher tous les articles pour cette commission">actus</a></h1>';
} else {
    echo '<h1 class="actus-h1"><a href="/accueil.html" title="Afficher tous les articles">actus</a></h1>';
}
?>

<!-- Liste -->
<br />
<div id="actus-list">
    <?php

    $req = 'SELECT *
        FROM  `caf_article`
        WHERE  `status_article` =1
        AND  `une_article` =0 '
        // commission donnée : filtre (mais on inclut les actus club, commission=0)
        . ($current_commission ? ' AND (commission_article = ' . (int) $comTab[$current_commission]['id_commission'] . ' OR commission_article = 0) ' : '')
        . 'ORDER BY `updated_at` DESC
        LIMIT 16';
$handleSql = LegacyContainer::get('legacy_mysqli_handler')->query($req);

while ($handle = $handleSql->fetch_array(\MYSQLI_ASSOC)) {
    // info de la commission liée
    if ($handle['commission_article'] > 0) {
        $req = 'SELECT * FROM caf_commission
                WHERE id_commission = ' . (int) $handle['commission_article'] . '
                LIMIT 1';
        $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
            $handle['commission'] = $handle2;
        }
    }

    // info de la sortie liée
    if ($handle['evt_article'] > 0) {
        $req = 'SELECT code_evt, id_evt, titre_evt FROM caf_evt
                WHERE id_evt = ' . (int) $handle['evt_article'] . '
                LIMIT 1';
        $handleSql2 = LegacyContainer::get('legacy_mysqli_handler')->query($req);
        while ($handle2 = $handleSql2->fetch_array(\MYSQLI_ASSOC)) {
            $handle['evt'] = $handle2;
        }
    }

    // AFFICHAGE
    $article = $handle;

    $url = 'article/' . html_utf8($article['code_article']) . '-' . (int) $article['id_article'] . '.html';
    if ($current_commission) {
        $url .= '?commission=' . $current_commission;
    } ?>

        <!-- titre + lien article -->
        <h2>
            <a href="<?php echo $url; ?>" title="Voir cet article">
                <?php echo html_utf8($article['titre_article']); ?>
            </a>
        </h2>

        <!-- lien commission -->
        <p class="commission-title">

            <?php
        echo (new \DateTime($article['created_at']))?->format('d/m/Y'); ?>


            <?php
        // un ID de commission est bien enregistré
        if (isset($article['commission']) && $article['commission']) {
            ?>
            - <a href="/accueil/<?php echo html_utf8($article['commission']['code_commission']); ?>.html#home-articles" title="Toutes les actus de cette commission">
                    <?php echo html_utf8($article['commission']['title_commission']); ?>
                </a>
                <?php
        }
    // 0 = actu club
    elseif (0 == $article['commission_article']) {
        ?>
                <a href="/accueil.html#home-articles" title="Toutes les actus du club">
                    CLUB
                </a>
                <?php
    }
    // -1 = compte rendu de sortie
    elseif (-1 == $article['commission_article']) {
        $urlEvt = 'sortie/' . $article['evt']['code_evt'] . '-' . $article['evt']['id_evt'] . '.html'; ?>
                <a href="<?php echo $urlEvt; ?>" title="Voir la sortie liée à cet article : &laquo; <?php echo html_utf8($article['evt']['titre_evt']); ?> &raquo;">
                    compte rendu de sortie
                </a>
                <?php
    } ?>
        </p>

        <!-- summup -->
        <p class="summup">
            <?php echo limiterTexte(strip_tags($article['cont_article']), 100); ?>
            <a href="<?php echo $url; ?>" title="Voir cet article">
                [...]
            </a>
        </p>
        <br style="clear:both" />

        <?php
}

?>
</div>

<!-- lien vers la page actus -->
<?php
if ($current_commission) {
    echo '<a href="/accueil/' . $current_commission . '.html" title="Afficher tous les articles pour cette commission" class="lien-big">&gt; Voir tous les articles ' . $comTab[$current_commission]['title_commission'] . '</a>';
} else {
    echo '<a href="/accueil.html" title="Afficher tous les articles" class="lien-big">&gt; Voir tous les articles</a>';
}
