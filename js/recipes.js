(function () {
    const script = document.currentScript;
    const category = script && script.dataset.category;
    const endpoint = script && script.dataset.endpoint;
    const recipeGrid = document.querySelector('.recipe-overview');
    const filter = document.querySelector('.recipe-filter');

    if (!category || !endpoint || !recipeGrid) {
        return;
    }

    const status = document.createElement('p');
    status.className = 'recipe-status';
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');
    status.textContent = 'Loading recipes...';
    recipeGrid.before(status);

    function addCard(recipe) {
        const card = document.createElement('a');
        card.className = 'overview-card';
        card.dataset.title = recipe.title;
        card.dataset.difficulty = recipe.difficulty;
        card.dataset.recipeId = recipe.id;
        card.href = '../detailpagina.html?id=' + encodeURIComponent(recipe.id);

        const image = document.createElement('img');
        image.src = '../' + recipe.image_path;
        image.alt = recipe.title;

        const title = document.createElement('span');
        title.textContent = recipe.title;

        const details = document.createElement('small');
        details.className = 'card-details';
        details.textContent = recipe.prep_time + ' min - ' + recipe.difficulty;

        card.append(image, title, details);
        recipeGrid.append(card);
    }

    function applyFilter() {
        const selectedDifficulty = filter ? filter.value.toLowerCase() : 'all';
        const cards = [...recipeGrid.querySelectorAll('.overview-card')];
        const visibleCards = cards.filter((card) => {
            const matches = selectedDifficulty === 'all' ||
                (card.dataset.difficulty || '').toLowerCase() === selectedDifficulty;
            card.hidden = !matches;
            return matches;
        });

        if (cards.length === 0) {
            status.textContent = 'No recipes found in this category.';
        } else if (visibleCards.length === 0) {
            status.textContent = 'No recipes found for this difficulty.';
        } else {
            status.textContent = '';
        }
        status.hidden = status.textContent === '';
    }

    if (filter) {
        filter.addEventListener('change', applyFilter);
    }

    fetch(endpoint + '?category=' + encodeURIComponent(category))
        .then((response) => {
            if (!response.ok) {
                throw new Error('Recipes could not be loaded (HTTP ' + response.status + ').');
            }
            return response.json();
        })
        .then((recipes) => {
            if (!Array.isArray(recipes)) {
                throw new Error('The recipes endpoint returned an unexpected response.');
            }

            recipes.forEach(addCard);
            applyFilter();
        })
        .catch((error) => {
            console.error(error);
            status.textContent = 'Recipes could not be loaded. Please try again later.';
            status.hidden = false;
        });
}());
