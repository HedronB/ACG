<?php
/**
 * Genera el HTML del burger button para incluir en los headers.
 * Requiere que sidebar.php esté incluido antes del cierre de </body>.
 */
function burgerBtn(): string {
    return '<button class="burger-btn" id="burgerBtn" onclick="abrirSidebar()" aria-label="Menú">
        <span></span><span></span><span></span>
    </button>';
}

/**
 * Incluye el partial del sidebar al final de cada página.
 */
function includeSidebar(): void {
    include BASE_PATH . '/app/views/partials/sidebar.php';
}
