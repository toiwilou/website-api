const search = document.getElementById('search');
const cards = document.querySelectorAll('.card');
const noResult = document.getElementById('no-result');

search.addEventListener('input', () => {
    const q = search.value.trim().toLowerCase();
    let visible = 0;

    cards.forEach(card => {
        const match = card.textContent.toLowerCase().includes(q);
        card.hidden = !match;
        if (match) visible++;
    });

    noResult.hidden = visible > 0;
});
