<?php

session_start();

/* ----------------------- Part 1: Data Structure ----------------------- */
// Categories for the dropdown
$categories = ["Electronics", "Books", "Groceries", "Clothing", "Other"];

// Initialize multi-dimensional products array in session (persist during session)
if (!isset($_SESSION['products'])) {
    $_SESSION['products'] = [
        [
            'id' => 1,
            'name' => 'USB-C Cable',
            'description' => 'Fast-charging braided cable (1m).',
            'price' => 5.50,
            'category' => 'Electronics'
        ],
        [
            'id' => 2,
            'name' => 'Notebook A5',
            'description' => 'Lined paper, 100 pages.',
            'price' => 2.25,
            'category' => 'Books'
        ],
    ];
}
$products = $_SESSION['products'];

/* ----------------------- Part 2: Form Handling & Validation ----------------------- */
$errors = [];
$submittedData = [];

// Helper: sanitize scalar input
function clean($v)
{
    if (is_array($v))
        return $v;
    return trim($v ?? '');
}

// Handle POST (Add product)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1) Check submission + collect
    $submittedData = [
        'name' => clean($_POST['name'] ?? ''),
        'description' => clean($_POST['description'] ?? ''),
        'price' => clean($_POST['price'] ?? ''),
        'category' => clean($_POST['category'] ?? ''),
    ];

    // 2) Validate
    // name
    if ($submittedData['name'] === '') {
        $errors['name'] = 'Name is required';
    } elseif (mb_strlen($submittedData['name']) < 2) {
        $errors['name'] = 'Name must be at least 2 characters';
    }

    // description
    if ($submittedData['description'] === '') {
        $errors['description'] = 'Description is required';
    } elseif (mb_strlen($submittedData['description']) < 5) {
        $errors['description'] = 'Description must be at least 5 characters';
    }

    // price
    if ($submittedData['price'] === '') {
        $errors['price'] = 'Price is required';
    } elseif (!is_numeric($submittedData['price'])) {
        $errors['price'] = 'Price must be a number';
    } elseif ((float) $submittedData['price'] <= 0) {
        $errors['price'] = 'Price must be greater than 0';
    }

    // category
    global $categories;
    if ($submittedData['category'] === '') {
        $errors['category'] = 'Category is required';
    } elseif (!in_array($submittedData['category'], $categories, true)) {
        $errors['category'] = 'Invalid category';
    }

    // 3) If valid -> add product, set success flash, clear $submittedData
    if (empty($errors)) {
        // Generate unique id (max + 1)
        $newId = 1 + max(array_column($products, 'id'));

        $newProduct = [
            'id' => $newId,
            'name' => $submittedData['name'],
            'description' => $submittedData['description'],
            'price' => round((float) $submittedData['price'], 2),
            'category' => $submittedData['category'],
        ];

        // Add to products (session store)
        $products[] = $newProduct;
        $_SESSION['products'] = $products;

        // Flash success message (disappear after refresh)
        $_SESSION['flash_success'] = 'Product added successfully (ID: ' . $newId . ').';

        // Clear form data by redirect (PRG pattern)
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
}

/* Helper for safe echo */
function e($str)
{
    return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
}

// Helper for repopulating inputs from $submittedData (if validation failed)
function old($key, $default = '')
{
    global $submittedData;
    return isset($submittedData[$key]) ? e($submittedData[$key]) : e($default);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>PHP Product Inventory</title>

    <!-- Part 0: Bootstrap CDN (CSS + JS) -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- JS -->
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    <style>
        body {
            background: #f7f9fc;
        }

        .card {
            border-radius: 16px;
        }

        .table thead th {
            white-space: nowrap;
        }

        .price-cell {
            font-variant-numeric: tabular-nums;
        }
    </style>
</head>

<body>
    <h2 style="color:blue">Hello GitHub Test!</h2>

    <div class="container py-4">
        <div class="row g-4">
            <div class="col-12">
                <h1 class="h3 fw-bold mb-3">PHP Product Inventory</h1>
            </div>

            <!-- Messages (Part 3.1) -->
            <div class="col-12">
                <?php if (!empty($_SESSION['flash_success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= e($_SESSION['flash_success']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['flash_success']); ?>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        Please fix the errors below and submit again.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Table (Part 3.2) -->
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <strong>Product List</strong>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th class="text-end">Price (USD)</th>
                                        <th>Category</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $p): ?>
                                        <tr>
                                            <td><?= e($p['id']) ?></td>
                                            <td><?= e($p['name']) ?></td>
                                            <td><?= e($p['description']) ?></td>
                                            <td class="text-end price-cell"><?= number_format((float) $p['price'], 2) ?>
                                            </td>
                                            <td><?= e($p['category']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (!$products): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No products yet.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer text-muted small">
                        Total: <?= count($products) ?> product(s)
                    </div>
                </div>
            </div>

            <!-- Add Product Form (Part 3.3) -->
            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <strong>Add New Product</strong>
                    </div>
                    <div class="card-body">
                        <form method="post" novalidate>
                            <!-- Name -->
                            <div class="mb-3">
                                <label class="form-label" for="name">Name</label>
                                <input type="text" id="name" name="name"
                                    class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                    value="<?= old('name') ?>" placeholder="e.g., USB-C Cable">
                                <?php if (isset($errors['name'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['name']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Description -->
                            <div class="mb-3">
                                <label class="form-label" for="description">Description</label>
                                <textarea id="description" name="description" rows="3"
                                    class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                                    placeholder="Short details..."><?= old('description') ?></textarea>
                                <?php if (isset($errors['description'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['description']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Price -->
                            <div class="mb-3">
                                <label class="form-label" for="price">Price</label>
                                <input type="number" step="0.01" min="0.01" id="price" name="price"
                                    class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>"
                                    value="<?= old('price') ?>" placeholder="e.g., 9.99">
                                <?php if (isset($errors['price'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['price']) ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Category -->
                            <div class="mb-3">
                                <label class="form-label" for="category">Category</label>
                                <select id="category" name="category"
                                    class="form-select <?= isset($errors['category']) ? 'is-invalid' : '' ?>">
                                    <option value="">-- Select --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= e($cat) ?>" <?= old('category') === $cat ? 'selected' : '' ?>>
                                            <?= e($cat) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['category'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['category']) ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    Add Product
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-muted small">
                        * Uses server-side validation with Bootstrap styles.
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>