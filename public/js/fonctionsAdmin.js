// afficher une date timestamp en format humain
function myDate(tsp){
	var a= new Date(parseInt(tsp)*1000);
	var year = a.getFullYear();
	var month = a.getMonth()+1;			if(month<10) month='0'+month;
	var date = a.getDate();				if(date<10) date='0'+date;
	var hour = a.getHours();			if(hour<10) hour='0'+hour;
	var min = a.getMinutes(); 			if(min<10) min='0'+min;
	var sec = a.getSeconds();			if(sec<10) sec='0'+sec;
	var time = date+'/'+month+'/'+year+' '+hour+':'+min;
	return time;
}
	
// ajouter des zéros en préfixe à un nombre
function pad(num, size) {
    var s = num+"";
    while (s.length < size) s = "0" + s;
    return s;
}


/**
 * Convert number of bytes into human readable format
 *
 * @param integer bytes     Number of bytes to convert
 * @param integer precision Number of digits after the decimal separator
 * @return string
 */
function bytesToSize(bytes, precision)
{  
    var kilobyte = 1024;
    var megabyte = kilobyte * 1024;
    var gigabyte = megabyte * 1024;
    var terabyte = gigabyte * 1024;
   
    if ((bytes >= 0) && (bytes < kilobyte)) {
        return bytes + ' B';
 
    } else if ((bytes >= kilobyte) && (bytes < megabyte)) {
        return (bytes / kilobyte).toFixed(precision) + ' Ko';
 
    } else if ((bytes >= megabyte) && (bytes < gigabyte)) {
        return (bytes / megabyte).toFixed(precision) + ' Mo';
 
    } else if ((bytes >= gigabyte) && (bytes < terabyte)) {
        return (bytes / gigabyte).toFixed(precision) + ' Go';
 
    } else if (bytes >= terabyte) {
        return (bytes / terabyte).toFixed(precision) + ' To';
 
    } else {
        return bytes + ' B';
    }
}

// mon systeme d'alertes
function lp_alert(msg){
	/*
	var shadow=$('<div class="mybox-down"></div>');
	var promptbox=$('<div class="mybox-up">'+msg+'<a class="bigButton" title="" href="javascript:void(0)">OK</a></div>');
	$('body').append(shadow).append(promptbox);
	$(shadow).fadeIn();
	$(promptbox).fadeIn();
	*/
	msg=$('<div><div style="width:400px">'+msg+'<br style="clear:both"><a class="bigButton" title="" href="javascript:void(0)" style="width:95%" onclick="$.fancybox.close()">OK</a></div><div>');
	$.fancybox([msg.html()]);
	// , {afterShow:function(val){ console.log(val); }}
	
}

