// ***********
// *** scroll up

$(window).scroll(function () {
	if ($(this).scrollTop() > 250) {
		$(".scrollup").fadeIn();
	} else {
		$(".scrollup").fadeOut();
	}
});
$('body').on("click", ".scrollup", function (e) {
	$("html, body").animate({ scrollTop: 0 }, 600);
	return false;
});
