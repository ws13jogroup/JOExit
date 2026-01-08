# JOb Exit Plugin - Documentazione Tecnica

Benvenuto nella documentazione tecnica di **JOb Exit Plugin**, un plugin per WordPress che implementa un'applicazione stile "Tinder" per la gestione e il voto dei dipendenti all'interno di un'azienda.

## 📝 Descrizione
JOb Exit è un plugin interattivo che permette ai dipendenti di votare i propri colleghi attraverso un'interfaccia di "swipe". Il sistema include meccanismi di gamification come punteggi, classifiche globali (Leaderboard) e premi per chi indovina correttamente l'uscita di un collega dall'azienda.

---

## 🚀 Funzionalità Principali

### 🌐 Interfaccia Pubblica
- **Swipe Card System**: Interfaccia stile Tinder per votare i colleghi (EXIT o NOPE).
- **Sistema di Punti**: I dipendenti accumulano "punti uscita" basati sui voti ricevuti.
- **Gamification**:
    - **Punti Esperienza (EXP)**: Gli utenti guadagnano EXP quando un dipendente che hanno votato come "EXIT" lascia effettivamente l'azienda.
    - **Leaderboard Collaboratori**: Classifica degli utenti più attivi e precisi.
    - **Classifica Dipendenti**: Classifica dei dipendenti basata sui voti ricevuti.
- **Gestione Account**: Registrazione, login, modifica profilo e caricamento avatar personalizzati.
- **Notifiche**: Sistema di alert per nuove comunicazioni aziendali.
- **Dark Mode**: Supporto per il tema scuro personalizzabile.

### ⚙️ Pannello Amministrativo
- **Gestione Dipendenti**: Aggiunta, modifica, eliminazione e gestione dello stato (Attivo/Uscito).
- **Controllo Voti**: Visualizzazione dei voti espressi dagli utenti e possibilità di reset.
- **Gestione EXP**: Monitoraggio e modifica manuale dei punti esperienza degli utenti.
- **Configurazione Tema**: Personalizzazione del colore primario del plugin.
- **Notifiche & Changelog**: Creazione di notifiche di sistema e gestione del registro delle modifiche.
- **Gestione Cooldown**: Controllo dei limiti di voto e reset dei timer.

---

## 🛠 Architettura Tecnica

Il plugin segue una struttura standard di WordPress organizzata in classi:

- `Jo_Exit`: La classe principale che inizializza il plugin, definisce le costanti e carica le dipendenze.
- `Jo_Exit_Loader`: Gestisce la registrazione di tutti gli hook (action e filter) tra il nucleo del plugin e WordPress.
- `Jo_Exit_Admin`: Contiene tutte le funzionalità specifiche per l'area di amministrazione.
- `Jo_Exit_Public`: Gestisce l'interfaccia utente, gli shortcode e le chiamate AJAX pubbliche.
- `Jo_Exit_DB`: Classe astratta per tutte le operazioni sul database (CRUD).
- `Jo_Exit_User`: Gestisce la logica specifica dell'utente (voti, limiti, EXP).
- `Jo_Exit_Notifications`: Sistema di gestione delle comunicazioni interne.

### 🗄 Struttura Database
Il plugin crea le seguenti tabelle personalizzate all'attivazione:

1.  `wp_jo_exit_employees`: Anagrafica dei dipendenti (nome, ruolo, foto, score, status).
2.  `wp_jo_exit_votes`: Registro dei voti effettuati dagli utenti.
3.  `wp_jo_exit_player_scores`: Punti accumulati dagli utenti (giocatori).
4.  `wp_jo_exit_notifications`: Messaggi di sistema.
5.  `wp_jo_exit_notification_reads`: Tracciamento delle notifiche lette.
6.  `wp_jo_exit_changelog`: Storico degli aggiornamenti visualizzabile nell'app.

---

## 🔢 Logiche di Business

### Sistema di Voto (FIFO)
Per evitare abusi, il sistema limita il numero di voti "EXIT" contemporanei che un utente può esprimere a un massimo di **5**. 
- Quando un utente tenta di votare un sesto "EXIT", il voto più vecchio (basato sul timestamp) viene sostituito dal nuovo (logica First-In-First-Out).
- Se un utente cambia un voto da "EXIT" a "NOPE", i punti precedentemente assegnati vengono azzerati.

### Cooldown e Sessioni
Il plugin implementa un sistema di **Cooldown di 8 ore**:
- Una volta raggiunti i 5 voti "EXIT" o dopo aver votato tutti i dipendenti disponibili, l'utente può essere limitato temporalmente prima di poter votare di nuovo.
- Il timer di cooldown è impostato a 28.800 secondi (8 ore).
- Lo stato del cooldown è sincronizzato tramite AJAX e salvato nei metadati dell'utente (`jo_exit_last_vote_timestamp`).

### Calcolo del Punteggio Finale
Quando un dipendente viene marcato come **Uscito (Exit)**:
1.  Viene calcolato il suo punteggio finale: `Punti Ricevuti + Anni di Anzianità`.
2.  Tutti gli utenti che avevano un voto attivo di tipo "EXIT" per quel dipendente ricevono punti EXP.
3.  Il dipendente viene spostato nella sezione "Exited".

---

## 🔌 Shortcodes
Puoi inserire le diverse sezioni del plugin nelle tue pagine utilizzando i seguenti shortcode:

- `[job_exit_app]`: Carica l'applicazione completa (Home, Classifiche, Account).
- `[job_exit_home]`: Visualizza solo la schermata di swipe.
- `[job_exit_leaderboard]`: Mostra la classifica dei punti uscita dei dipendenti.
- `[job_exit_global_leaderboard]`: Mostra la classifica EXP degli utenti.
- `[job_exit_exited]`: Visualizza l'elenco dei dipendenti che hanno lasciato l'azienda.
- `[job_exit_account]`: Area profilo utente.

---

## 📦 Installazione
1.  Carica la cartella `job-exit-plugin` nella directory `/wp-content/plugins/`.
2.  Attiva il plugin tramite il menu 'Plugin' di WordPress.
3.  Crea una nuova pagina e inserisci lo shortcode `[job_exit_app]`.
4.  Configura i primi dipendenti dal menu **JOb Exit** nella barra laterale dell'admin.

## 📝 Requisiti
- PHP 7.4 o superiore.
- WordPress 5.6 o superiore.
- Hammer.js (incluso, per la gestione dei gesti touch).

---
