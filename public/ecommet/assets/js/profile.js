function showProfileDetails() {
    if (window.innerWidth < 768) {
        document.getElementById('sidebar-menu').classList.add('hidden');
        const details = document.getElementById('profile-details');
        details.classList.remove('hidden');
        details.classList.add('flex');
    }
}
