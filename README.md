# Kalkulator kosztu produktu (BOM)

Aplikacja w Symfony 7 do wyliczania kosztu wytworzenia i ceny sprzedaży produktu
na podstawie jego **BOM** (_Bill of Materials_, czyli listy materiałów z ilościami).

Dla każdego produktu definiujesz materiały i ich zużycie. Aplikacja liczy koszt
materiałów, dolicza marżę i podaje cenę końcową w trzech miejscach: na stronie
produktu, w REST API i na stronie kalkulatora, która pobiera dane z API bez
przeładowania strony.

![Kalkulator kosztów](docs/screenshots/kalkulator.png)

## Funkcje

- **CRUD materiałów i produktów**: wygenerowany przez `make:crud`, dopracowany
  pod Bootstrap 5, z polskim UI i komunikatami flash.
- **Zarządzanie BOM na stronie produktu**: dodawanie i usuwanie pozycji (materiał + ilość)
  z podsumowaniem kosztów na bieżąco.
- **Serwis `CostCalculator`**: cała logika kosztowa w jednym miejscu, bez kodu w kontrolerach.
- **REST API**: lista produktów i rozbicie kosztów produktu w JSON (Symfony Serializer).
- **Strona `/kalkulator`**: czysty JavaScript (`fetch`, bez frameworków) renderuje tabelę kosztów.
- **Walidacja**: nazwa wymagana, cena i ilość większe od zera, marża 0–999,99%,
  ten sam materiał tylko raz w BOM produktu.
- **Fixtures**: przykładowe produkty placu zabaw (Huśtawka, Zjeżdżalnia, Piaskownica).
- **Testy**: jednostkowe dla `CostCalculator` i funkcjonalne dla API.

## Technologie

| Obszar         | Technologia                                                       |
|----------------|-------------------------------------------------------------------|
| Backend        | PHP 8.2+ (rozwijane na 8.4), Symfony 7.4 LTS                      |
| Środowisko     | Docker Compose: FrankenPHP (aplikacja) + MySQL 8.4                |
| ORM            | Doctrine ORM 3 + Doctrine Migrations, DoctrineFixturesBundle      |
| Widoki         | Twig, Bootstrap 5.3 (CDN), Symfony Form + Validator               |
| API            | Symfony Serializer (grupy serializacji, DTO)                      |
| Frontend       | Vanilla JavaScript (ES modules, `fetch`, `<template>`)            |
| Testy          | PHPUnit 11, WebTestCase, DAMA DoctrineTestBundle                  |
| Jakość kodu    | PHP-CS-Fixer (`@PSR12` + `@Symfony`, `declare(strict_types=1)`)   |

## Kluczowe decyzje projektowe

- **Pieniądze bez floatów.** Kolumny `DECIMAL` są w PHP stringami, a obliczenia idą
  przez `bcmath`. API zwraca kwoty jako stringi (`"876.96"`), żeby klient nie tracił precyzji.
- **Model kosztów.** Koszt pozycji = ilość × cena jednostkowa, zaokrąglony do groszy
  (jak na fakturze). Marża liczona jest od kosztu materiałów (narzut):
  `cena końcowa = koszt materiałów + koszt materiałów × marża% / 100`.
- **Niemutowalny wynik.** `CostCalculator` zwraca DTO `CostBreakdown` / `CostLine`
  (`final readonly class`), serializowane wprost do JSON.
- **Bez N+1.** `ProductRepository::findWithBom()` i `findAllWithBom()` pobierają
  produkt, pozycje BOM i materiały jednym zapytaniem (fetch join).
- **Jednostki jako enum.** `App\Enum\Unit` (szt, m, m2, kg, l) mapowany przez Doctrine `enumType`.
- **Spójność danych.** Unikalny indeks (produkt, materiał) + `UniqueEntity`. Materiału
  użytego w BOM nie da się usunąć (czytelny komunikat zamiast błędu klucza obcego).
  Usunięcie produktu kasuje jego BOM (`ON DELETE CASCADE` + `orphanRemoval`).
