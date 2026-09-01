<?php

declare(strict_types=1);

/**
 * Plugin Name: FORMEDIL — Configurazione ambiente
 * Description: Impostazioni specifiche di questo server (email, URL frontend) per il plugin formedil-moduli. Sta in mu-plugins: si attiva da solo e non si disattiva per errore.
 * Version: 1.0.0
 *
 * DOVE VA QUESTO FILE
 * -------------------
 * Caricare in:  /cms/wp-content/mu-plugins/formedil-config.php
 * Se la cartella "mu-plugins" non esiste, crearla (nome esatto, minuscolo).
 * Non serve attivare nulla da wp-admin: i "must-use plugin" sono sempre attivi.
 *
 * PERCHÉ SEPARATO DAL PLUGIN
 * --------------------------
 * Il plugin formedil-moduli è il codice dell'applicazione: si aggiorna e si
 * sovrascrive. Questo file contiene solo i valori di QUESTO server, quindi
 * sopravvive agli aggiornamenti del plugin.
 *
 * RAPPORTO CON LA PAGINA "IMPOSTAZIONI EMAIL"
 * -------------------------------------------
 * Dalla versione S14 gli stessi valori si possono impostare da wp-admin →
 * FORMEDIL → Impostazioni email. Quando una costante qui sotto è valorizzata,
 * VINCE su quanto scritto nel pannello e il campo corrispondente appare in
 * sola lettura: così non ci sono due fonti di verità in disaccordo.
 *
 * Per lasciare la gestione alla segreteria, svuota la costante (stringa vuota)
 * e il campo torna modificabile da wp-admin.
 */

// Blocca l'accesso diretto al file.
if (!defined('ABSPATH')) {
    exit;
}

/* -------------------------------------------------------------------------
 * 1. VALORI DA CONFIGURARE
 *
 * Modificare SOLO le tre righe qui sotto. Il resto del file non va toccato.
 * ---------------------------------------------------------------------- */

/**
 * Indirizzo mittente delle email automatiche.
 *
 * È la stessa casella già usata dal sito storico in ASP per le notifiche
 * automatiche, quindi con reputazione e record DNS del dominio già collaudati.
 *
 * IMPORTANTE: deve restare identica all'utenza configurata in WP Mail SMTP.
 * Aruba rifiuta le email il cui mittente non corrisponde all'utenza SMTP
 * autenticata, quindi un valore diverso qui = email non consegnate.
 */
define('FORMEDIL_MAIL_FROM', 'no-reply@formedillecce.it');

/**
 * Nome visualizzato come mittente nella casella di chi riceve.
 */
define('FORMEDIL_MAIL_FROM_NAME', 'FORMEDIL Lecce');

/**
 * Caselle interne FORMEDIL che ricevono in Cc ogni notifica (pratica inserita
 * e documenti ricevuti). Sono anche i destinatari quando il richiedente non ha
 * indicato un'email.
 *
 * Più indirizzi separati da virgola. Per aggiungerne o toglierne uno basta
 * modificare questa riga: non serve toccare il codice del plugin.
 *
 * Lasciare vuoto per usare l'indirizzo amministratore di WordPress
 * (Impostazioni → Generali → Indirizzo email).
 */
define('FORMEDIL_ADMIN_EMAIL', 'responsabileareasicurezza@formedillecce.it, direttore@formedillecce.it');

/* -------------------------------------------------------------------------
 * 2. APPLICAZIONE DEI FILTRI
 *
 * Ogni filtro viene registrato solo se il valore corrispondente è compilato,
 * così un campo lasciato vuoto ricade sul default del plugin invece di
 * impostare una stringa vuota.
 * ---------------------------------------------------------------------- */

if (FORMEDIL_MAIL_FROM !== '') {
    add_filter('formedil_mail_from', static function (): string {
        return FORMEDIL_MAIL_FROM;
    });

    // Allinea anche il mittente globale di WordPress (email di sistema, reset
    // password): evita che partano da wordpress@dominio, che Aruba scarta.
    add_filter('wp_mail_from', static function (string $from): string {
        return FORMEDIL_MAIL_FROM;
    });
}

if (FORMEDIL_MAIL_FROM_NAME !== '') {
    add_filter('formedil_mail_from_name', static function (): string {
        return FORMEDIL_MAIL_FROM_NAME;
    });

    add_filter('wp_mail_from_name', static function (string $name): string {
        return FORMEDIL_MAIL_FROM_NAME;
    });
}

if (FORMEDIL_ADMIN_EMAIL !== '') {
    add_filter('formedil_admin_email', static function (): string {
        return FORMEDIL_ADMIN_EMAIL;
    });
}

/* -------------------------------------------------------------------------
 * 3. URL DEL FRONTEND (già corretto nel plugin, qui solo come override)
 *
 * Il plugin punta di default a https://gestionale.formedillecce.it, che è la
 * radice dove è pubblicata la SPA React. Questo blocco serve solo se il
 * dominio cambia: in quel caso decommentare e aggiornare il valore, senza
 * toccare il codice del plugin.
 * ---------------------------------------------------------------------- */

// add_filter('formedil_frontend_base_url', static function (): string {
//     return 'https://gestionale.formedillecce.it';
// });

/* -------------------------------------------------------------------------
 * 4. DIAGNOSTICA EMAIL (opzionale, da tenere spento in condizioni normali)
 *
 * Decommentare per scrivere in debug.log ogni tentativo di invio fallito con
 * il motivo restituito da PHPMailer. Utile solo mentre si configura SMTP.
 * Richiede WP_DEBUG e WP_DEBUG_LOG attivi in wp-config.php.
 * ---------------------------------------------------------------------- */

// add_action('wp_mail_failed', static function ($error): void {
//     if (is_wp_error($error)) {
//         error_log('[formedil] invio email fallito: ' . $error->get_error_message());
//     }
// });
