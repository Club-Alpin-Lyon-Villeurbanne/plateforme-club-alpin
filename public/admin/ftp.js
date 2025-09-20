var oTable=false; // conteint l'instace de datatables
var currentDir = ''; // dossier courant, var "globale"
var showDirs=true; // montrer les dossiers dans la liste des fichiers à droite ?



function updateRight(dossier, fade){
		
	var tmp, ext, re, ico, weightInfo, fileTimeMs;
	var loadList=new Array(); // images à charger pour aperçu.
	var classList=new Array(); // classes ajoutées à la ligne courante
	
	if(typeof(fade)=="undefined") fade=true;
	// waiter
	if(fade) $('#loading').fadeIn({duration:110, queue:false});
	var chutier=$('#ftp-2-fichiers tbody');
	// vidange dure du tableau
	chutier.html('');
	// vidange du tableau "virtuel" datatables
	if(oTable!=false) oTable.fnClearTable(); // clear table
	$.ajax({
		type: "GET",
		async: false,
		dataType: 'json',
		url: "ftp-ajax.php?dossier="+encodeURIComponent(dossier),
		success: function(jsonMsg){
			var file;
			if(jsonMsg.success){
				
				// mise à jour du dossier courant
				currentDir=dossier; 
				
				// LISTAGE DES DOSSIERS
				if(showDirs){
					for(i=0; i<jsonMsg.dirTab.length; i++){
						dir=jsonMsg.dirTab[i]
						chutier.append(
							'<tr class="line-dir" rel="'+dir+'">'
								+'<td><img src="/img/base/folder.png" alt="" title="" /></td>'
								+'<td class="filename"><span style="display:none">dossier</span><a href="'+dossier+dir+'">'+dir+'</a></td>'
								+'<td><span style="display:none">-1</span></td>'
								+'<td><span style="display:none">-1</span></td>'
								+'<td><span style="display:none">aaa</span></td>'
								+'<td> </td>'
								+'<td><img src="/img/base/bullet_delete.png" alt="DEL" title="Supprimer" class="ftp-dir-delete" /></td>'
							+'</tr>'
						);
					}
				}
				
				// LISTAGE DES FICHIERS
				for(i=0; i<jsonMsg.fileTab.length; i++){
					file=jsonMsg.fileTab[i];
					classList=new Array();
					weightInfo='';
					
					// définition de l'icone
					re = /(?:\.([^.]+))?$/;
					ext=re.exec(file.name)[1].toLowerCase();
					switch(ext){
						case 'jpg':
						case 'jpeg':
						case 'gif':
						case 'png':
						case 'bmp': ico='/img/base/image.png'; 	break;
						case 'pdf': ico='/img/base/pdf.png'; 		break;
						case 'docx':
						case 'doc': ico='/img/base/ms-word.png'; 		break;
						default:	ico='/img/base/fichier.png';
					}
					
					// si c'est une image
					if(ext=='jpg' || ext=='jpeg' || ext=='gif' || ext=='png' || ext=='bmp'){
						
						// classe qui indique qu'elle conteint une image : utiles pour ensuite afficher les aperçus à la place des icones
						classList[classList.length] = 'ftp-img';
						classList[classList.length] = 'ftp-img-'+loadList.length;
						loadList[loadList.length] = file.path;
						
						// classe en fonction du poids
						if(parseInt(file.filesize) < 102400){ // 100 Ko
							classList[classList.length]='ftp-imgsize-ok';
							weightInfo='';
						}
						else if(file.filesize < 307200){ // 300 Ko
							classList[classList.length]='ftp-imgsize-big';
							weightInfo='<acronym title="Cette image est volumineuse et peut ralentir l\'affichage des pages où elle est affichée."><img src="/img/base/bullet_error.png" alt="!" title="" /></acronym>';
						}
						else{ // > 300 Ko
							classList[classList.length]='ftp-imgsize-toobig';
							weightInfo='<acronym title="Cette image est très volumineuse ne devrait pas être intégrée dans une page web, mais uniquement en téléchargement ou dans une galerie photo."><img src="/img/base/bullet_error.png" alt="!" title="" /></acronym>';
						}
					}

					// appends
					chutier.append(
						'<tr class="'+(classList.join(' '))+'">'
							+'<td><a rel="file1" href="'+file.path+'" target="_blank" title="'+file.name+'"><img class="ftp-ico" src="'+ico+'" alt="'+ext+'" title="" /></a></td>'
							+'<td class="filename"><a rel="file2" href="'+file.path+'" target="_blank" title="'+file.name+'">'+file.name+'</a></td>'
							+'<td class="filesize"><span style="display:none">fichier'+pad(file.filesize, 20)+'</span>'+(bytesToSize(file.filesize))+' '+weightInfo+'</td>'
							+'<td><span style="display:none">'+pad(file.filemtime, 13)+'</span>'+myDate(file.filemtime)+'</td>'
							// +'<td>'+file.filetype+'</td>'
							+'<td>'+file.ext+'</td>'
							+'<td>'+(file.imgw?file.imgw+' x '+file.imgh:'')+'</td>'
							// tools :
							+'<td class="ftp-tools">'
								// edit image
								+(ext=='jpg'||ext=='jpeg'||ext=='png'?'<img src="/img/base/image_edit.png" alt="EDIT" title="Modifier / redimensionner cette image" class="ftp-img-edit" />':'')
								// lien
								+'<img src="/img/base/link.png" alt="LINK" title="Partager / URL du fichier" class="ftp-url-share" />'
								// del
								+'<img src="/img/base/bullet_delete.png" alt="DEL" title="Supprimer" class="ftp-file-delete" />'
							+'</td>'
						+'</tr>'
					);
				}
				
				// mise à jour du fil d'ariane
				$('#ftp-ariane').html(currentDir.replace(/\//gi, '<span>/</span>'));
				
				// si des images doivent être chargées, elles prennent la place de leur logo
				// BUG : lié à l'ordonnancement de datatables : les fichiers ne snot pas affichés dans le même ordre, le temps que les 
				// apercus aient chargé
				for(i=0; i<loadList.length; i++){
					$('<img src="'+loadList[i]+'" id="preloadImg-'+i+'" />').load(function(){
						var i=parseInt($(this).attr('id').substr(11));
						$('.ftp-img-'+i).find('img:first').attr('src', loadList[i]);
					});
				}
				
				// les liens vers des images sont remplacés par des liens fancybox
				$('.ftp-img a').fancybox();
				
				// outils : URL
				$('.ftp-url-share').click(function(){
					var target=$(this).parents('tr:first').find('a:first').attr('href');
					$('#freeFancyFrame').attr('href', 'ftp-url.php?target='+encodeURIComponent(target)).click();
				});
				
				// outils : DELETE
				$('.ftp-file-delete').click(function(){
					var target=$(this).parents('tr:first').find('a:first').attr('href');
					$('#freeFancyFrame').attr('href', 'ftp-deletefile.php?target='+encodeURIComponent(target)+'&operation=delete').click();
				});
				
				// outils : DELETE DIR
				$('.ftp-dir-delete').click(function(){
					var target=$(this).parents('tr:first').find('a:first').attr('href');
					$('#freeFancyFrame').attr('href', 'ftp-deletedir.php?target='+encodeURIComponent(target)).click();
				});
				
			}
			else{
				chutier.html('<ul class="erreur"><li>'+jsonMsg.error.join('</li><li>')+'</li></ul>');
			}
		},
		error : function(msg){
			alert( "Erreur Ajax " + msg );
		}
	});
	/* */
	
	// on retire le loading
	if(fade) $('#loading').fadeOut({duration:110, queue:false});
	
	// reset datatables
	oTable = $('#ftp-2-fichiers').dataTable({
		"bDestroy": true,
		"aaSorting": [[3, "desc"], [0, "asc"]],
		"bPaginate": false
	});
	
	// actualisation de la taille de la frame
	window.parent.actu_iframe();
	
	// mise à jour de l'uploader et de sn dossier cible
	createUploader();
}




$(window).load(function(){
	
	$().ready(function() {
		
		
		// SETTIN UP DROITE PAR DÉFAUT : PREMIER LIEN SELECTIONNE
		updateRight($('#ftp-2-arbo a.selected').attr('href'), false);
		
		// ****
		// A GAUCHE 
		
		// outils : CREATINON DE DOSSIER
		$('.ftp-dir-add').click(function(){
			$('#freeFancyFrame').attr('href', 'ftp-adddir.php?target='+encodeURIComponent(currentDir)).click();
		});
		
		// SUPPRESSION DES TRIGGERS INUTILES
		$('.removetrigger').each(function(){
			$(this).siblings('.dirtrigger').addClass('off');
		});
		// CLICK SUR LIENS GAUCHE : trigger
		$('#ftp-2-arbo a.dirtrigger').bind('click', function(){
			if(!$(this).hasClass('off')){
				// déroulement des contenus
				$(this).siblings('.level').slideToggle({duration:150, queue:false, step:function(e){
					if(e) window.parent.actu_iframe();
				}});
				$(this).toggleClass('open');
			}
			// desactivation du comportement par defaut
			return false;
		});
		
		// CLICK SUR LIENS GAUCHE : update
		$('#ftp-2-arbo a.dirlink').bind('click', function(){
			// mise à jour de la fenêtre de droite
			updateRight($(this).attr('href'));
			// mise en selected du lien
			$('#ftp-2-arbo a.dirlink').removeClass('selected');
			$(this).addClass('selected');
			// desactivation du comportement par defaut
			return false;
		});
		
		// CLICK SUR LIENS DROITE : update toute la frame : reload
		$(document).on('click', '#ftp-2-fichiers .line-dir a', function(){
			document.location.href='ftp.php?dossier='+encodeURIComponent($(this).attr('href')+'/');
			return false;
		});
		
		// verifier la taille de la frame
		$(window).load(function(){
			window.parent.actu_iframe()
		});
		
		// lien unique pour les appels fancybox
		$("#freeFancyFrame").fancybox({
			'type'			:	'iframe',
			'overlayColor'	:	'#dedede',
			'width'			:	500,
			'height'		:	300,
			'speedIn'		:	250, 
			'speedOut'		:	200
		});
	});
});