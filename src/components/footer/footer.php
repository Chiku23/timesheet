<div class="floatingBtn" id="floatingTimerBtn" title="Toggle Timer">
    <div class="timerBtn">
        <div class="clock-face">
            <span class="clock-pin"></span>
            <span class="clock-hand hand-hour"></span>
            <span class="clock-hand hand-minute"></span>
        </div>
    </div>
</div>
<div class="timer-backdrop" id="timerBackdrop"></div>
<div class="timer-modal modal" id="timerModal">
    <div class="timer-modal-header">
        <h2>Timer</h2>
        <button type="button" class="modal-close" id="closeTimerModal">&times;</button>
    </div>
    <div class="time-box">
        <span id="hours">00</span><span class="colon">:</span>
        <span id="minutes">00</span><span class="colon">:</span>
        <span id="seconds">00</span>
    </div>
    <div class="action">
        <input type="text" id="task" placeholder="What are you working on?">
        <div class="btn-group">
            <button id="start" class="btn-start">Start</button>
            <button id="stop" class="btn-stop">Stop</button>
        </div>
    </div>
</div>
<footer>
    Made by Chiku | 2026
</footer>

<?php if (isset($jsFiles) && is_array($jsFiles)): ?>
    <?php foreach ($jsFiles as $js): ?>
        <script src="<?= htmlspecialchars($js) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>