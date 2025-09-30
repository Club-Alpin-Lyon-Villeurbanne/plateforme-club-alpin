$().ready(function() {

    // Faux select (ex : page agenda)
    $('.faux-select a.up').css('z-index', 1).siblings('a').hide().css('z-index', 1);

    $('.faux-select a').bind('focus mouseenter', function(){
        $(this).css('z-index', 1500).siblings('a').fadeIn({duration:100, queue:false});
    });
    $('.faux-select').bind('mouseleave', function(){
        $(this).find('a.up').css('z-index', 1).siblings('a').fadeOut({duration:100, queue:false}).css('z-index', 1);
    });
});
