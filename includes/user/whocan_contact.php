<?php 
    // $whocan_selected = $tmpUser['auth_contact_user'];
    // $whocan_table = true; 
    
    $whocan_before = $whocan_between = $whocan_after = null;

    if ($whocan_table) {
        $whocan_before = '<table><tbody><tr><td>';
        $whocan_between = '</td></tr><tr><td>';
        $whocan_after = '</td></tr></tbody></table>';
    }
?>
<div class="nice-checkboxes">
    <?php echo $whocan_before; ?>
    <label for="auth_contact_user-all">
        <input type="radio" <?php if($whocan_selected=='all') echo 'checked="checked"'; ?> name="auth_contact_user" value="all" id="auth_contact_user-all" />
        Tous les visiteurs du site
    </label>
    <?php echo $whocan_between; ?>
    <label for="auth_contact_user-users">
        <input type="radio" <?php if($whocan_selected=='users') echo 'checked="checked"'; ?> name="auth_contact_user" value="users" id="auth_contact_user-users" />
        Tous les adhérents, inscrits et connectés sur ce site
    </label>
    <?php echo $whocan_between; ?>
    <label for="auth_contact_user-none">
        <input type="radio" <?php if($whocan_selected=='none') echo 'checked="checked"'; ?> name="auth_contact_user" value="none" id="auth_contact_user-none" />
        Personne sauf les responsables du club
    </label>
    <?php echo $whocan_after; ?>
</div>