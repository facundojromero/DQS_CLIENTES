$(document).ready(function () {
    var $slider = $('#js-main-slider');
    var $slides = $slider.find('.pogoSlider-slide');
    var $counter = $slider.find('.hero-slide-counter');
    var slideCount = $slides.length;
    var prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function updateSliderState(currentIndex) {
        var safeIndex = Math.max(0, Math.min(currentIndex || 0, slideCount - 1));
        var currentNumber = safeIndex + 1;
        var currentLabel = (currentNumber < 10 ? '0' : '') + currentNumber;

        $slides.attr('aria-hidden', 'true').eq(safeIndex).attr('aria-hidden', 'false');
        $counter
            .attr('aria-label', 'Foto ' + (safeIndex + 1) + ' de ' + slideCount)
            .find('.hero-slide-counter-current')
            .text(currentLabel);
    }

    if (slideCount === 0) {
        return;
    }

    if (slideCount > 1) {
        if (prefersReducedMotion) {
            $slides.attr('data-duration', '1');
        }

        var sliderInstance = $slider.pogoSlider({
            autoplay: !prefersReducedMotion,
            autoplayTimeout: 6500,
            displayProgess: false,
            generateNav: false,
            pauseOnHover: true,
            preserveTargetSize: false,
            responsive: true,
            slideTransition: 'fade',
            slideTransitionDuration: prefersReducedMotion ? 1 : 800,
            targetWidth: 1920,
            targetHeight: 1000,
            onSlideStart: function () {
                updateSliderState(this.currentSlideIndex);
            }
        }).data('plugin_pogoSlider');

        $slider.find('.pogoSlider-dir-btn').attr('type', 'button');
        $slider.find('.pogoSlider-dir-btn--prev').attr('aria-label', 'Ver foto anterior');
        $slider.find('.pogoSlider-dir-btn--next').attr('aria-label', 'Ver foto siguiente');

        $slider.on('focusin', function () {
            sliderInstance.pause();
        });

        $slider.on('focusout', function () {
            if (!prefersReducedMotion) {
                sliderInstance.resume();
            }
        });

        updateSliderState(sliderInstance.currentSlideIndex);
    } else {
        $slides.css({
            'opacity': '1',
            'display': 'block'
        });
        updateSliderState(0);
    }
});
