# Sirk

Projekt zaliczeniowy z przedmiotu **System interakcyjny** na kierunku Elektroniczne Przetwarzanie Informacji (2 r. I st.).
Temat projektu: **Aplikacja do zarządzania projektami IT**.

Nazwa *Sirk* to połączenie skrótowca SI oraz słowa work.

## Technologia

Oprogramownie użyte podczas pracy nad projektem Sirk.

* Ubuntu 16.10
* phpStorm 17.01
	- Composer
	- phpCodeSniffer
	- phpDocumentor
* MySQL Workbench

### TechStack

Technologia, na której opierać będzie się aplikacja Sirk.

* PHP wraz z frameworkiem [Silex](http://silex.sensiolabs.org/)
* MySQL 5
* HTML + CSS (z frameworkiem [UIkit](https://getuikit.com/))

## Funkcje

Planowane funkcje aplikacji.

1. [Lista zadań (todo, kanban)](#sirk-funkcje-lista-zadan)
2. [Time tracker](#sirk-funkcje-time-tracker)
2. [Kalendarz](#sirk-funkcje-kalendarz)
3. [Pliki](#sirk-funkcje-pliki)
4. [Dyskusja (czat)](#sirk-funkcje-dyskusja)
5. [Notatki](#sirk-funkcje-notatki)
6. [Podsumowanie projektu](#sirk-funkcje-podsumowanie-projektu)
7. [Agenda](#sirk-funkcje-agenda)

### Lista zadań

Najbardziej podstawowa funkcja aplikacji do zarządzania projektami.

Do każdego projektu można dodać zadanie. Zadanie takie ma: **tytuł\***, opis, priorytet oraz termin wykonania.  
Przy każdym zadaniu można w dowolnym momencie uruchomić odmierzanie czasu ([time tracker](#sirk-funkcje-time-tracker)).  
Każde zadanie można też przypisać do konkretnej osoby co sprawia, że w osobistej [agendzie](#sirk-funkcje-agenda) pojawiają się przypisane zadania.

Poza [klientem](#sirk-uzytkownicy-klient), który w ogóle nie ma dostępu do tej sekcji, jest ona otwarta dla wszystkich [pracowników](#sirk-uzytkownicy-pracownik).

### Time tracker

Każde [zadanie](#sirk-funkcje-lista-zadan) posiada osobny licznik czasu, który został poświęcony na jego wykonanie.  
Taki licznik [pracownik](#sirk-uzytkownicy-pracownik) może uruchamiać wiele razy, a każe uruchomienie pozostawi po sobie ślad w logu. Będzie to czas spędzony na zadaniu oraz data, kiedy miało to miejsce.

Widoczne będzie także podsumowanie czasu spędzonego na zadaniu we wszystkich podejściach przez wszystkich (dla [administratora](#sirk-uzytkownicy-administrator) oraz przez danego [pracownika](#sirk-uzytkownicy-pracownik) (dla niego).

[Klient](#sirk-uzytkownicy-klient) nie ma dostępu do tej sekcji.

### Kalendarz

Kalendarz to bardzo przydatna funkcja, której działanie polega na pobieraniu wszystkich [zadań](#sirk-funkcje-lista-zadan) przypisanych do projektu, a następnie wyświetlenie ich w atrakcyjnej wizualnie formie.  
Zadania trafiają do odpowiednich dni w kalendarzu, co pozwala łatwo zobaczyć zbliżające się [zadania](#sirk-funkcje-lista-zadan) i ich priorytet (np. poprzez różne kolory).

Z tego widoku również można otworzyć konkretne [zadanie](#sirk-funkcje-zadania), uruchomić [śledzenie czasu](#sirk-funkcje-time-tracker).

Poza [klientem](#sirk-uzytkownicy-klient), który w ogóle nie ma dostępu do tej sekcji, jest ona otwarta dla wszystkich [pracowników](#sirk-uzytkownicy-pracownik).

### Pliki

Każdy projekt posiada również sekcję plików (załączników).

Załączniki mogą być grafikami, plikami audio, wideo czy też dokumentami (np. PDF).  
Aplikacja powinna pozwalać na podgląd tych plików (wyświetlenie zdjęcia, odsłuchanie pliku audio) i ich pobranie.  
Każdy plik byłby też opatrzony informacją o tym, jaki użytkownik go dodał i kiedy.

Pliki może dodawać każdy, kto ma dostęp do projektu. Usuwać może je tylko [administrator](#sirk-uzytkownicy-administrator).

### Dyskusja

Dyskusja to bardzo ważna funkcjonalność. Pozwala ona na rozmowę pomiędzy [klientem](#sirk-uzytkownicy-klient) a [pracownikiem](#sirk-uzytkownicy-pracownik) wewnątrz systemu, w jednym scentralizowanym miejscu (zamiast wiadomości rozrzuconych w mailach, Facebooku itd.).

Dyskusja powinna mieć formę grupowego czatu dla osób włączonych w dany projekt. Każdy użytkownik powinien mieć możliwość edytowania swoich wiadomości z przeszłości. Dyskusja może mieć funkcję dołączania do wiadomości [załączników](#sirk-funkcje-pliki] (permalink do załącznika w sekcji pliki).

Do dyskusji mają dostęp wszyscy [użytkownicy](#sirk-uzytkownicy) przypisani do projektu. W razie potrzeby, wiadomości moderować (edytować, usuwać) może tylko [administrator](#sirk-uzytkownicy-administrator).

### Notatki

Każdy projekt posiada także sekcję służącą tworzeniu notatek.

Jest to tak naprawdę jeden wielki notatnik, gdzie [pracownik](#sirk-uzytkownicy-pracownik) może zapisywać różne rzeczy – na przykład ciekawe pomysły, spostrzeżenia, inspiracje. Każdy pracownik ma swój własny notatnik, który nie jest dzielony z innymi pracownikami.

Dostęp do notatnika mają wszyscy [pracownicy](#sirk-uzytkownicy-pracownik) zaangażowani w projekt.

### Podsumowanie projektu

Jest to tak naprawdę widok startowy po otwarciu projektu i stanowi jego podsumowanie.

[Wszyscy użytkownicy](#sirk-uzytkownicy) widzą tutaj ostatnie zmiany w projekcie (np. [pracownicy](#sirk-uzytkownicy-pracownik) dodanie zadania, a wszyscy widzą pojawienie się nowych wiadomości w [dyskusji](#sirk-funkcje-dyskusja), dodanie nowego [pliku](#sirk-funkcje-pliki)).

[Administratorowi](#sirk-uzytkownicy-administrator) pozwala podejrzeć [osoby włączone do projektu](#sirk-uzytkownicy), zbliżające się terminy wykonania wszystkich [zadań](#sirk-funkcje-lista-zadan). Widok ten pozwala także na zsumowanie czasu spędzonego na wszystkich zadaniach w tym projekcie.

[Pracownik](#sirk-uzytkownicy-pracownik) również widzi [zadania](#sirk-funkcje-lista-zadan) do wykonania w najbliższym czasie, ale tylko niepodpięte do nikogo lub podpięte do niego; także zsumowany [czas](#sirk-funkcje-time-tracker), który poświęcił na cały projekt (we wszystkich zadaniach).

### Agenda

Jest to startowy widok po zalogowaniu się i stanowi swego rodzaju [osobisty panel startowy](#sirk-uzytkownicy-dziedziczone).

Agenda nieco przypomina podsumownanie projektu, jednak zbiera informacje ze wszystkich projektów, w jakich dany użytkownik bierze udział. Wyświetla zadania do wykonania w najbliższym czasie, zmiany w projektach itp.

## Użytkownicy

### Niezalogowany

Może jedynie wyświetlić stronę startową, pozwalającą na zalogowanie się i przypomnienie hasła (gdyby zostało zapomniane przez użytkownika).

### Dziedziczone

Każdy użytkownik zalogowany dziedziczy pewien zbiór uprawnień. Jest to przede wszystkim dostęp do [osobistej agendy](#sirk-funkcje-agenda).  
Użytkownik może też modyfikować swój profil, zmieniając swoje dane, aktualizując zdjęcie profilowe, zmieniając hasło.

Informacje, jakie **musi** i może podać użytkownik:

* **Login**
* **Hasło**
* **Adres e-mail**
* Awatar
* Imię i nazwisko
* Nazwa firmy
* Wiek

### Administrator

Ma pełen dostęp do serwisu. Może dodawać i usuwać użytkowników, dodawać, usuwać, modyfikować wszystkie projekty.  
Do każdego projektu może przypisać klienta i innych pracowników.  
Może przydzielać [zadania](#sirk-funkcje-lista-zadan) do innych pracowników.

### Pracownik

Administrator może dodać dodatkowych pracowników (utworzyć dla nich konta) i przypisać ich do konkretnych projektów.  
Pracownik ma dostęp tylko do projektów, do których zostanie dodany. Nie może też dodawać ani usuwać innych użytkowników, nie może usunąć projektu ani przypisać do niego innych użytkowników.  
Nie może przydzielić [zadania](#sirk-funkcje-lista-zadan), chyba, że do siebie samego.

### Klient

Klient posiada ograniczony dostęp do serwisu, głównie pozwalający na wygodną komunikację z pracownikami.  
Ma dostęp do [dyskusji](#sirk-funkcje-dyskusja), gdzie może dodawać nowe wiadomości; ma prawo do zamieszczania załączników w sekcji [pliki](#sirk-funkcje-pliki).
Nie widzi [listy zadań](#sirk-funkcje-lista-zadan) ani [notatek](#sirk-funkcje-notatki). Ma dostęp do uproszczonego [kalendarza](#sirk-funkcje-kalendarz), w którym widać zaplanowane zadania, ale bez szczegółów (tylko tytuł zadania, jego priorytet i termin do wykonania).

# CRUD

## Administrator

Obiekt            	| Uprawnienia
------------------- | -------------
Projekt			  	| CRUD
Zadania			  	| CRUD
Time-tracker		| CRUD
Pliki				| CR–D
Dyskusja			| CRUD				   
Notatki				| CRUD
Profil				| CRUD				   

## Pracownik

Obiekt            	| Uprawnienia   | Komentarz
------------------- | ------------- | -------------------
Projekt			  	| –RU–			|
Zadania			  	| CRU–			| 
Time-tracker		| CR––			| Tylko swoje
Pliki				| CR––			|
Dyskusja			| CR––			|	   
Notatki				| CRUD			|
Profil				| –RU–			| Tylko swój

## Klient

Obiekt            	| Uprawnienia   | Komentarz
------------------- | ------------- | -------------------
Projekt			  	| –R––			|
Zadania			  	| ––––			|
Time-tracker		| ––––			|
Pliki				| CR––			|
Dyskusja			| CR––			|	   
Notatki				| ––––			|
Profil				| –RU–			| Tylko swój

# Informacje

26/03/2017 [mciszczon.pl]  
Kontakt: [contact@mciszczon.pl]
 
[mciszczon.pl]: https://mciszczon.pl/
[mciszczon@gmail.com]: mailto:mciszczon@gmail.com
