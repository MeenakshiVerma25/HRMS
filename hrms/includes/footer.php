</div>

    <!-- jQuery FIRST -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

    <!-- Buttons dependencies -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

    <!-- Buttons -->
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    
    <script>

        $(document).on("click", ".has-submenu > a", function () {
            $(this).next(".submenu").slideToggle();
        });

        ( function () {
            const STORAGE_KEY = 'hrms-theme';
            const body = document.body;
            const html = document.documentElement;
            const btn = document.getElementById('themeToggle');

            function applyTheme(theme) {
                body.classList.remove('theme-dark', 'theme-light');
                body.classList.add('theme-' + theme);
                html.classList.remove('theme-dark', 'theme-light');
                html.classList.add('theme-' + theme);
                localStorage.setItem(STORAGE_KEY, theme);
            }

            const saved = localStorage.getItem(STORAGE_KEY) || 'dark';
            applyTheme(saved);

            btn.addEventListener('click', function (e) {
                const opt = e.target.closest('.tt-option');
                const current = body.classList.contains('theme-light') ? 'light' : 'dark';
                const next = opt ? opt.dataset.theme : (current === 'dark' ? 'light' : 'dark');
                if (next !== current) {
                    applyTheme(next);
                }
            });
        })();

        document.getElementById('menu-btn').addEventListener('click', function() {
            document.getElementById('menu').classList.toggle('collapsed');
            document.getElementById('interface').classList.toggle('expanded');
        });
    </script>

    <script>
        function animateCount(el) {
            const target = parseInt(el.dataset.target, 10);
            if (isNaN(target) || target === 0) { el.textContent = 0; return; }
            const duration = 900;
            const start = performance.now();
            (function step(now) {
                const p = Math.min((now - start) / duration, 1);
                const eased = 1 - Math.pow(1 - p, 3);
                el.textContent = Math.round(eased * target);
                if (p < 1) requestAnimationFrame(step);
                else el.textContent = target;
            })(performance.now());
        }
        setTimeout(() => document.querySelectorAll('.count-up').forEach(animateCount), 350);
    </script>
    
</body>
</html>