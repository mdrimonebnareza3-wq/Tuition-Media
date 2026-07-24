<footer class="mt-5 py-4 bg-dark text-light">
    <div class="container d-flex flex-column flex-md-row justify-content-between gap-2">
        <div>
            <strong>Tuition Media</strong> — connecting tutors with learners.
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>

<style>
.tm-click-effect {
    background-color: #6f42c1 !important;
    color: #ffffff !important;
    animation: tm-vibrate 0.4s linear !important;
}

.tm-click-effect * {
    color: #ffffff !important;
}

@keyframes tm-vibrate {
    0% {
        transform: translateX(0);
    }

    20% {
        transform: translateX(-7px);
    }

    40% {
        transform: translateX(7px);
    }

    60% {
        transform: translateX(-5px);
    }

    80% {
        transform: translateX(5px);
    }

    100% {
        transform: translateX(0);
    }
}
</style>

<script>
document.addEventListener('pointerdown', function (event) {
    const item = event.target.closest(
        '.btn, .card, button, .nav-link, .dashboard-card'
    );

    if (!item) {
        return;
    }

    item.classList.remove('tm-click-effect');
    void item.offsetWidth;
    item.classList.add('tm-click-effect');

    setTimeout(function () {
        item.classList.remove('tm-click-effect');
    }, 450);
});
</script>

</body>
</html>