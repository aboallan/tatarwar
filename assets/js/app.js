document.addEventListener('DOMContentLoaded', () => {
    let toast = document.querySelector('.toast');

    if (!toast) {
        toast = document.createElement('div');
        toast.className = 'toast';
        document.body.appendChild(toast);
    }

    function showToast(message, type = 'success') {
        toast.textContent = message;
        toast.classList.remove('error', 'success');
        toast.classList.add('show', type);
        setTimeout(() => toast.classList.remove('show'), 3000);
    }

    document.querySelectorAll('.reminder-button').forEach(button => {
        button.addEventListener('click', async () => {
            const taskId = button.dataset.taskId;
            const targetSelector = button.dataset.target;
            const target = targetSelector ? document.querySelector(targetSelector) : null;

            button.disabled = true;
            try {
                const response = await fetch('send_reminder.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ taskId })
                });

                const result = await response.json();
                if (response.ok) {
                    showToast(result.message, 'success');
                    if (target && result.lastReminder) {
                        target.textContent = result.lastReminder;
                    }
                } else {
                    throw new Error(result.message || 'Unable to send reminder');
                }
            } catch (error) {
                showToast(error.message, 'error');
            } finally {
                button.disabled = false;
            }
        });
    });
});
