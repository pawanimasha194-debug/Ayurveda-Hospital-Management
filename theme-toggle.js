// theme-toggle.js
(function() {
    // 1. පේජ් එක ලෝඩ් වෙන්න කලින්ම සේව් වෙලා තියෙන තීම් එක දාන්න (Flash වෙන එක නවත්තන්න)
    const savedTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);

    // 2. පේජ් එක ලෝඩ් වුණාම බට්න් එක ඔටෝම හැදිලා Topbar එකට එකතු වෙන්න හදන්න
    document.addEventListener('DOMContentLoaded', () => {
        // .user-info එක තියෙන තැන හොයාගන්න
        const userInfo = document.querySelector('.user-info');
        
        // බට්න් එක හදන්න
        const themeToggleBtn = document.createElement('button');
        themeToggleBtn.className = 'theme-toggle-btn';
        themeToggleBtn.id = 'themeToggle';
        themeToggleBtn.title = 'Toggle Light/Dark Theme';
        themeToggleBtn.textContent = savedTheme === 'dark' ? '☀️' : '🌙';

        // Logout බට්න් එකට ඉස්සරහින් මේක එකතු කරන්න (Logout එක නැත්නම් userInfo එකේ අන්තිමට)
        if (userInfo) {
            const logoutBtn = userInfo.querySelector('a[href="logout.php"]');
            if (logoutBtn) {
                userInfo.insertBefore(themeToggleBtn, logoutBtn);
            } else {
                userInfo.appendChild(themeToggleBtn);
            }

            // බට්න් එක ඔබද්දී තීම් එක මාරු කරන්න
            themeToggleBtn.addEventListener('click', () => {
                let currentTheme = document.documentElement.getAttribute('data-theme');
                if (currentTheme === 'dark') {
                    document.documentElement.setAttribute('data-theme', 'light');
                    localStorage.setItem('theme', 'light');
                    themeToggleBtn.textContent = '🌙';
                } else {
                    document.documentElement.setAttribute('data-theme', 'dark');
                    localStorage.setItem('theme', 'dark');
                    themeToggleBtn.textContent = '☀️';
                }
            });
        }
    });
})();