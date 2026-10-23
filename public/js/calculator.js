/**
 * Kalkulator kosztów: pobiera listę produktów i rozbicie kosztów z REST API
 * i renderuje tabelę bez przeładowania strony. Czysty JavaScript, bez frameworków.
 */

const root = document.getElementById('calculator');
const select = document.getElementById('product-select');
const result = document.getElementById('calculator-result');
const { productsUrl, productPageUrl } = root.dataset;

const money = new Intl.NumberFormat('pl-PL', { style: 'currency', currency: 'PLN', useGrouping: 'always' });
const decimal = new Intl.NumberFormat('pl-PL', { maximumFractionDigits: 3, useGrouping: 'always' });

// API zwraca kwoty jako stringi (precyzja DECIMAL) – na liczbę zamieniamy je wyłącznie do wyświetlenia.
const formatMoney = (amount) => money.format(Number(amount));
const formatDecimal = (value) => decimal.format(Number(value));

let pendingRequest = null;

async function fetchJson(url, signal) {
    const response = await fetch(url, { headers: { Accept: 'application/json' }, signal });
    const data = await response.json().catch(() => null);

    if (!response.ok) {
        throw new Error(data?.error ?? `Błąd serwera (HTTP ${response.status}).`);
    }

    if (data === null) {
        throw new Error('Nieprawidłowa odpowiedź serwera.');
    }

    return data;
}

/** Klonuje <template> i wstawia wartości do elementów [data-field] jako tekst. */
function cloneTemplate(id, values = {}) {
    const fragment = document.getElementById(id).content.cloneNode(true);

    for (const element of fragment.querySelectorAll('[data-field]')) {
        element.textContent = values[element.dataset.field] ?? '';
    }

    return fragment;
}

function showStatus(templateId, message = '') {
    result.replaceChildren(cloneTemplate(templateId, { message }));
}

function renderCost(cost) {
    const table = cloneTemplate('cost-table-template', {
        productName: cost.productName,
        materialsCost: formatMoney(cost.materialsCost),
        marginPercent: formatDecimal(cost.marginPercent),
        marginAmount: formatMoney(cost.marginAmount),
        finalPrice: formatMoney(cost.finalPrice),
    });
    const rows = table.querySelector('[data-slot="items"]');

    if (cost.items.length === 0) {
        rows.append(cloneTemplate('cost-empty-template'));
    }

    for (const item of cost.items) {
        rows.append(cloneTemplate('cost-row-template', {
            materialName: item.materialName,
            quantity: `${formatDecimal(item.quantity)} ${item.unit}`,
            unitPrice: formatMoney(item.unitPrice),
            totalCost: formatMoney(item.totalCost),
        }));
    }

    table.querySelector('[data-slot="product-link"]').href = `${productPageUrl}/${cost.productId}`;
    result.replaceChildren(table);
}

async function loadCost(productId) {
    // Szybka zmiana produktu: przerywamy poprzednie żądanie, żeby stara odpowiedź nie nadpisała nowej
    pendingRequest?.abort();

    const url = new URL(window.location.href);
    if (productId) {
        url.searchParams.set('product', productId);
    } else {
        url.searchParams.delete('product');
    }
    window.history.replaceState(null, '', url);

    if (!productId) {
        result.replaceChildren();
        return;
    }

    const controller = new AbortController();
    pendingRequest = controller;
    showStatus('loading-template');

    // signal.aborted zamiast error.name: AbortError z response.json() jest połykany w fetchJson,
    // a odpowiedź na przerwane żądanie nie może nadpisać wyniku nowego wyboru.
    try {
        const cost = await fetchJson(`${productsUrl}/${encodeURIComponent(productId)}/cost`, controller.signal);
        if (!controller.signal.aborted) {
            renderCost(cost);
        }
    } catch (error) {
        if (!controller.signal.aborted) {
            showStatus('error-template', error.message);
        }
    }
}

async function loadProducts() {
    try {
        const products = await fetchJson(productsUrl);

        select.replaceChildren(
            new Option(products.length > 0 ? '— wybierz produkt —' : 'Brak produktów', ''),
            ...products.map((product) => new Option(product.name, product.id)),
        );
        select.disabled = products.length === 0;
    } catch (error) {
        select.replaceChildren(new Option('Nie udało się wczytać produktów', ''));
        showStatus('error-template', error.message);
        return;
    }

    // Link z parametrem ?product=ID (np. z karty produktu) od razu pokazuje koszty
    const preselected = new URLSearchParams(window.location.search).get('product');
    if (preselected && [...select.options].some((option) => option.value === preselected)) {
        select.value = preselected;
        await loadCost(preselected);
    }
}

select.addEventListener('change', () => loadCost(select.value));
loadProducts();
