<?php
if (user()) {
    ?>
	<hr />
	[MENU PROFIL]
	<nav>
		<ul>
			<li><a href="profil/infos.html" title="Mes infos personnelles et ma confidentialité" class="<?php if ('profil' == $p1 && 'infos' == $p2) {
        echo 'up';
    } ?>">Mon profil</a></li>
			<li><a href="profil/sorties.html" title="Récapitulatif de vos sorties" class="<?php if ('profil' == $p1 && 'sorties' == $p2) {
        echo 'up';
    } ?>">Mes sorties</a></li>
			<li><a href="profil/articles.html" title="Les articles que vous avez écrits, auxquels vous avez contribué..." class="<?php if ('profil' == $p1 && 'articles' == $p2) {
        echo 'up';
    } ?>">Mes articles</a></li>
			<li><a href="profil/photos.html" title="Gérez les photos que vous avez partagé sur le site" class="<?php if ('profil' == $p1 && 'photos' == $p2) {
        echo 'up';
    } ?>">Mes photos</a></li>
			<li><a href="profil/filiation.html" title="Liez votre compte avec celui de vos proches, et organisez des sorties en famille" class="<?php if ('profil' == $p1 && 'filiation' == $p2) {
        echo 'up';
    } ?>">Filiations</a></li>
		</ul>
	</nav>
	<?php
}
