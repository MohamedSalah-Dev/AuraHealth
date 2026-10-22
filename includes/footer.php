<script>
(() => {
    const root = document.documentElement;
    const languageButtons = document.querySelectorAll('[data-language]');
    const themeButton = document.querySelector('.portal-theme-switch');
    const setLanguage = language => {
        root.lang = language;
        root.dir = language === 'ar' ? 'rtl' : 'ltr';
        document.querySelectorAll('[data-en][data-ar]').forEach(element => {
            element.textContent = element.dataset[language];
        });
        if (window.applyPortalLanguage) window.applyPortalLanguage(language);
        languageButtons.forEach(button => button.setAttribute('aria-pressed', String(button.dataset.language === language)));
        localStorage.setItem('medibook-language', language);
    };
    const setTheme = dark => {
        root.dataset.theme = dark ? 'dark' : 'light';
        themeButton.setAttribute('aria-pressed', String(dark));
        themeButton.setAttribute('aria-label', root.lang === 'ar' ? (dark ? 'التبديل إلى الوضع الفاتح' : 'التبديل إلى الوضع الداكن') : (dark ? 'Switch to light mode' : 'Switch to dark mode'));
        themeButton.querySelector('.theme-label').textContent = dark ? (root.lang === 'ar' ? 'فاتح' : 'Light') : (root.lang === 'ar' ? 'داكن' : 'Dark');
        themeButton.querySelector('.theme-icon').textContent = dark ? String.fromCharCode(9728) : String.fromCharCode(9790);
        localStorage.setItem('medibook-theme', dark ? 'dark' : 'light');
    };
    languageButtons.forEach(button => button.addEventListener('click', () => setLanguage(button.dataset.language)));
    themeButton.addEventListener('click', () => setTheme(root.dataset.theme !== 'dark'));
    setLanguage(localStorage.getItem('medibook-language') || 'en');
    setTheme(localStorage.getItem('medibook-theme') === 'dark');
})();
</script>
<script src="<?= $base ?>assets/js/portal-i18n.js?v=20261022-arabic"></script>
</div></main></div></body></html>
