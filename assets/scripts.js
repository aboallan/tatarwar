document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search');
    const departmentFilter = document.getElementById('filter-department');
    const statusFilter = document.getElementById('filter-status');
    const priorityFilter = document.getElementById('filter-priority');
    const rows = Array.from(document.querySelectorAll('#tasks-table tbody tr'));

    const applyFilters = () => {
        const searchTerm = (searchInput?.value || '').toLowerCase();
        const department = departmentFilter?.value || 'all';
        const status = statusFilter?.value || 'all';
        const priority = priorityFilter?.value || 'all';

        rows.forEach(row => {
            const title = row.querySelector('td strong')?.textContent.toLowerCase() || '';
            const assignee = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
            const description = row.querySelector('.description')?.textContent.toLowerCase() || '';

            const matchesSearch = !searchTerm || title.includes(searchTerm) || assignee.includes(searchTerm) || description.includes(searchTerm);
            const matchesDepartment = department === 'all' || row.dataset.department === department;
            const matchesStatus = status === 'all' || row.dataset.status === status;
            const matchesPriority = priority === 'all' || row.dataset.priority === priority;

            row.style.display = (matchesSearch && matchesDepartment && matchesStatus && matchesPriority) ? '' : 'none';
        });
    };

    [searchInput, departmentFilter, statusFilter, priorityFilter].forEach(element => {
        element?.addEventListener('input', applyFilters);
        element?.addEventListener('change', applyFilters);
    });

    const dueDates = document.querySelectorAll('.due-date');
    const today = new Date();

    const formatDays = (value) => {
        const absolute = Math.abs(value);
        return `${absolute} day${absolute === 1 ? '' : 's'}`;
    };

    dueDates.forEach(dueDateElement => {
        const dueText = dueDateElement.getAttribute('datetime');
        if (!dueText) {
            return;
        }

        const dueDate = new Date(dueText + 'T00:00:00');
        const diffTime = dueDate.getTime() - today.setHours(0, 0, 0, 0);
        const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));

        const indicator = dueDateElement.nextElementSibling;
        if (!(indicator instanceof HTMLElement)) {
            return;
        }

        indicator.textContent = '';
        indicator.classList.remove('overdue', 'soon', 'safe');

        if (Number.isNaN(diffDays)) {
            return;
        }

        if (diffDays < 0) {
            indicator.textContent = `Overdue by ${formatDays(diffDays)}`;
            indicator.classList.add('overdue');
        } else if (diffDays === 0) {
            indicator.textContent = 'Due today';
            indicator.classList.add('soon');
        } else if (diffDays <= 3) {
            indicator.textContent = `Due in ${formatDays(diffDays)}`;
            indicator.classList.add('soon');
        } else {
            indicator.textContent = `Due in ${formatDays(diffDays)}`;
            indicator.classList.add('safe');
        }
    });

    applyFilters();
});