- **Bezpieczny frontend.** JS wstawia dane przez `textContent` do szablonów `<template>`
  (bez `innerHTML`, czyli bez ryzyka XSS) i przerywa nieaktualne żądania przez `AbortController`.
- **Spójne błędy API.** Wyjątki HTTP pod `/api/` (404, 405) zwracane są jako JSON.

## Struktura katalogów

```
├── docker-compose.yml             # aplikacja (FrankenPHP, port 8088) + MySQL 8.4 (port 3309)
├── docker/app/                    # obraz aplikacji i skrypt startowy (migracje, fixtures)
├── docker/mysql/init.sql          # zakłada bazę testową bom_calculator_test
├── migrations/                    # migracje Doctrine
├── public/js/calculator.js        # strona /kalkulator (fetch + render tabeli)
├── src/
│   ├── Controller/
│   │   ├── Api/ProductApiController.php
│   │   ├── CalculatorController.php
│   │   ├── MaterialController.php
│   │   └── ProductController.php  # CRUD produktu + zarządzanie BOM
│   ├── DataFixtures/AppFixtures.php
│   ├── Dto/                       # CostBreakdown, CostLine
│   ├── Entity/                    # Material, Product, BomItem
│   ├── Enum/Unit.php
│   ├── EventListener/ApiExceptionListener.php
│   ├── Form/                      # MaterialType, ProductType, BomItemType
│   ├── Repository/
│   ├── Service/CostCalculator.php
│   └── Twig/NumberFormatExtension.php   # filtry |pln i |decimal
├── templates/
└── tests/
    ├── Functional/Api/ProductApiTest.php
    └── Unit/Service/CostCalculatorTest.php
```

## Uruchomienie

### Opcja A: wszystko w Dockerze (wymagany tylko Docker)

```bash
git clone https://github.com/ryba-pawel/bom-calculator.git
cd bom-calculator
docker compose up -d --wait
```

Aplikacja: **http://127.0.0.1:8088**. W Docker Desktop link jest przy kontenerze `app`
(port `8088:80`).

Pierwsze uruchomienie trwa kilka minut: Docker buduje obraz aplikacji (FrankenPHP),
a kontener przy starcie instaluje zależności Composera, uruchamia migracje
(także w bazie testowej) i przy pustej bazie ładuje dane przykładowe. Kod jest
podmontowany do kontenera, więc zmiany w plikach widać od razu, bez przebudowy.

Testy i konsola Symfony w kontenerze:

```bash
docker compose exec app composer test
docker compose exec app php bin/console doctrine:fixtures:load -n   # przywraca dane przykładowe
```

Zatrzymanie: `docker compose down` (dane zostają), `docker compose down -v` (usuwa też bazę).

### Opcja B: lokalne PHP + Symfony CLI

Wymagania:

