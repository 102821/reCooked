(function () {
    const script = document.currentScript;
    const authEndpoint = script && script.dataset.authEndpoint;
    const ingredientsEndpoint = script && script.dataset.ingredientsEndpoint;
    const form = document.querySelector('.recipe-form');

    if (!form) {
        return;
    }

    const message = document.querySelector('#form-message');
    const ingredientList = document.querySelector('#ingredient-list');
    const ingredientPicker = document.querySelector('#ingredient-picker');
    const addSelectedIngredient = document.querySelector('#add-selected-ingredient');
    const stepList = document.querySelector('#step-list');
    const ingredientsField = document.querySelector('#ingredients');
    const imageInput = document.querySelector('#image');
    const photoDrop = document.querySelector('#photo-drop');
    const photoPreview = document.querySelector('#photo-preview');
    const photoPlaceholder = document.querySelector('#photo-placeholder');
    const photoRemove = document.querySelector('#photo-remove');
    let photoUrl = '';

    function showMessage(text, type) {
        message.textContent = text;
        message.className = 'form-message' + (type ? ' ' + type : '');
    }

    function updateRemoveButtons(list) {
        const buttons = list.querySelectorAll('.remove-btn');
        buttons.forEach((button) => {
            button.hidden = buttons.length <= 1;
        });
    }

    function addRow(list, type, focus) {
        const row = document.createElement('li');
        const index = list.children.length + 1;
        const field = type === 'step'
            ? document.createElement('textarea')
            : document.createElement('input');

        if (type === 'step') {
            field.className = 'step-input';
            field.name = 'steps[]';
            field.rows = 3;
            field.setAttribute('aria-label', 'Bereidingsstap ' + index);
        } else {
            field.className = 'ingredient-input';
            field.type = 'text';
            field.setAttribute('aria-label', 'Ingrediënt ' + index);
        }

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'remove-btn';
        remove.textContent = '×';
        remove.setAttribute('aria-label', 'Verwijder ' + (type === 'step' ? 'bereidingsstap' : 'ingrediënt'));
        remove.addEventListener('click', () => {
            row.remove();
            updateRemoveButtons(list);
        });

        row.append(field, remove);
        list.append(row);
        updateRemoveButtons(list);
        if (focus) {
            field.focus();
        }
    }

    function addIngredient(name) {
        const normalizedName = name.trim();
        if (!normalizedName) {
            return;
        }

        const existingInputs = Array.from(ingredientList.querySelectorAll('.ingredient-input'));
        if (existingInputs.some((input) => input.value.trim().toLocaleLowerCase() === normalizedName.toLocaleLowerCase())) {
            showMessage('Dit ingrediënt staat al in de lijst.', 'error');
            return;
        }

        const emptyInput = existingInputs.find((input) => !input.value.trim());
        if (emptyInput) {
            emptyInput.value = normalizedName;
            emptyInput.focus();
            return;
        }

        addRow(ingredientList, 'ingredient', false);
        const newInput = ingredientList.lastElementChild.querySelector('.ingredient-input');
        newInput.value = normalizedName;
        newInput.focus();
    }

    function showPhoto(file) {
        if (!file) {
            return;
        }
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            imageInput.value = '';
            showMessage('Kies een JPG-, PNG- of WEBP-afbeelding.', 'error');
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            imageInput.value = '';
            showMessage('De foto mag maximaal 5 MB groot zijn.', 'error');
            return;
        }

        if (photoUrl) {
            URL.revokeObjectURL(photoUrl);
        }
        photoUrl = URL.createObjectURL(file);
        photoPreview.src = photoUrl;
        photoPreview.hidden = false;
        photoRemove.hidden = false;
        photoPlaceholder.hidden = true;
        showMessage('', '');
    }

    function clearPhoto() {
        if (photoUrl) {
            URL.revokeObjectURL(photoUrl);
            photoUrl = '';
        }
        imageInput.value = '';
        photoPreview.removeAttribute('src');
        photoPreview.hidden = true;
        photoRemove.hidden = true;
        photoPlaceholder.hidden = false;
    }

    for (let index = 0; index < 3; index += 1) {
        addRow(ingredientList, 'ingredient', false);
    }
    for (let index = 0; index < 2; index += 1) {
        addRow(stepList, 'step', false);
    }

    document.querySelector('#add-ingredient').addEventListener('click', () => {
        addRow(ingredientList, 'ingredient', true);
    });
    addSelectedIngredient.addEventListener('click', () => {
        addIngredient(ingredientPicker.value);
        ingredientPicker.value = '';
    });
    document.querySelector('#add-step').addEventListener('click', () => {
        addRow(stepList, 'step', true);
    });

    if (ingredientsEndpoint) {
        fetch(ingredientsEndpoint, { credentials: 'same-origin' })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Ingredient list could not be loaded (HTTP ' + response.status + ').');
                }
                return response.json();
            })
            .then((ingredients) => {
                if (!Array.isArray(ingredients) || ingredients.some((name) => typeof name !== 'string')) {
                    throw new Error('Ingredient list response has an invalid format.');
                }

                ingredientPicker.replaceChildren(new Option('Kies een ingrediënt', ''));
                ingredients.forEach((name) => {
                    ingredientPicker.add(new Option(name, name));
                });
                ingredientPicker.disabled = ingredients.length === 0;
                addSelectedIngredient.disabled = ingredients.length === 0;
                if (ingredients.length === 0) {
                    ingredientPicker.replaceChildren(new Option('Geen ingrediënten beschikbaar', ''));
                }
            })
            .catch((error) => {
                console.error(error);
                ingredientPicker.replaceChildren(new Option('Ingrediënten konden niet worden geladen', ''));
                showMessage('De beschikbare ingrediënten konden niet worden geladen. Je kunt ze hieronder zelf invoeren.', 'error');
            });
    }

    ingredientList.addEventListener('keydown', (event) => {
        const lastInput = ingredientList.lastElementChild &&
            ingredientList.lastElementChild.querySelector('.ingredient-input');
        if (event.key === 'Enter' && event.target === lastInput && lastInput.value.trim()) {
            event.preventDefault();
            addRow(ingredientList, 'ingredient', true);
        }
    });

    imageInput.addEventListener('change', () => showPhoto(imageInput.files[0]));
    photoRemove.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        clearPhoto();
    });

    ['dragenter', 'dragover'].forEach((eventName) => {
        photoDrop.addEventListener(eventName, (event) => {
            event.preventDefault();
            photoDrop.classList.add('dragover');
        });
    });
    ['dragleave', 'drop'].forEach((eventName) => {
        photoDrop.addEventListener(eventName, (event) => {
            event.preventDefault();
            photoDrop.classList.remove('dragover');
        });
    });
    photoDrop.addEventListener('drop', (event) => {
        const file = event.dataTransfer.files[0];
        if (file) {
            imageInput.files = event.dataTransfer.files;
            showPhoto(file);
        }
    });

    form.addEventListener('input', (event) => {
        event.target.classList.remove('invalid');
        if (message.classList.contains('error')) {
            showMessage('', '');
        }
    });

    form.addEventListener('submit', (event) => {
        const ingredients = Array.from(form.querySelectorAll('.ingredient-input'))
            .map((input) => input.value.trim())
            .filter(Boolean);
        const steps = Array.from(form.querySelectorAll('.step-input'))
            .map((input) => input.value.trim())
            .filter(Boolean);

        if (ingredients.length === 0 || steps.length === 0) {
            event.preventDefault();
            showMessage('Vul minimaal één ingrediënt en één bereidingsstap in.', 'error');
            const firstEmpty = ingredients.length === 0
                ? ingredientList.querySelector('.ingredient-input')
                : stepList.querySelector('.step-input');
            firstEmpty.classList.add('invalid');
            firstEmpty.focus();
            return;
        }

        ingredientsField.value = ingredients.join('\n');
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.textContent = 'Bezig met publiceren...';
    });

    if (authEndpoint) {
        fetch(authEndpoint, { credentials: 'same-origin' })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Authentication could not be checked (HTTP ' + response.status + ').');
                }
                return response.json();
            })
            .then((user) => {
                if (!user || !user.authenticated) {
                    window.location.replace('user/login.html?error=login-required');
                }
            })
            .catch((error) => {
                console.error(error);
                showMessage('Je login kon niet worden gecontroleerd. Probeer het later opnieuw.', 'error');
            });
    }
}());
