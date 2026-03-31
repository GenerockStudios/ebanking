<?php
/**
 * footer.php
 * Ferme le conteneur du contenu et les balises HTML.
 * ⚠️ Toast system injecté ici — disponible dans toutes les vues.
 */
?>

    </div><!-- /.content -->

    <!-- ─── FOOTER ───────────────────────────────────────────── -->
    <footer style="
        text-align: center;
        padding: 14px max(16px, env(safe-area-inset-right)) calc(14px + env(safe-area-inset-bottom)) max(16px, env(safe-area-inset-left));
        font-size: 0.8em;
        color: #888;
        border-top: 1px solid #f0f0f0;
        margin-top: 16px;
    " class="no-print">
        <p>&copy; <?= date('Y') ?> <?= APP_NAME ?>. Système Bancaire Interne. Version 1.0.</p>
    </footer>

    <!-- ─── TOAST CONTAINER (notifications flottantes) ──────── -->
    <div id="toast-container" role="region" aria-live="polite" aria-atomic="false" aria-label="Notifications"></div>

    <!-- ─── TOAST SYSTEM GLOBAL ──────────────────────────────── -->
    <script>
    /**
     * showToast(message, type, duration)
     * @param {string} message  - HTML ou texte de la notification
     * @param {string} type     - 'success' | 'error' | 'info'
     * @param {number} duration - Durée en ms (défaut : 4500)
     */
    function showToast(message, type, duration) {
        type     = type     || 'info';
        duration = duration || 4500;

        var icons = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
        var container = document.getElementById('toast-container');
        if (!container) return;

        var toast = document.createElement('div');
        toast.className = 'toast ' + type;
        toast.setAttribute('role', 'alert');
        toast.innerHTML =
            '<span aria-hidden="true" style="font-size:1.1em;">' + (icons[type] || 'ℹ️') + '</span>' +
            '<span style="flex:1;">' + message + '</span>' +
            '<button onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;font-size:1rem;opacity:.6;padding:0 0 0 8px;" aria-label="Fermer">&times;</button>';

        container.appendChild(toast);

        // Auto-dismiss
        setTimeout(function() {
            if (!toast.parentElement) return;
            toast.classList.add('fade-out');
            toast.addEventListener('animationend', function() { toast.remove(); }, { once: true });
            // Fallback si animation non supportée
            setTimeout(function() { if (toast.parentElement) toast.remove(); }, 400);
        }, duration);
    }

    console.log("E-Banking Pro — chargé ✔");
    </script>

</body>
</html>