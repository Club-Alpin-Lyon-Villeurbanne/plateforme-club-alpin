// débug utf8
$().ready(function() {


    // le choix de la commssion relance la page / confirmation necessaire
    $('#choix-commission a').bind('click', function(){
        if(!confirm("Attention, si vous changez cette valeur, cette page sera rechargée et vous perdrez les informations déja remplies. \nContinuer ?"))
            return false;
    });
    // le choix de la commssion relance la page / confirmation necessaire
    $('#choix-destination a').bind('click', function(){
        if(!confirm("Attention, si vous changez cette valeur, cette page sera rechargée et vous perdrez les informations déja remplies. \nContinuer ?"))
            return false;
    });
	
	
	// JQUERYUI
	
	// valeurs d'autocomplete pour les massifs
	var availableTags = [
		"Aravis",
		"Bauges",
		"Beaufortain",
		"Belledonne",
		"Bornes",
		"Chablais",
		"Chartreuse",
		"Devoluy",
		"Diois",
		"Ecrins",
		"Grandes Rousses",
		"Mercantour",
		"Mont Blanc",
		"Oisans",
		"Trièves",
		"Vanoise",
		"Vercors",
		"Vosges"
	]; 
    // CAF CLERMONT AUVERGNE
    /* 
	var availableTags = [
		"Cantal",
		"Cézallier",
		"Forez",
		"Jura",
		"Livradois",
		"Pyrénées",
		"Sancy",
		"Vosges"
	];
    */
	$("input[name=massif_evt]").autocomplete({
		source: availableTags,
		minLength: 0,
		delay: 100
	});
	// force autocomplete :
	$("input[name=massif_evt]").focus(function(){
		$(this).trigger(jQuery.Event("keydown"));
	});
	
	// datepicker
	$('input[name=tsp_evt_day], input[name=tsp_end_evt_day]').datepicker({ 
		dateFormat: "dd/mm/yy",
		firstDay: 1,
		dayNamesMin:["Di", "Lu", "Ma", "Me", "Je", "Ve", "Sa" ],
		monthNames:["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre" ]
	});

	// timepickers
	$('input[name=tsp_evt_hour]').timepicker({
		currentText:'Maintenant',
		closeText:'Ok',
		timeOnlyTitle:"Choisissez l'heure",
		timeText:'',
		hourText:'Heure',
		minuteText:'Minutes',
		stepMinute:'5',
		//
		hour:'08',
		minute:'00'
	});
	$('input[name=tsp_end_evt_hour]').timepicker({
		currentText:'Maintenant',
		closeText:'Ok',
		timeOnlyTitle:"Choisissez l'heure",
		timeText:'',
		hourText:'Heure',
		minuteText:'Minutes',
		stepMinute:'5',
		//
		hour:'19',
		minute:'00'
	});
	
});