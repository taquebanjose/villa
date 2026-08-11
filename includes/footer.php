<?php // includes/footer.php ?>
    <footer style="padding: 40px; text-align: center; color: var(--text-sub); font-size: 0.8rem; border-top: 1px solid var(--border-subtle); width: 100%; box-sizing: border-box;">
        <p>&copy; <?= date('Y') ?> Villa Marciana Resort. Designed for Excellence.</p>
    </footer>

    <script>
        // Sync Theme UI State
        const activeTheme = document.documentElement.getAttribute('data-theme') || 'light';
        updateThemeUI(activeTheme);

        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeUI(newTheme);
        }

        function updateThemeUI(theme) {
            const themeIcon = document.getElementById('themeIcon');
            const themeText = document.getElementById('themeText');
            if (themeIcon && themeText) {
                themeIcon.innerText = theme === 'dark' ? '☀️' : '🌙';
                themeText.innerText = theme === 'dark' ? 'Light' : 'Dark';
            }
        }

        function togglePremiumMenu(event) {
            event.stopPropagation();
            const menu = document.getElementById('userMenu');
            if (menu.style.display === 'flex') { 
                closePremiumMenu(); 
            } else {
                menu.style.display = 'flex';
                requestAnimationFrame(() => { menu.classList.add('active'); });
            }
        }

        function closePremiumMenu() {
            const menu = document.getElementById('userMenu');
            if (menu) {
                menu.classList.remove('active');
                setTimeout(() => { if (!menu.classList.contains('active')) { menu.style.display = 'none'; } }, 500);
            }
        }

        window.onclick = function(event) { 
            if (!event.target.closest('.account-dropdown')) { closePremiumMenu(); } 
        }
    </script>
</body>
</html>