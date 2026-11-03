CREATE TABLE IF NOT EXISTS users (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	username VARCHAR(50) NOT NULL,
	password_hash VARCHAR(255) NOT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY users_username_unique (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recipes (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	user_id INT UNSIGNED NOT NULL,
	title VARCHAR(100) NOT NULL,
	description TEXT NOT NULL,
	ingredients TEXT NOT NULL,
	category VARCHAR(30) NOT NULL,
	difficulty VARCHAR(20) NOT NULL,
	prep_time SMALLINT UNSIGNED NOT NULL,
	image_path VARCHAR(255) NOT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	KEY recipes_category_idx (category),
	UNIQUE KEY recipes_category_title_unique (category, title),
	CONSTRAINT recipes_user_fk FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recipe_steps (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	recipe_id INT UNSIGNED NOT NULL,
	step_number SMALLINT UNSIGNED NOT NULL,
	description TEXT NOT NULL,
	PRIMARY KEY (id),
	UNIQUE KEY recipe_steps_order_unique (recipe_id, step_number),
	CONSTRAINT recipe_steps_recipe_fk FOREIGN KEY (recipe_id) REFERENCES recipes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE recipes
	ADD COLUMN IF NOT EXISTS ingredients TEXT NULL AFTER description;

ALTER TABLE recipes
	ADD COLUMN IF NOT EXISTS category VARCHAR(30) NULL AFTER ingredients,
	ADD COLUMN IF NOT EXISTS difficulty VARCHAR(20) NULL AFTER category,
	ADD COLUMN IF NOT EXISTS prep_time SMALLINT UNSIGNED NULL AFTER difficulty,
	ADD COLUMN IF NOT EXISTS image_path VARCHAR(255) NULL AFTER prep_time;

UPDATE recipes
SET ingredients = ''
WHERE ingredients IS NULL;

UPDATE recipes
SET category = 'other'
WHERE category IS NULL;

UPDATE recipes
SET difficulty = 'Easy'
WHERE difficulty IS NULL;

UPDATE recipes
SET prep_time = 1
WHERE prep_time IS NULL;

UPDATE recipes
SET image_path = ''
WHERE image_path IS NULL;

ALTER TABLE recipes
	MODIFY COLUMN ingredients TEXT NOT NULL,
	MODIFY COLUMN category VARCHAR(30) NOT NULL,
	MODIFY COLUMN difficulty VARCHAR(20) NOT NULL,
	MODIFY COLUMN prep_time SMALLINT UNSIGNED NOT NULL,
	MODIFY COLUMN image_path VARCHAR(255) NOT NULL;

INSERT INTO users (username, password_hash)
VALUES (
	'reCooked',
	'$2y$12$p0bK6q/jO7hziTE48yy4duHWQHUOyum6wUx7QHrYfGTbUhQX1OgNK'
)
ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id);

SET @builtin_user_id = LAST_INSERT_ID();

INSERT IGNORE INTO recipes
	(user_id, title, description, ingredients, category, difficulty, prep_time, image_path)
VALUES
	(@builtin_user_id, 'Baked feta pasta', 'A creamy baked feta pasta with tomatoes.', 'Pasta\nFeta\nTomatoes\nOlive oil', 'pasta', 'Easy', 25, 'imgrecooked/pasta/baked feta pasta.png'),
	(@builtin_user_id, 'Baked spaghetti', 'A comforting baked spaghetti dish.', 'Spaghetti\nTomato sauce\nMozzarella\nParmesan', 'pasta', 'Easy', 40, 'imgrecooked/pasta/baked spaghetti.png'),
	(@builtin_user_id, 'Buttered noodles', 'Simple noodles tossed with butter.', 'Noodles\nButter\nSalt\nBlack pepper', 'pasta', 'Easy', 15, 'imgrecooked/pasta/buttered noodles.png'),
	(@builtin_user_id, 'Chicken alfredo', 'Creamy pasta with chicken and parmesan.', 'Pasta\nChicken\nCream\nParmesan\nGarlic', 'pasta', 'Medium', 35, 'imgrecooked/pasta/chicken alfredo.png'),
	(@builtin_user_id, 'Creamy garlic pasta', 'A creamy pasta with garlic and herbs.', 'Pasta\nCream\nGarlic\nParmesan\nParsley', 'pasta', 'Easy', 25, 'imgrecooked/pasta/creamy garlic pasta.png'),
	(@builtin_user_id, 'Feta tomato pasta', 'Pasta with feta and roasted tomatoes.', 'Pasta\nFeta\nTomatoes\nOlive oil\nBasil', 'pasta', 'Easy', 30, 'imgrecooked/pasta/feta tomato pasta.png'),
	(@builtin_user_id, 'Garlic herb linguine', 'Linguine flavored with garlic and fresh herbs.', 'Linguine\nGarlic\nMixed herbs\nOlive oil\nParmesan', 'pasta', 'Easy', 25, 'imgrecooked/pasta/garlic herb linguine.png'),
	(@builtin_user_id, 'Mexican mostaccioli', 'A spicy Mexican-inspired baked pasta.', 'Mostaccioli\nGround beef\nTomatoes\nBeans\nCheese', 'pasta', 'Medium', 45, 'imgrecooked/pasta/mexican mostaccioli.png'),
	(@builtin_user_id, 'Simple ramen', 'A quick and simple ramen bowl.', 'Ramen noodles\nBroth\nEgg\nSpring onion', 'pasta', 'Easy', 20, 'imgrecooked/pasta/simple ramen.png'),
	(@builtin_user_id, 'Spaghetti cacio e pepe', 'Spaghetti with pecorino and black pepper.', 'Spaghetti\nPecorino\nBlack pepper\nPasta water', 'pasta', 'Medium', 20, 'imgrecooked/pasta/spaghetti cacio pepe.png'),
	(@builtin_user_id, 'Banana chocolate chip cake', 'A moist banana cake with chocolate chips.', 'Bananas\nFlour\nSugar\nEggs\nChocolate chips', 'desserts', 'Easy', 25, 'imgrecooked/desserts/banana chocolate chip cake.png'),
	(@builtin_user_id, 'Brownies', 'Fudgy chocolate brownies.', 'Dark chocolate\nButter\nSugar\nEggs\nFlour', 'desserts', 'Easy', 40, 'imgrecooked/desserts/brownies.png'),
	(@builtin_user_id, 'Cherry pie', 'A classic pie filled with cherries.', 'Cherries\nPie dough\nSugar\nCornstarch', 'desserts', 'Easy', 15, 'imgrecooked/desserts/cherry pie.png'),
	(@builtin_user_id, 'Chocolate pudding dessert', 'A rich and creamy chocolate pudding dessert.', 'Chocolate\nMilk\nSugar\nCornstarch\nCream', 'desserts', 'Medium', 35, 'imgrecooked/desserts/chocolate pudding dessert.png'),
	(@builtin_user_id, 'Creme brulee', 'A smooth custard with a caramelized sugar top.', 'Cream\nEgg yolks\nSugar\nVanilla', 'desserts', 'Easy', 25, 'imgrecooked/desserts/creme brulee.png'),
	(@builtin_user_id, 'Fresh peach dessert', 'A fresh dessert made with ripe peaches.', 'Peaches\nCream\nSugar\nVanilla', 'desserts', 'Easy', 30, 'imgrecooked/desserts/fresh peach dessert.png'),
	(@builtin_user_id, 'Lemon ricotta dessert', 'A light lemon dessert with ricotta.', 'Ricotta\nLemon\nSugar\nEggs\nFlour', 'desserts', 'Easy', 25, 'imgrecooked/desserts/lemon ricotta dessert.png'),
	(@builtin_user_id, 'Pumpkin dessert', 'A warmly spiced pumpkin dessert.', 'Pumpkin\nCinnamon\nNutmeg\nSugar\nCream', 'desserts', 'Medium', 45, 'imgrecooked/desserts/pumpkin dessert.png'),
	(@builtin_user_id, 'Strawberry angel food', 'Light angel food cake with strawberries.', 'Angel food cake\nStrawberries\nCream\nSugar', 'desserts', 'Easy', 20, 'imgrecooked/desserts/strawberry angel food.png'),
	(@builtin_user_id, 'Strawberry twinkie', 'A sweet strawberry-filled snack cake.', 'Sponge cake\nStrawberries\nCream\nSugar', 'desserts', 'Medium', 20, 'imgrecooked/desserts/strawberry twinkie.png'),
	(@builtin_user_id, 'Air fried garlic butter steak', 'Steak finished with garlic butter in an air fryer.', 'Steak\nButter\nGarlic\nSalt\nBlack pepper', 'other', 'Easy', 25, 'imgrecooked/verschillende recepten/air fried garlic butter steak.png'),
	(@builtin_user_id, 'Campfire queso', 'A warm and cheesy campfire dip.', 'Cheese\nTomatoes\nChiles\nOnion', 'other', 'Easy', 40, 'imgrecooked/verschillende recepten/campfire queso.png'),
	(@builtin_user_id, 'Cheddar bay chicken', 'Chicken topped with cheddar and herb biscuits.', 'Chicken\nCheddar\nFlour\nButter\nHerbs', 'other', 'Easy', 15, 'imgrecooked/verschillende recepten/cheddar bay chicken.png'),
	(@builtin_user_id, 'Chicken gobbler', 'A hearty turkey-style chicken dinner.', 'Chicken\nStuffing\nGravy\nCranberry sauce', 'other', 'Medium', 35, 'imgrecooked/verschillende recepten/chicken_gobbler.png'),
	(@builtin_user_id, 'Crispy parmesan crusted roasted chicken', 'Roasted chicken with a crisp parmesan crust.', 'Chicken\nParmesan\nBreadcrumbs\nGarlic\nHerbs', 'other', 'Easy', 25, 'imgrecooked/verschillende recepten/crispy parmesan crusted roasted chicken.png'),
	(@builtin_user_id, 'Fried beef tacos', 'Crispy tacos filled with seasoned beef.', 'Ground beef\nTaco shells\nCheese\nLettuce\nTomatoes', 'other', 'Easy', 30, 'imgrecooked/verschillende recepten/fried beef tacos.png'),
	(@builtin_user_id, 'Georgia pot roast', 'Slow-cooked pot roast with vegetables.', 'Beef roast\nPotatoes\nCarrots\nOnion\nStock', 'other', 'Easy', 25, 'imgrecooked/verschillende recepten/georgia pot roast.png'),
	(@builtin_user_id, 'Green goddess salad', 'A fresh salad with a creamy herb dressing.', 'Lettuce\nAvocado\nCucumber\nHerbs\nYogurt', 'other', 'Medium', 45, 'imgrecooked/verschillende recepten/green goddess salad.png'),
	(@builtin_user_id, 'Parmesan crusted potato', 'Crispy potatoes with a parmesan crust.', 'Potatoes\nParmesan\nButter\nGarlic\nHerbs', 'other', 'Easy', 20, 'imgrecooked/verschillende recepten/parmesan crusted potato.png');
