# Nutrition Facts Label App

A web-based tool for studying real branded food products using data from the USDA FoodData Central database and Open Food Facts. It will randomly select a real label with photo among almost 2 million different real products. Designed as a learning resource for nutrition literacy.

---

## What it does

- Loads a random branded food product on each click
- Displays an accurate, standards-compliant FDA Nutrition Facts label
- Shows the product photo (sourced from Open Food Facts), linked to the full product page
- Lists all ingredients
- Optionally highlights nutrients and ingredients of concern using colour-coded warnings, based on peer-reviewed classification criteria

---

## Features

### Nutrition Facts Label
- Rendered following official FDA guidelines (vertical standard format)
- Serving size, calories, macronutrients, micronutrients, and % Daily Values (FDA 2020 reference values)
- Missing values displayed as `—` rather than zero

### Checks and Warnings
When the **"Show warnings and checks"** checkbox is ticked:

**Nutrients** are highlighted:

| Colour | Meaning |
|--------|---------|
| 🚩 Red | Nutrient to limit is **high** (> 20% DV per serving) |
| ⚠️ Orange | Beneficial nutrient is **low** (< 5% DV per serving) |
| ✅ Green | Beneficial nutrient is high, or nutrient to limit is low |

**Ingredients** are highlighted inline:

| Colour | Meaning |
|--------|---------|
| 🚩 Red background | Strong ultra-processed marker (industrial fats, artificial colours, preservatives, artificial sweeteners) |
| ⚠️ Orange background | Notable additive (added sugars, flavour enhancers, emulsifiers, texture agents) |

Hover over any highlighted ingredient to see the specific concern. Unknown ingredients are left unmarked.

### Ingredient classification sources
- **NOVA Group 4** — Monteiro CA et al. (2019). *Ultra-processed foods: what they are and how to identify them.* Public Health Nutrition, 22(5), 936–941. https://doi.org/10.1017/S1368980018003762
- **FAO/WHO Codex Alimentarius** — General Standard for Food Additives (GSFA). https://www.fao.org/fao-who-codexalimentarius/codex-texts/dbs/gsfa/en/

---

## Data sources

| Source | Licence | Used for |
|--------|---------|---------|
| [USDA FoodData Central](https://fdc.nal.usda.gov) | Public domain, CC0 1.0 | Nutritional data, ingredients, serving sizes |
| [Open Food Facts](https://world.openfoodfacts.org) | CC BY-SA 3.0 | Product photos |

Only products that have a verified photo in Open Food Facts are served.

---

## Technical stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3, Vanilla JavaScript (no frameworks) |
| Backend | PHP 8 |
| Database | MySQL — pre-processed subset of USDA FoodData Central |
| Data pipeline | Python 3 (`usda_to_sql.py`) |

### Database tables
- `food` — product identifiers and descriptions
- `branded_food` — brand, ingredients, serving info
- `food_nutrient_flat` — one row per product with all key nutrients pre-joined
- `off_verified` — Open Food Facts GTIN → image URL mapping (filtered to non-null images only)

### Key files

```
index.html          Main application page
style.css           All styles (label, highlights, legend, layout)
api.php             JSON endpoint — returns one random or specific product
db_config.php       Database credentials
usda_to_sql.py      Data pipeline: USDA CSV → MySQL
.htaccess           Redirects and cache headers
```

---

## Licence

Application code: [CC BY-SA 4.0](https://creativecommons.org/licenses/by-sa/4.0/)

Generated with [Perplexity](https://www.perplexity.ai/) and [Claude Sonnet 4.6](https://www.anthropic.com/news/claude-sonnet-4-6)

**Task 4. 0955.26: El enfoque metodológico AICLE/CLIL en Educación Infantil y Primaria: curso básico.**
por **Ramón Cutanda López**, 2026