// set
$().ready(function() {
	// hover on editable
	var lphovered=false;
	$('.contenuEditable').mouseenter(function(){
		if(!lphovered){
			window.lpTempOutline=$(this).css('outline');
			window.lpTempBgImage=$(this).css('background-image');
			window.lpTempBgRepeat=$(this).css('background-repeat');
		}
		$(this).css('outline', '1px dotted #74b7df');
		$(this).css('background-image', 'url(img/base/travaux.gif)');
		$(this).css('background-repeat', 'repeat');
		// lien
		$(this).find('.editHtmlTools').animate({'opacity':'1'}, {queue:false, duration:200});
		lphovered=true; // debug fond quand duex hovers successifs sans out
	});
	$('.contenuEditable').mouseleave(function(){	
		lphovered=false;
		$(this).css('outline', window.lpTempOutline);
		$(this).css('background-image', window.lpTempBgImage);
		$(this).css('background-repeat', window.lpTempBgRepeat);
		// lien
		$(this).find('.editHtmlTools').animate({'opacity':'0.3'}, {queue:false, duration:200});
	});
	
	// Ajax update content of block
	window.document.contUpdate = function(code){
		// alert('update');
		var elt=$('#'+code);
		var newContent='ERREUR';
		// recup new content
		$.ajax({
			type: "POST",
			async: false,
			dataType: 'json',
			url: "/ajax/get_content_html",
			data: "code="+code,
			success: function(jsonMsg){
				// variable retour
				if(jsonMsg.success)
					newContent=html_entity_decode(jsonMsg.content);
				else
					newContent='ERREUR DE CHARGEMENT : '+jsonMsg.error;
			},
			error : function(msg){
				newContent="Erreur Ajax "+msg;
			}
		});
		// visus
		elt.animate({'opacity':'0'}, {duration:700, easing:'easeOutQuad', complete:function(){
			elt.find('div:first').siblings().remove();
			elt.append(newContent).animate({'opacity':'1'}, {duration:700, easing:'easeOutQuad'});
		}});
	}

	// update vis of block [link=lien avec attribut REL contenant la visibilité]
	window.document.majVisBlock = function(link, code_content_html){
		var vis=parseInt($(link).attr('rel'));
		$("#loading1").after('<div id="updateVisBox" class="mybox-up">Voulez-vous vraiment <b>'+(vis==1?'masquer':'afficher à nouveau')+'</b><br />cet élément aux visiteurs du site ?<br /><br /><a href="javascript:void(0)" id="updateVisSubmit" class="boutonFancy"><img src="img/base/tick2.png" alt="" title="" style="vertical-align:middle" /> Je confirme</a> <a href="javascript:void(0)" id="updateVisCancel" class="boutonFancy"><img src="img/base/cancel.png" alt="" title="" style="vertical-align:middle" /> Annuler</a></div>');
		$("#loading1, #updateVisBox").fadeIn('fast');
		// click cancel
		$("#updateVisCancel").focus().bind('click', function(){
			$("#loading1, #updateVisBox").fadeOut(250, function(){
				$("#updateVisBox").remove();
			});
		});
		// click submit
		$("#updateVisSubmit").focus().bind('click', function(){
			var vis_content_html=vis?0:1;
			$(link).attr('rel', vis_content_html);
			var elt=$('#'+code_content_html);
			$.ajax({
				type: "POST",
				async: false,
				dataType: 'json',
				url: "/ajax/content_html_updatevis",
				data: "code_content_html="+code_content_html+"&vis_content_html="+vis_content_html,
				success: function(jsonMsg){
					// variable retour
					if(jsonMsg.success)
						newContent=html_entity_decode(jsonMsg.content);
					else
						newContent='ERREUR DE CHARGEMENT : '+jsonMsg.error;
				},
				error : function(msg){
					newContent="Erreur Ajax "+msg;
				}
			});

			// maj visus page courante
			elt.animate({'opacity':'0'}, {duration:700, easing:'easeOutQuad', complete:function(){
					elt.find('div:first').siblings().remove();
					elt.append(newContent).animate({'opacity':'1'}, {duration:700, easing:'easeOutQuad'});
				}});

			// on retire la promptbox
			$("#loading1, #updateVisBox").fadeOut(250, function(){
				$("#updateVisBox").remove();
			});
		});
	}
});

