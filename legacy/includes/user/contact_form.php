<?php
    // Déclarer $contact_form_width avant l'inclusion du formulaire
?>
<div id="trigger-userinfo" style="display:<?php if ('user_contact' != $_POST['operation']) {
    echo 'none';
} ?>">
    <hr  />
    <form action="<?php echo $versCettePage; ?>" method="post">
        <input type="hidden" name="operation" value="user_contact" />
        <input type="hidden" name="id_user" value="<?php echo (int) ($tmpUser['id_user']); ?>" />

        <h2>Formulaire de contact</h2>
        <?php
        // MESSAGES A LA SOUMISSION
        if ('user_contact' == $_POST['operation'] && isset($errTab) && count($errTab) > 0) {
            echo '<div class="erreur">Erreur : <ul><li>'.implode('</li><li>', $errTab).'</li></ul></div>';
        }
        if ('user_contact' == $_POST['operation'] && (!isset($errTab) || 0 === count($errTab))) {
            echo '<p class="info">Votre message a bien été envoyé.</p>';
        } else {
            // info user
            echo '<p>Attention : le destinataire de ce message verra apparaître votre nom, et votre e-mail en clair !</p>'; ?>
        <?php if (!user()) { ?>
                Nom :<br />
                <input type="text" name="nom" class="type1" style="width:<?php echo $contact_form_width; ?>" value="<?php echo html_utf8(stripslashes($_POST['nom'])); ?>" placeholder="" /><br />
                Email :<br />
                <input type="text" name="email" class="type1" style="width:<?php echo $contact_form_width; ?>" value="<?php echo html_utf8(stripslashes($_POST['email'])); ?>" placeholder="" />
            <?php } ?>

            <br />
            Objet :<br />
            <input type="text" name="objet" class="type1" style="width:<?php echo $contact_form_width; ?>" value="<?php echo html_utf8(stripslashes($_POST['objet'])); ?>" placeholder="" /><br />
            Message :<br />
            <textarea name="message" class="type1" style="width:<?php echo $contact_form_width; ?>; height:150px"><?php echo html_utf8(stripslashes($_POST['message'])); ?></textarea>

            <br /><br />
            <input type="submit" class="nice" value="&gt; Envoyer mon message" onclick="$.fancybox.close()" />

        <?php
        }
        ?>

    </form>
	<hr  />
</div>

