$(document).ready(function () {
    var $slider = $('#js-main-slider');
    var slideCount = $slider.find('.pogoSlider-slide').length;

    if (slideCount > 1) {
        // Si hay más de una foto, activamos el slider normal
        $slider.pogoSlider({
            autoplay: true,
            autoplayTimeout: 5000,
            displayProgess: true,
            preserveTargetSize: true,
            targetWidth: 1000,
            targetHeight: 300,
            responsive: true
        }).data('plugin_pogoSlider');
    } else {
        // Si solo hay una foto, forzamos que se vea y no inicializamos el slider
        $slider.find('.pogoSlider-slide').css({
            'opacity': '1',
            'display': 'block'
        });
        // Opcional: ajustar altura si se ve pequeño
        $slider.css('height', 'auto'); 
    }

    var transitionDemoOpts = {
        displayProgess: false,
        generateNav: false,
        generateButtons: false
    }
});