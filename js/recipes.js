(function () {
    const script = document.currentScript;
    const category = script && script.dataset.category;
    const endpoint = script && script.dataset.endpoint;
    const recipeGrid = document.querySelector('.recipe-overview');
    const filter = document.querySelector('.recipe-filter');

    if (!category || !endpoint || !recipeGrid) {
        return;
    }

    function applyFilter() {
        const selectedDifficulty = filter ? filter.value : 'all';
        recipeGrid.querySelectorAll('.overview-card').forEach((card) => {
            const details = card.querySelector('.card-details');
            const difficulty = (card.dataset.difficulty || details?.textContent || '').toLowerCase();
            card.hidden = selectedDifficulty !== 'all' && !difficulty.includes(selectedDifficulty.toLowerCase());
        });
    }

    if (filter) {
        filter.addEventListener('change', applyFilter);
    }

    fetch(endpoint + '?category=' + encodeURIComponent(category))
        .then((response) => response.ok ? response.json() : [])
        .then((recipes) => {
            recipes.forEach((recipe) => {
                const card = document.createElement('a');
                card.className = 'overview-card user-recipe';
                card.dataset.difficulty = recipe.difficulty;
                card.href = '#';
                card.addEventListener('click', (event) => event.preventDefault());

                const image = document.createElement('img');
                image.src = '../' + recipe.image_path;
                image.alt = recipe.title;

                const title = document.createElement('span');
                title.textContent = recipe.title;

                const details = document.createElement('small');
                details.className = 'card-details';
                details.textContent = recipe.prep_time + ' min - ' + recipe.difficulty;

                const author = document.createElement('small');
                author.className = 'recipe-author';
                author.textContent = 'Made by ' + recipe.username;

                card.append(image, title, details, author);
                recipeGrid.prepend(card);
            });
            applyFilter();
        })
        .catch(() => {
            // Existing recipe cards remain available if the database is offline.
        });
}());