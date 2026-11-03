(function () {
    const script = document.currentScript;
    const endpoint = script && script.dataset.endpoint;
    const params = new URLSearchParams(window.location.search);
    const recipeId = params.get('id');
    const recipeTitle = params.get('title');
    const recipeCategory = params.get('category');

    if (!endpoint || (!recipeId && (!recipeTitle || !recipeCategory))) {
        return;
    }

    const title = document.querySelector('.recept-titel');
    const description = document.querySelector('.recept-beschrijving');
    const image = document.querySelector('.recept-img');
    const time = document.querySelector('.label-tijd');
    const difficulty = document.querySelector('.label-niveau');
    const ingredients = document.querySelector('.ingredienten');
    const steps = document.querySelector('.stappen');
    const backLink = document.querySelector('.btn-terug');

    const query = recipeId
        ? 'id=' + encodeURIComponent(recipeId)
        : 'title=' + encodeURIComponent(recipeTitle) +
          '&recipe_category=' + encodeURIComponent(recipeCategory);

    fetch(endpoint + '?' + query)
        .then((response) => {
            if (!response.ok) {
                throw new Error('Recipe could not be loaded.');
            }
            return response.json();
        })
        .then((recipe) => {
            document.title = recipe.title + ' | reCooked';
            title.textContent = recipe.title;
            description.textContent = recipe.description;
            image.src = recipe.image_path;
            image.alt = recipe.title;
            time.textContent = recipe.prep_time + ' min';
            difficulty.textContent = recipe.difficulty;
            backLink.href = 'overzichtspagina/overzichtspagina' +
                ({ pasta: '1', desserts: '2', other: '3' }[recipe.category] || '1') + '.html';

            recipe.ingredients.split(/\r?\n/).filter(Boolean).forEach((ingredient) => {
                const item = document.createElement('li');
                item.textContent = ingredient;
                ingredients.append(item);
            });

            recipe.description.split(/\r?\n/).filter(Boolean).forEach((step, index) => {
                const item = document.createElement('li');
                item.className = 'stap';
                item.innerHTML = '<span class="stap-label">Stap ' + (index + 1) +
                    '.</span><p class="stap-tekst"></p>';
                item.querySelector('.stap-tekst').textContent = step;
                steps.append(item);
            });
        })
        .catch((error) => {
            title.textContent = 'Recipe unavailable';
            description.textContent = error.message;
        });
}());
