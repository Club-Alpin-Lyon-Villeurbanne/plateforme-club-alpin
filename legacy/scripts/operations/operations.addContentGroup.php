<?php

use App\Legacy\LegacyContainer;

$nom_content_inline_group = trim(stripslashes($_POST['nom_content_inline_group']));

// checks
if (!strlen($nom_content_inline_group)) {
    $errTab[] = 'Entrez un nom';
}

$stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('SELECT COUNT(*) FROM caf_content_inline_group WHERE nom_content_inline_group = ?');
$stmt->bind_param('s', $nom_content_inline_group);
$stmt->execute();
$result = $stmt->get_result();
$handleCount = $result;
$stmt->close();

if (getArrayFirstValue($handleCount->fetch_array(\MYSQLI_NUM))) {
    $errTab[] = 'Erreur : ce groupe existe déjà dans la liste';
}

if (!isset($errTab) || 0 === count($errTab)) {
    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare("INSERT INTO `caf_content_inline_group` (`ordre_content_inline_group`, `nom_content_inline_group`) VALUES ('', ?)");
    $stmt->bind_param('s', $nom_content_inline_group);

    if (!$stmt->execute()) {
        $erreur = 'Erreur BDD<br />INSERT INTO caf_content_inline_group';
    }
    $stmt->close();

    $id_content_inline_group = LegacyContainer::get('legacy_mysqli_handler')->insertId();

    $stmt = LegacyContainer::get('legacy_mysqli_handler')->prepare('UPDATE `caf_content_inline_group` SET `ordre_content_inline_group` = ? WHERE `id_content_inline_group` = ? LIMIT 1');
    $stmt->bind_param('ii', $id_content_inline_group, $id_content_inline_group);

    if (!$stmt->execute()) {
        $erreur = 'Erreur BDD<br />UPDATE caf_content_inline_group';
    }
    $stmt->close();
}
