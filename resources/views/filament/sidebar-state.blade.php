<script data-filament-sidebar-state>
    (() => {
        let collapsedGroups;

        try {
            collapsedGroups = JSON.parse(localStorage.getItem('collapsedGroups'));
        } catch {
            collapsedGroups = null;
        }

        if (!Array.isArray(collapsedGroups)) {
            collapsedGroups = [];
            localStorage.setItem('collapsedGroups', '[]');
        }

        document.addEventListener('alpine:initializing', () => {
            const sidebar = window.Alpine.store('sidebar');

            if (sidebar && !Array.isArray(sidebar.collapsedGroups)) {
                sidebar.collapsedGroups = collapsedGroups;
            }
        });
    })();
</script>
