// EASTER EGGS CODES
function launchEasterEgg1(){
	if(document.body.contentEditable=='inherit'){
		alert("Power : on");
		document.body.contentEditable = 'true';
		document.designMode = 'on'; 
	}
	else{
		alert("Power : off");
		document.body.contentEditable = 'false';
		document.designMode = 'off'; 
	}
	void 0
}





// SETTING KONAMI
var konamiBase='38384040373937396665';
var konamiStep='';

window.onkeyup = function (e) {
	konamiStep+=e.keyCode;
	if(konamiStep == konamiBase.substr(0,konamiStep.length)){
		// console.log('juskici on va vers le konami');
		// on a tapé tout le konami
		if(konamiStep == konamiBase){
			// easter egg à effectuer :
			setTimeout('launchEasterEgg1()', 0);
		}
	}
	else{
		// console.log('mal tapé');
		konamiStep='';
	}	
};