- PHP 8.2 lub nowszy z rozszerzeniami `pdo_mysql`, `bcmath`, `intl`, `ctype`, `iconv`
- [Composer](https://getcomposer.org/) 2
- [Docker](https://www.docker.com/) z Docker Compose (tylko dla MySQL)
- [Symfony CLI](https://symfony.com/download) (na macOS: `brew install symfony-cli/tap/symfony-cli`)

1. **Sklonuj repozytorium i zainstaluj zależności**

   ```bash
   git clone https://github.com/ryba-pawel/bom-calculator.git
   cd bom-calculator
   composer install
   ```

2. **Uruchom samą bazę danych MySQL**

   ```bash
   docker compose up -d --wait database
   ```

   MySQL nasłuchuje na porcie **3309** hosta (żeby nie kolidować z lokalnym MySQL
   na 3306). Dane dostępowe są w `.env` (`DATABASE_URL`). Inną konfigurację ustawisz
   w pliku `.env.local`, który nie trafia do repozytorium.

3. **Utwórz schemat bazy i załaduj przykładowe dane**

   ```bash
   php bin/console doctrine:migrations:migrate -n
   php bin/console doctrine:fixtures:load -n
   ```

4. **Uruchom serwer deweloperski**

   ```bash
   symfony serve -d
   ```

   Aplikacja będzie dostępna pod adresem wypisanym przez Symfony CLI, domyślnie
   http://127.0.0.1:8000. Jeśli port jest zajęty, użyj np. `symfony serve -d --port=8001`.
   Zamiast Symfony CLI możesz użyć wbudowanego serwera PHP: `php -S 127.0.0.1:8000 -t public`.

5. **Testy**

   Baza testowa `bom_calculator_test` powstaje automatycznie przy pierwszym starcie
   kontenera MySQL (`docker/mysql/init.sql`). Przed pierwszym uruchomieniem testów
   utwórz w niej schemat, potem uruchom testy:

   ```bash
   php bin/console --env=test doctrine:migrations:migrate -n
   composer test          # lub: php bin/phpunit
   ```

Każdy test funkcjonalny działa w transakcji wycofywanej po teście (DAMA DoctrineTestBundle),
więc baza testowa zostaje czysta.

### Najważniejsze adresy

| Strona                    | Adres                     |
|---------------------------|---------------------------|
| Produkty (strona główna)  | `/products`               |
| Materiały                 | `/materials`              |
| Kalkulator (fetch + API)  | `/kalkulator`             |
| API: lista produktów      | `/api/products`           |
| API: koszt produktu       | `/api/products/1/cost`    |

### Styl kodu

```bash
composer cs            # sprawdzenie (PSR-12 + Symfony)
composer cs:fix        # automatyczna poprawa
```

## REST API

| Metoda | Endpoint                    | Opis                                    | Kody        |
|--------|-----------------------------|-----------------------------------------|-------------|
| GET    | `/api/products`             | Lista produktów (sortowana po nazwie)   | 200         |
| GET    | `/api/products/{id}/cost`   | Rozbicie kosztów produktu               | 200, 404    |

Przykłady zakładają uruchomienie w Dockerze (port 8088). Przy Symfony CLI użyj portu 8000.

### `GET /api/products`

```bash
curl http://127.0.0.1:8088/api/products
```

```json
[
    {"id": 1, "name": "Huśtawka", "marginPercent": "35.00"},
    {"id": 3, "name": "Piaskownica", "marginPercent": "30.00"},
    {"id": 2, "name": "Zjeżdżalnia", "marginPercent": "40.00"}
]
```

### `GET /api/products/{id}/cost`

```bash
curl http://127.0.0.1:8088/api/products/1/cost
```

Odpowiedź `200 OK` (lista `items` skrócona do 2 z 6 pozycji):

```json
{
    "productId": 1,
    "productName": "Huśtawka",
    "items": [
        {
            "bomItemId": 1,
            "materialId": 1,
            "materialName": "Kantówka sosnowa 90×90 mm",
            "unit": "m",
            "quantity": "14.000",
            "unitPrice": "18.50",
            "totalCost": "259.00"
        },
        {
            "bomItemId": 6,
            "materialId": 6,
            "materialName": "Farba ochronna do drewna",
            "unit": "l",
            "quantity": "2.500",
            "unitPrice": "42.00",
            "totalCost": "105.00"
        }
    ],
    "materialsCost": "649.60",
    "marginPercent": "35.00",
    "marginAmount": "227.36",
    "finalPrice": "876.96",
    "currency": "PLN"
}
```

Gdy produkt nie istnieje, odpowiedź to `404 Not Found`:

```json
{"error": "Produkt o id 999 nie istnieje."}
```

## Zrzuty ekranu

**Lista produktów** z kosztem materiałów i ceną końcową:

![Lista produktów](docs/screenshots/produkty.png)

**Karta produktu**: BOM, podsumowanie kosztów i dodawanie pozycji:

![Karta produktu](docs/screenshots/produkt.png)

## Możliwe kierunki rozwoju

Zaplanowane usprawnienia i znane problemy są opisane w zakładce
[Issues](https://github.com/ryba-pawel/bom-calculator/issues), m.in. CI w GitHub Actions,
PHPStan, edycja ilości pozycji BOM i pełne środowisko w Dockerze.

Pomysły na dalszą przyszłość, jeszcze bez zgłoszeń: uwierzytelnianie (Symfony Security)
z rolami oraz historia cen materiałów z kalkulacjami „na dzień”.
