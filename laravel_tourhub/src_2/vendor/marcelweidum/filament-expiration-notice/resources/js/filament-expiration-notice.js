document.addEventListener('livewire:init', () => {
    let isShowingSessionExpiredModal = false;

    Livewire.hook('request', ({ fail }) => {
        fail(({ status, preventDefault }) => {
            if (status !== 419) {
                return;
            }

            preventDefault();

            if (isShowingSessionExpiredModal) {
                return;
            }

            isShowingSessionExpiredModal = true;

            Livewire.dispatch('open-modal', { id: 'session-expired' });
        });
    });
});
