// Main JavaScript file for Neon Watchdog CMS

// Toggle service enabled/disabled
function toggleService(serviceId) {
    if (confirm('¿Estás seguro de cambiar el estado de este servicio?')) {
        fetch('api/services.php?id=' + serviceId, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ action: 'toggle' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', 'Estado actualizado correctamente');
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast('error', 'Error: ' + data.message);
            }
        })
        .catch(error => {
            showToast('error', 'Error al actualizar el servicio');
            console.error('Error:', error);
        });
    }
}

// Show toast notification
function showToast(type, message) {
    alert(message);
}

// Copy to clipboard helper
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        alert('Copiado al portapapeles');
    }).catch(err => {
        alert('Error al copiar');
        console.error('Error:', err);
    });
}
