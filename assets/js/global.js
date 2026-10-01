// Show/Hide the Timer Modal on the floating Timer Button click
document.addEventListener('DOMContentLoaded', () => {
    const themeToggleBtn = document.getElementById('themeToggleBtn');

    // Theme Toggle (Dark / Light) with localStorage persistence
    const savedTheme = localStorage.getItem('app-theme');
    if (savedTheme === 'light') {
        document.body.classList.remove('theme-dark');
    } else if (savedTheme === 'dark') {
        document.body.classList.add('theme-dark');
    }

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const isDark = document.body.classList.toggle('theme-dark');
            localStorage.setItem('app-theme', isDark ? 'dark' : 'light');
        });
    }

    const floatingBtn = document.querySelector('.floatingBtn');
    const timerBtn = document.querySelector('.timerBtn');
    const timerModal = document.getElementById('timerModal');
    const timerBackdrop = document.getElementById('timerBackdrop');
    const closeBtn = document.getElementById('closeTimerModal');

    // Toggle modal visibility
    function openModal() {
        if (!timerModal) return;
        timerModal.classList.add('open');
        if (timerBackdrop) timerBackdrop.classList.add('open');
    }

    function closeModal() {
        if (!timerModal) return;
        timerModal.classList.remove('open');
        if (timerBackdrop) timerBackdrop.classList.remove('open');
    }

    if (floatingBtn) {
        floatingBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (timerModal.classList.contains('open')) {
                closeModal();
            } else {
                openModal();
            }
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeModal);
    }

    if (timerBackdrop) {
        timerBackdrop.addEventListener('click', closeModal);
    }

    // Close on Escape key press
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && timerModal && timerModal.classList.contains('open')) {
            closeModal();
        }
    });

    // Timer functionality
    const hoursSpan = document.getElementById('hours');
    const minutesSpan = document.getElementById('minutes');
    const secondsSpan = document.getElementById('seconds');
    const startBtn = document.getElementById('start');
    const stopBtn = document.getElementById('stop');

    let timerInterval = null;
    let totalSeconds = 0;

    function formatTime(val) {
        return String(val).padStart(2, '0');
    }

    function updateDisplay() {
        const hrs = Math.floor(totalSeconds / 3600);
        const mins = Math.floor((totalSeconds % 3600) / 60);
        const secs = totalSeconds % 60;

        if (hoursSpan) hoursSpan.textContent = formatTime(hrs);
        if (minutesSpan) minutesSpan.textContent = formatTime(mins);
        if (secondsSpan) secondsSpan.textContent = formatTime(secs);
    }

    if (startBtn) {
        startBtn.addEventListener('click', () => {
            if (timerInterval) return; // Already running

            timerInterval = setInterval(() => {
                totalSeconds++;
                updateDisplay();
            }, 1000);

            if (timerBtn) {
                timerBtn.classList.add('active'); // Triggers wiggle animation
            }
        });
    }

    if (stopBtn) {
        stopBtn.addEventListener('click', () => {
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }

            if (timerBtn) {
                timerBtn.classList.remove('active'); // Stops animation
            }
        });
    }
});
