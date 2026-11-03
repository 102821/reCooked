(function () {
    const script = document.currentScript;
    const category = script && script.dataset.category;
    const endpoint = script && script.dataset.endpoint;
    const recipeGrid = document.querySelector('.recipe-overview');
    const filter = document.querySelector('.recipe-filter');

    if (!category || !endpoint || !recipeGrid) {
        return;
    }

    const fallbackRecipes = {
        pasta: [
            ['Baked feta pasta', 'imgrecooked/pasta/baked feta pasta.png', 25, 'Easy'],
            ['Baked spaghetti', 'imgrecooked/pasta/baked spaghetti.png', 40, 'Easy'],
            ['Buttered noodles', 'imgrecooked/pasta/buttered noodles.png', 15, 'Easy'],
            ['Chicken alfredo', 'imgrecooked/pasta/chicken alfredo.png', 35, 'Medium'],
            ['Creamy garlic pasta', 'imgrecooked/pasta/creamy garlic pasta.png', 25, 'Easy'],
            ['Feta tomato pasta', 'imgrecooked/pasta/feta tomato pasta.png', 30, 'Easy'],
            ['Garlic herb linguine', 'imgrecooked/pasta/garlic herb linguine.png', 25, 'Easy'],
            ['Mexican mostaccioli', 'imgrecooked/pasta/mexican mostaccioli.png', 45, 'Medium'],
            ['Simple ramen', 'imgrecooked/pasta/simple ramen.png', 20, 'Easy'],
            ['Spaghetti cacio e pepe', 'imgrecooked/pasta/spaghetti cacio pepe.png', 20, 'Medium']
        ],
        desserts: [
            ['Banana chocolate chip cake', 'imgrecooked/desserts/banana chocolate chip cake.png', 25, 'Easy'],
            ['Brownies', 'imgrecooked/desserts/brownies.png', 40, 'Easy'],
            ['Cherry pie', 'imgrecooked/desserts/cherry pie.png', 15, 'Easy'],
            ['Chocolate pudding dessert', 'imgrecooked/desserts/chocolate pudding dessert.png', 35, 'Medium'],
            ['Creme brulee', 'imgrecooked/desserts/creme brulee.png', 25, 'Easy'],
            ['Fresh peach dessert', 'imgrecooked/desserts/fresh peach dessert.png', 30, 'Easy'],
            ['Lemon ricotta dessert', 'imgrecooked/desserts/lemon ricotta dessert.png', 25, 'Easy'],
            ['Pumpkin dessert', 'imgrecooked/desserts/pumpkin dessert.png', 45, 'Medium'],
            ['Strawberry angel food', 'imgrecooked/desserts/strawberry angel food.png', 20, 'Easy'],
            ['Strawberry twinkie', 'imgrecooked/desserts/strawberry twinkie.png', 20, 'Medium']
        ],
        other: [
            ['Air fried garlic butter steak', 'imgrecooked/verschillende recepten/air fried garlic butter steak.png', 25, 'Easy'],
            ['Campfire queso', 'imgrecooked/verschillende recepten/campfire queso.png', 40, 'Easy'],
            ['Cheddar bay chicken', 'imgrecooked/verschillende recepten/cheddar bay chicken.png', 15, 'Easy'],
            ['Chicken gobbler', 'imgrecooked/verschillende recepten/chicken_gobbler.png', 35, 'Medium'],
            ['Crispy parmesan crusted roasted chicken', 'imgrecooked/verschillende recepten/crispy parmesan crusted roasted chicken.png', 25, 'Easy'],
            ['Fried beef tacos', 'imgrecooked/verschillende recepten/fried beef tacos.png', 30, 'Easy'],
            ['Georgia pot roast', 'imgrecooked/verschillende recepten/georgia pot roast.png', 25, 'Easy'],
            ['Green goddess salad', 'imgrecooked/verschillende recepten/green goddess salad.png', 45, 'Medium'],
            ['Parmesan crusted potato', 'imgrecooked/verschillende recepten/parmesan crusted potato.png', 20, 'Easy']
        ]
    };

    function addCard(recipe, isDatabaseRecipe) {
        const card = document.createElement('a');
        card.className = 'overview-card' + (isDatabaseRecipe ? ' user-recipe' : '');
        card.dataset.title = recipe.title;
        card.href = '../detailpagina.html?title=' + encodeURIComponent(recipe.title) +
            '&category=' + encodeURIComponent(category);
        card.innerHTML = '<img alt=""><span></span><small class="card-details"></small>';
        updateCard(card, recipe, isDatabaseRecipe);
        recipeGrid.append(card);
        return card;
    }

    function updateCard(card, recipe, isDatabaseRecipe) {
        card.dataset.title = recipe.title;
        card.dataset.difficulty = recipe.difficulty;
        if (isDatabaseRecipe) {
            card.dataset.recipeId = recipe.id;
            card.href = '../detailpagina.html?id=' + encodeURIComponent(recipe.id);
        }
        card.querySelector('img').src = '../' + recipe.image_path;
        card.querySelector('img').alt = recipe.title;
        card.querySelector('span').textContent = recipe.title;
        card.querySelector('.card-details').textContent =
            recipe.prep_time + ' min - ' + recipe.difficulty;
    }

    (fallbackRecipes[category] || []).forEach(([title, imagePath, prepTime, difficulty]) => {
        addCard({
            title,
            image_path: imagePath,
            prep_time: prepTime,
            difficulty
        }, false);
    });

    function applyFilter() {
        const selectedDifficulty = filter ? filter.value.toLowerCase() : 'all';
        recipeGrid.querySelectorAll('.overview-card').forEach((card) => {
            const difficulty = (card.dataset.difficulty || '').toLowerCase();
            card.hidden = selectedDifficulty !== 'all' && difficulty !== selectedDifficulty;
        });
    }

    if (filter) {
        filter.addEventListener('change', applyFilter);
    }

    fetch(endpoint + '?category=' + encodeURIComponent(category))
        .then((response) => {
            if (!response.ok) {
                throw new Error('Recipes could not be loaded.');
            }
            return response.json();
        })
        .then((recipes) => {
            recipes.forEach((recipe) => {
                const existingCard = [...recipeGrid.querySelectorAll('.overview-card')]
                    .find((card) => card.dataset.title === recipe.title);

                if (existingCard) {
                    existingCard.classList.add('user-recipe');
                    updateCard(existingCard, recipe, true);
                    return;
                }

                const card = addCard(recipe, true);
                recipeGrid.prepend(card);
            });
            applyFilter();
        })
        .catch((error) => {
            console.error(error);
        });
}());