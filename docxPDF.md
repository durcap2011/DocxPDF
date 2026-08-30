# Convertitore docx PDF

## Descrizione
Quante volte, da programmatori abbiamo la necessità di generare un file pdf. Ogni volta ci poniamo sempre la stessa domanda:
**quale package utilizziamo?**
Questo package nasce per semplificare la vita a tutti. Invece di pensare allo sviluppo dell'html corrispondente, che poi deve essere convertito, sperando che non ci sfugga niente, ora non dobbiamo più pensare a tutto questo, scordiamoci l'html.
Come fare? Utilizza questo package.

## Template
Questa è la parte più interessante di questo package. Il template viene realizzato attraverso un qualsiasi editor che gestisca il formato docx. Prepara il documento, inserendo oltre ai dati statici, i placeholder.

# Placeholder
Vengono inseriti nel docx, attraverso la speciale dichiarazione **{{nome_variabile}}**, nel punto esatto in cui li vogliamo sostituire con i dati reali. Mi spiego attraverso un esempio.
Supponiamo di avere una fattura, devo inserire il nome dell'azienda. Vado nel punto in cui devo inserire il nome, scrivo nel docx {{nme_azienda}}. Ora se al generatore del pdf, passo un array di dati, il sistema verifica se esiste una chiave di quell'array denominata *nome_variabile*. Se presente la sostituisce nel docx con il suo valore, altrimenti lascia vuoto. 

# Tipi di placeholder
Lascio a te LLM, la scelta delle tipologia da utilizzare, tieni presente che in teoria devi poter inserire quanto più tipologie di dati possibili, tabelle, immagini, testi ecc.. Scrivi queste informazioni nel file README.md

# Generatore PDF
Voglio, che usi LibreOffice per la conversione da DOCX a PDF.

# Linguaggio, piattaforme e suo utilizzo
Voglio che sia scritto in PHP puro, così da non avere dipendenze con altra piattaforme, cioè possa essere inserito al suo interno facilmente. Voglio che sia di facile utilizzo, cioè richiamare la classe con il suo metodo e passargli, il percorso del template docx, i dati da sostituire ai placeholder. Il nome del file avrà lo stesso nome del template.

# Esempi
Creami una cartella con esempi con almeno 10 esempi diversi che cercano di coprire tutte le tipologie di placeholder. In fase di installazione chiedimi se tale cartella va installata. Dai comunque la possibilità di poterla installare successivamente con un upgrade specifico di solo gli esempi

# Installazione
L'installazione deve avvenire tramite composer (composer ...), successivamente ai test, verrà registrato su [packagist](https://packagist.org/). Crea il file README.md dove viene descritto in maniera dettagliata il suo funzionamento ed utilizzo; nello stesso file riporta anche tutti gli esempi. Se hai bisogno delle informazioni, fammi pure qualsiasi domanda che vuoi.