// FONCTIONS D'ENCODAGE / DECODAGE DE CHAINES JS 
//Encode une chaîne
function htmlentities(texte) {
	texte = texte.replace(/"/g,'&quot;'); // 34 22
	texte = texte.replace(/&/g,'&amp;'); // 38 26
	texte = texte.replace(/\'/g,'&#39;'); // 39 27
	texte = texte.replace(/</g,'&lt;'); // 60 3C
	texte = texte.replace(/>/g,'&gt;'); // 62 3E
	texte = texte.replace(/\^/g,'&circ;'); // 94 5E
	texte = texte.replace(/‘/g,'&lsquo;'); // 145 91
	texte = texte.replace(/’/g,'&rsquo;'); // 146 92
	texte = texte.replace(/“/g,'&ldquo;'); // 147 93
	texte = texte.replace(/”/g,'&rdquo;'); // 148 94
	texte = texte.replace(/•/g,'&bull;'); // 149 95
	texte = texte.replace(/–/g,'&ndash;'); // 150 96
	texte = texte.replace(/—/g,'&mdash;'); // 151 97
	texte = texte.replace(/˜/g,'&tilde;'); // 152 98
	texte = texte.replace(/™/g,'&trade;'); // 153 99
	texte = texte.replace(/š/g,'&scaron;'); // 154 9A
	texte = texte.replace(/›/g,'&rsaquo;'); // 155 9B
	texte = texte.replace(/œ/g,'&oelig;'); // 156 9C
	texte = texte.replace(//g,'&#357;'); // 157 9D
	texte = texte.replace(/ž/g,'&#382;'); // 158 9E
	texte = texte.replace(/Ÿ/g,'&Yuml;'); // 159 9F
	texte = texte.replace(/ /g,'&nbsp;'); // 160 A0
	texte = texte.replace(/¡/g,'&iexcl;'); // 161 A1
	texte = texte.replace(/¢/g,'&cent;'); // 162 A2
	texte = texte.replace(/£/g,'&pound;'); // 163 A3
	texte = texte.replace(/ /g,'&curren;'); // 164 A4
	texte = texte.replace(/¥/g,'&yen;'); // 165 A5
	texte = texte.replace(/¦/g,'&brvbar;'); // 166 A6
	texte = texte.replace(/§/g,'&sect;'); // 167 A7
	texte = texte.replace(/¨/g,'&uml;'); // 168 A8
	texte = texte.replace(/©/g,'&copy;'); // 169 A9
	texte = texte.replace(/ª/g,'&ordf;'); // 170 AA
	texte = texte.replace(/«/g,'&laquo;'); // 171 AB
	texte = texte.replace(/¬/g,'&not;'); // 172 AC
	texte = texte.replace(/­/g,'&shy;'); // 173 AD
	texte = texte.replace(/®/g,'&reg;'); // 174 AE
	texte = texte.replace(/¯/g,'&macr;'); // 175 AF
	texte = texte.replace(/°/g,'&deg;'); // 176 B0
	texte = texte.replace(/±/g,'&plusmn;'); // 177 B1
	texte = texte.replace(/²/g,'&sup2;'); // 178 B2
	texte = texte.replace(/³/g,'&sup3;'); // 179 B3
	texte = texte.replace(/´/g,'&acute;'); // 180 B4
	texte = texte.replace(/µ/g,'&micro;'); // 181 B5
	texte = texte.replace(/¶/g,'&para'); // 182 B6
	texte = texte.replace(/·/g,'&middot;'); // 183 B7
	texte = texte.replace(/¸/g,'&cedil;'); // 184 B8
	texte = texte.replace(/¹/g,'&sup1;'); // 185 B9
	texte = texte.replace(/º/g,'&ordm;'); // 186 BA
	texte = texte.replace(/»/g,'&raquo;'); // 187 BB
	texte = texte.replace(/¼/g,'&frac14;'); // 188 BC
	texte = texte.replace(/½/g,'&frac12;'); // 189 BD
	texte = texte.replace(/¾/g,'&frac34;'); // 190 BE
	texte = texte.replace(/¿/g,'&iquest;'); // 191 BF
	texte = texte.replace(/À/g,'&Agrave;'); // 192 C0
	texte = texte.replace(/Á/g,'&Aacute;'); // 193 C1
	texte = texte.replace(/Â/g,'&Acirc;'); // 194 C2
	texte = texte.replace(/Ã/g,'&Atilde;'); // 195 C3
	texte = texte.replace(/Ä/g,'&Auml;'); // 196 C4
	texte = texte.replace(/Å/g,'&Aring;'); // 197 C5
	texte = texte.replace(/Æ/g,'&AElig;'); // 198 C6
	texte = texte.replace(/Ç/g,'&Ccedil;'); // 199 C7
	texte = texte.replace(/È/g,'&Egrave;'); // 200 C8
	texte = texte.replace(/É/g,'&Eacute;'); // 201 C9
	texte = texte.replace(/Ê/g,'&Ecirc;'); // 202 CA
	texte = texte.replace(/Ë/g,'&Euml;'); // 203 CB
	texte = texte.replace(/Ì/g,'&Igrave;'); // 204 CC
	texte = texte.replace(/Í/g,'&Iacute;'); // 205 CD
	texte = texte.replace(/Î/g,'&Icirc;'); // 206 CE
	texte = texte.replace(/Ï/g,'&Iuml;'); // 207 CF
	texte = texte.replace(/Ð/g,'&ETH;'); // 208 D0
	texte = texte.replace(/Ñ/g,'&Ntilde;'); // 209 D1
	texte = texte.replace(/Ò/g,'&Ograve;'); // 210 D2
	texte = texte.replace(/Ó/g,'&Oacute;'); // 211 D3
	texte = texte.replace(/Ô/g,'&Ocirc;'); // 212 D4
	texte = texte.replace(/Õ/g,'&Otilde;'); // 213 D5
	texte = texte.replace(/Ö/g,'&Ouml;'); // 214 D6
	texte = texte.replace(/×/g,'&times;'); // 215 D7
	texte = texte.replace(/Ø/g,'&Oslash;'); // 216 D8
	texte = texte.replace(/Ù/g,'&Ugrave;'); // 217 D9
	texte = texte.replace(/Ú/g,'&Uacute;'); // 218 DA
	texte = texte.replace(/Û/g,'&Ucirc;'); // 219 DB
	texte = texte.replace(/Ü/g,'&Uuml;'); // 220 DC
	texte = texte.replace(/Ý/g,'&Yacute;'); // 221 DD
	texte = texte.replace(/Þ/g,'&THORN;'); // 222 DE
	texte = texte.replace(/ß/g,'&szlig;'); // 223 DF
	texte = texte.replace(/à/g,'&agrave;'); // 224 E0
	texte = texte.replace(/á/g,'&aacute;'); // 225 E1
	texte = texte.replace(/â/g,'&acirc;'); // 226 E2
	texte = texte.replace(/ã/g,'&atilde;'); // 227 E3
	texte = texte.replace(/ä/g,'&auml;'); // 228 E4
	texte = texte.replace(/å/g,'&aring;'); // 229 E5
	texte = texte.replace(/æ/g,'&aelig;'); // 230 E6
	texte = texte.replace(/ç/g,'&ccedil;'); // 231 E7
	texte = texte.replace(/è/g,'&egrave;'); // 232 E8
	texte = texte.replace(/é/g,'&eacute;'); // 233 E9
	texte = texte.replace(/ê/g,'&ecirc;'); // 234 EA
	texte = texte.replace(/ë/g,'&euml;'); // 235 EB
	texte = texte.replace(/ì/g,'&igrave;'); // 236 EC
	texte = texte.replace(/í/g,'&iacute;'); // 237 ED
	texte = texte.replace(/î/g,'&icirc;'); // 238 EE
	texte = texte.replace(/ï/g,'&iuml;'); // 239 EF
	texte = texte.replace(/ð/g,'&eth;'); // 240 F0
	texte = texte.replace(/ñ/g,'&ntilde;'); // 241 F1
	texte = texte.replace(/ò/g,'&ograve;'); // 242 F2
	texte = texte.replace(/ó/g,'&oacute;'); // 243 F3
	texte = texte.replace(/ô/g,'&ocirc;'); // 244 F4
	texte = texte.replace(/õ/g,'&otilde;'); // 245 F5
	texte = texte.replace(/ö/g,'&ouml;'); // 246 F6
	texte = texte.replace(/÷/g,'&divide;'); // 247 F7
	texte = texte.replace(/ø/g,'&oslash;'); // 248 F8
	texte = texte.replace(/ù/g,'&ugrave;'); // 249 F9
	texte = texte.replace(/ú/g,'&uacute;'); // 250 FA
	texte = texte.replace(/û/g,'&ucirc;'); // 251 FB
	texte = texte.replace(/ü/g,'&uuml;'); // 252 FC
	texte = texte.replace(/ý/g,'&yacute;'); // 253 FD
	texte = texte.replace(/þ/g,'&thorn;'); // 254 FE
	texte = texte.replace(/ÿ/g,'&yuml;'); // 255 FF
	return texte;
}
//Décode une chaîne
function html_entity_decode(texte) {
	texte = texte.replace(/&quot;/g,'"'); // 34 22
	texte = texte.replace(/&amp;/g,'&'); // 38 26	
	texte = texte.replace(/&#39;/g,"'"); // 39 27
	texte = texte.replace(/&lt;/g,'<'); // 60 3C
	texte = texte.replace(/&gt;/g,'>'); // 62 3E
	texte = texte.replace(/&circ;/g,'^'); // 94 5E
	texte = texte.replace(/&lsquo;/g,'‘'); // 145 91
	texte = texte.replace(/&rsquo;/g,'’'); // 146 92
	texte = texte.replace(/&ldquo;/g,'“'); // 147 93
	texte = texte.replace(/&rdquo;/g,'”'); // 148 94
	texte = texte.replace(/&bull;/g,'•'); // 149 95
	texte = texte.replace(/&ndash;/g,'–'); // 150 96
	texte = texte.replace(/&mdash;/g,'—'); // 151 97
	texte = texte.replace(/&tilde;/g,'˜'); // 152 98
	texte = texte.replace(/&trade;/g,'™'); // 153 99
	texte = texte.replace(/&scaron;/g,'š'); // 154 9A
	texte = texte.replace(/&rsaquo;/g,'›'); // 155 9B
	texte = texte.replace(/&oelig;/g,'œ'); // 156 9C
	texte = texte.replace(/&#357;/g,''); // 157 9D
	texte = texte.replace(/&#382;/g,'ž'); // 158 9E
	texte = texte.replace(/&Yuml;/g,'Ÿ'); // 159 9F
	texte = texte.replace(/&nbsp;/g,' '); // 160 A0
	texte = texte.replace(/&iexcl;/g,'¡'); // 161 A1
	texte = texte.replace(/&cent;/g,'¢'); // 162 A2
	texte = texte.replace(/&pound;/g,'£'); // 163 A3
	texte = texte.replace(/&curren;/g,' '); // 164 A4
	texte = texte.replace(/&yen;/g,'¥'); // 165 A5
	texte = texte.replace(/&brvbar;/g,'¦'); // 166 A6
	texte = texte.replace(/&sect;/g,'§'); // 167 A7
	texte = texte.replace(/&uml;/g,'¨'); // 168 A8
	texte = texte.replace(/&copy;/g,'©'); // 169 A9
	texte = texte.replace(/&ordf;/g,'ª'); // 170 AA
	texte = texte.replace(/&laquo;/g,'«'); // 171 AB
	texte = texte.replace(/&not;/g,'¬'); // 172 AC
	texte = texte.replace(/&shy;/g,'­'); // 173 AD
	texte = texte.replace(/&reg;/g,'®'); // 174 AE
	texte = texte.replace(/&macr;/g,'¯'); // 175 AF
	texte = texte.replace(/&deg;/g,'°'); // 176 B0
	texte = texte.replace(/&plusmn;/g,'±'); // 177 B1
	texte = texte.replace(/&sup2;/g,'²'); // 178 B2
	texte = texte.replace(/&sup3;/g,'³'); // 179 B3
	texte = texte.replace(/&acute;/g,'´'); // 180 B4
	texte = texte.replace(/&micro;/g,'µ'); // 181 B5
	texte = texte.replace(/&para/g,'¶'); // 182 B6
	texte = texte.replace(/&middot;/g,'·'); // 183 B7
	texte = texte.replace(/&cedil;/g,'¸'); // 184 B8
	texte = texte.replace(/&sup1;/g,'¹'); // 185 B9
	texte = texte.replace(/&ordm;/g,'º'); // 186 BA
	texte = texte.replace(/&raquo;/g,'»'); // 187 BB
	texte = texte.replace(/&frac14;/g,'¼'); // 188 BC
	texte = texte.replace(/&frac12;/g,'½'); // 189 BD
	texte = texte.replace(/&frac34;/g,'¾'); // 190 BE
	texte = texte.replace(/&iquest;/g,'¿'); // 191 BF
	texte = texte.replace(/&Agrave;/g,'À'); // 192 C0
	texte = texte.replace(/&Aacute;/g,'Á'); // 193 C1
	texte = texte.replace(/&Acirc;/g,'Â'); // 194 C2
	texte = texte.replace(/&Atilde;/g,'Ã'); // 195 C3
	texte = texte.replace(/&Auml;/g,'Ä'); // 196 C4
	texte = texte.replace(/&Aring;/g,'Å'); // 197 C5
	texte = texte.replace(/&AElig;/g,'Æ'); // 198 C6
	texte = texte.replace(/&Ccedil;/g,'Ç'); // 199 C7
	texte = texte.replace(/&Egrave;/g,'È'); // 200 C8
	texte = texte.replace(/&Eacute;/g,'É'); // 201 C9
	texte = texte.replace(/&Ecirc;/g,'Ê'); // 202 CA
	texte = texte.replace(/&Euml;/g,'Ë'); // 203 CB
	texte = texte.replace(/&Igrave;/g,'Ì'); // 204 CC
	texte = texte.replace(/&Iacute;/g,'Í'); // 205 CD
	texte = texte.replace(/&Icirc;/g,'Î'); // 206 CE
	texte = texte.replace(/&Iuml;/g,'Ï'); // 207 CF
	texte = texte.replace(/&ETH;/g,'Ð'); // 208 D0
	texte = texte.replace(/&Ntilde;/g,'Ñ'); // 209 D1
	texte = texte.replace(/&Ograve;/g,'Ò'); // 210 D2
	texte = texte.replace(/&Oacute;/g,'Ó'); // 211 D3
	texte = texte.replace(/&Ocirc;/g,'Ô'); // 212 D4
	texte = texte.replace(/&Otilde;/g,'Õ'); // 213 D5
	texte = texte.replace(/&Ouml;/g,'Ö'); // 214 D6
	texte = texte.replace(/&times;/g,'×'); // 215 D7
	texte = texte.replace(/&Oslash;/g,'Ø'); // 216 D8
	texte = texte.replace(/&Ugrave;/g,'Ù'); // 217 D9
	texte = texte.replace(/&Uacute;/g,'Ú'); // 218 DA
	texte = texte.replace(/&Ucirc;/g,'Û'); // 219 DB
	texte = texte.replace(/&Uuml;/g,'Ü'); // 220 DC
	texte = texte.replace(/&Yacute;/g,'Ý'); // 221 DD
	texte = texte.replace(/&THORN;/g,'Þ'); // 222 DE
	texte = texte.replace(/&szlig;/g,'ß'); // 223 DF
	texte = texte.replace(/&agrave;/g,'à'); // 224 E0
	texte = texte.replace(/&aacute;/g,'á'); // 225 E1
	texte = texte.replace(/&acirc;/g,'â'); // 226 E2
	texte = texte.replace(/&atilde;/g,'ã'); // 227 E3
	texte = texte.replace(/&auml;/g,'ä'); // 228 E4
	texte = texte.replace(/&aring;/g,'å'); // 229 E5
	texte = texte.replace(/&aelig;/g,'æ'); // 230 E6
	texte = texte.replace(/&ccedil;/g,'ç'); // 231 E7
	texte = texte.replace(/&egrave;/g,'è'); // 232 E8
	texte = texte.replace(/&eacute;/g,'é'); // 233 E9
	texte = texte.replace(/&ecirc;/g,'ê'); // 234 EA
	texte = texte.replace(/&euml;/g,'ë'); // 235 EB
	texte = texte.replace(/&igrave;/g,'ì'); // 236 EC
	texte = texte.replace(/&iacute;/g,'í'); // 237 ED
	texte = texte.replace(/&icirc;/g,'î'); // 238 EE
	texte = texte.replace(/&iuml;/g,'ï'); // 239 EF
	texte = texte.replace(/&eth;/g,'ð'); // 240 F0
	texte = texte.replace(/&ntilde;/g,'ñ'); // 241 F1
	texte = texte.replace(/&ograve;/g,'ò'); // 242 F2
	texte = texte.replace(/&oacute;/g,'ó'); // 243 F3
	texte = texte.replace(/&ocirc;/g,'ô'); // 244 F4
	texte = texte.replace(/&otilde;/g,'õ'); // 245 F5
	texte = texte.replace(/&ouml;/g,'ö'); // 246 F6
	texte = texte.replace(/&divide;/g,'÷'); // 247 F7
	texte = texte.replace(/&oslash;/g,'ø'); // 248 F8
	texte = texte.replace(/&ugrave;/g,'ù'); // 249 F9
	texte = texte.replace(/&uacute;/g,'ú'); // 250 FA
	texte = texte.replace(/&ucirc;/g,'û'); // 251 FB
	texte = texte.replace(/&uuml;/g,'ü'); // 252 FC
	texte = texte.replace(/&yacute;/g,'ý'); // 253 FD
	texte = texte.replace(/&thorn;/g,'þ'); // 254 FE
	texte = texte.replace(/&yuml;/g,'ÿ'); // 255 FF
	return texte;
}