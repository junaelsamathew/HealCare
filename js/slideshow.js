document.addEventListener('DOMContentLoaded', function () {
    const slides = document.querySelectorAll('.hero-slide');
    const intervalTime = 3000; // 3 seconds
    let currentSlide = 0;

    if (slides.length === 0) return;

    function nextSlide() {
        // Remove active class from current
        slides[currentSlide].classList.remove('active');

        // Calculate next slide index
        currentSlide = (currentSlide + 1) % slides.length;

        // Add active class to next
        slides[currentSlide].classList.add('active');
    }

    setInterval(nextSlide, intervalTime);
});
