// ********* Mon CMS
function mailThis(name,domain,ext,attributes,anchor){
	// console.log('mailthos ('+name+','+domain+','+ext+','+attributes+','+anchor+')');
	var mail=name+'@'+domain+'.'+ext;
	if(typeof(attributes)=="undefined")	attributes=' ';
	if(typeof(anchor)=="undefined")		anchor=mail;
	if(!anchor)							anchor=mail;
	// debug
	attributes = $('<textarea/>').html(attributes).val();
	// retour
	// document.write('<a href="mailto:'+mail+'" '+attributes+'>'+anchor+'</a>');
	$('.mailthisanchor:first').after('<a href="mailto:'+mail+'" '+attributes+'>'+anchor+'</a>').remove();
}

// ********* Cookies
function createCookie(name,value,days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}

function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}

function eraseCookie(name) {
	createCookie(name,"",-1);
	return true;
}


// dimensionn les iframes visées en fonction de leur contenu
function actu_iframe(){
	//alert('actu_iframe');
	$('iframe.resize').each(function(){
		if(this.contentDocument.body){
			if(this.style.display=='none') var none=true;
			else var none=false;
			
			if(none) this.style.display='block';
			this.style.height = this.contentDocument.body.offsetHeight  + 20 +"px";
			if(none) this.style.display='none';
		}
	});
}


/////////// Popups
var newWin = null;
function closeWin(){
	if (newWin != null){
		if(!newWin.closed)
		newWin.close();
	}
}
function popUp(strURL,strType,strHeight,strWidth) {
closeWin();
var strOptions="";
if (strType=="console") strOptions="resizable,height="+strHeight+",width="+strWidth;
if (strType=="fixed") strOptions="status,height="+strHeight+",width="+strWidth;
if (strType=="elastic") strOptions="toolbar,menubar,scrollbars,resizable,location,height="+strHeight+",width="+strWidth;
newWin = window.open(strURL, 'newWin', strOptions);
newWin.focus();
} 