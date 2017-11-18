$(document).ready(function() {
    
    var logo = $('.site-title-link');
    var menu = $('.main-menu-list');

    makeMobileMenu(menu, logo);

    $('.close-alert').click(function(){
        $(this).closest('.section-alert').remove();
    });
    
    $(window).resize(function() {
        makeMobileMenu(menu, logo);
    });
    
});

// Show/hide menu behavior
// e    -> event (click)
// menu -> menu object
// logo -> logo object
function bindMobileMenu(e, menu, logo) {
    e.preventDefault();
    logo.blur();
    menu.toggleClass('menu-hidden');
    console.log('Toggled class once');
}

// Menu behavior
// menu -> menu object
// logo -> logo object
function makeMobileMenu(menu, logo) { 
    // Add this class
    // If on mobile, the above class
    // Will make menu invisible
    menu.addClass('menu-hidden');
    console.log('Added class once');

    // Check if it is invisible
    if (menu.css('display') === 'none') {
        // Bind show/hide behavior to the menu click
        logo.unbind().on('click', function(e) {
            bindMobileMenu(e, menu, logo);
        });
    }
    // If not on mobile, unbind the click sniffer
    else {
        logo.off('click');
        console.log('Click hijack is off')
    }
}

// Collapsable form
function collapseForm() {
    $('.form-collapse').find('.form-collapse-content').hide();
    $('.form-collapse legend').click(function () {
        $(this).parent().find('.form-collapse-content').slideToggle();
    })
}