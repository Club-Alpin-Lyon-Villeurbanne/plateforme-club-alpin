<?php

use App\Helper\HtmlHelper;
if (user()) {
    $defaultObject = '';
    $idEvent = $idArticle = 0;
    if ($tmpEvent) {
        $idEvent = (int) $tmpEvent['id_evt'] ?? 0;
        $defaultObject = $tmpEvent['titre_evt'] ?? '';
    }
    if ($tmpArticle) {
        $idArticle = (int) $tmpArticle['id_article'] ?? 0;
        $defaultObject = $tmpArticle['titre_article'] ?? '';
    }
    ?>
<div id="trigger-userinfo" style="display:<?php

use App\Helper\HtmlHelper; if ('user_contact' != ($_POST['operation'] ?? null)) {
    echo 'none';
} ?>">
    <hr  />
    <form action="<?php

use App\Helper\HtmlHelper; echo $versCettePage; ?>" method="post">
        <input type="hidden" name="operation" value="user_contact" />
        <input type="hidden" name="id_user" value="<?php

use App\Helper\HtmlHelper; echo (int) $tmpUser['id_user']; ?>" />
        <input type="hidden" name="id_event" value="<?php

use App\Helper\HtmlHelper; echo $idEvent; ?>" />
        <input type="hidden" name="id_article" value="<?php

use App\Helper\HtmlHelper; echo $idArticle; ?>" />

        <h2>Formulaire de contact</h2>
        <?php

use App\Helper\HtmlHelper;
    // MESSAGES A LA SOUMISSION
    if (isset($_POST['operation']) && 'user_contact' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
        echo '<div class="erreur">Erreur : <ul><li>' . implode('</li><li>', $errTab) . '</li></ul></div>';
    }
    if (isset($_POST['operation']) && 'user_contact' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
        echo '<p class="info">Votre message a bien été envoyé.</p>';
    } else {
        // info user
        echo '<p>Le destinataire de ce message sera informé de votre nom et votre e-mail</p>'; ?>

            <br />
            Objet :<br />
            <input type="text" name="objet" class="type1" style="width:<?php

use App\Helper\HtmlHelper; echo $contact_form_width; ?>" value="<?php echo !empty($_POST['objet']) ? HtmlHelper::escape(stripslashes($_POST['objet'])) : $defaultObject; ?>" placeholder="" /><br />
            Message :<br />
            <textarea name="message" class="type1" style="width:<?php

use App\Helper\HtmlHelper; echo $contact_form_width; ?>; height:150px"><?php echo !empty($_POST['message']) ? HtmlHelper::escape(stripslashes($_POST['message'])) : ''; ?></textarea>

            <br /><br />
            <input type="submit" class="nice" value="&gt; Envoyer mon message" onclick="$.fancybox.close()" />

        <?php

use App\Helper\HtmlHelper;
    } ?>

    </form>
	<hr  />
</div>
    <?php

use App\Helper\HtmlHelper;
}
