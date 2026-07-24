document.querySelectorAll('[data-confirm]').forEach(function (element) {
    element.addEventListener('click', function (event) {
        const message = element.dataset.confirm || 'Are you sure?';

        if (!confirm(message)) {
            event.preventDefault();
        }
    });
});

setTimeout(function () {
    document.querySelectorAll('.alert').forEach(function (alertElement) {
        try {
            bootstrap.Alert.getOrCreateInstance(alertElement).close();
        } catch (error) {
            // Bootstrap may not be available while working offline.
        }
    });
}, 5000);
