<?php
session_start();

/* =================================================
   Part 1: Data Structure & Initialization
   ================================================= */
if (!isset($_SESSION['products'])) {
    $_SESSION['products'] = [
        [
            'id' => 1,
            'name' => 'USB-C Cable',
            'description' => 'Fast-charging braided cable (1m).',
            'price' => 5.50,
            'category' => 'Electronics',
        ],
        [
            'id' => 2,
            'name' => 'Notebook A5',
            'description' => 'Lined paper, 100 pages.',
            'price' => 2.25,
            'category' => 'Books',
        ],
    ];
}
$products = $_SESSION['products'];
$categories = ["Electronics", "Books", "Groceries", "Clothing", "Other"];

// Helpers
function clean($v)
{
    return is_array($v) ? $v : trim($v ?? '');
}
function e($s)
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
// repopulate helper
$submittedData = [];
function old($key, $default = '')
{
    global $submittedData;
    return isset($submittedData[$key]) ? e($submittedData[$key]) : e($default);
}

// Flash helpers
function flash_set($key, $msg)
{
    $_SESSION[$key] = $msg;
}
function flash_get($key)
{
    $m = $_SESSION[$key] ?? '';
    unset($_SESSION[$key]);
    return $m;
}

// CSRF token (simple)
if (!isset($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

/* =================================================
   Extra (first): Delete Handler (before add/validate)
   ================================================= 
   */
$errors = [];
function product_index_by_id(array $arr, $id)
{
    foreach ($arr as $i => $row)
        if ((string) $row['id'] === (string) $id)
            return $i;
    return -1;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $errors['general'] = 'Invalid request.';
    } else {
        $idToDelete = clean($_POST['id'] ?? '');
        if ($idToDelete === '' || !ctype_digit($idToDelete)) {
            $errors['general'] = 'Invalid product id.';
        } else {
            $idx = product_index_by_id($products, (int) $idToDelete);
            if ($idx === -1) {
                $errors['general'] = 'Product not found.';
            } else {
                array_splice($products, $idx, 1);
                $_SESSION['products'] = $products;
                flash_set('flash_success', "Product #{$idToDelete} deleted.");
                header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?')); // PRG
                exit;
            }
        }
    }
}

/* =================================================
   Part 2: Form Handling & Validation (Add product)
   ================================================= 
   */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== 'delete') {
    // collect
    $submittedData = [
        'name' => clean($_POST['name'] ?? ''),
        'description' => clean($_POST['description'] ?? ''),
        'price' => clean($_POST['price'] ?? ''),
        'category' => clean($_POST['category'] ?? ''),
    ];

    // validate
    if ($submittedData['name'] === '')
        $errors['name'] = 'Name is required';
    elseif (mb_strlen($submittedData['name']) < 2)
        $errors['name'] = 'Name must be at least 2 characters';

    if ($submittedData['description'] === '')
        $errors['description'] = 'Description is required';
    elseif (mb_strlen($submittedData['description']) < 5)
        $errors['description'] = 'Description must be at least 5 characters';

    if ($submittedData['price'] === '')
        $errors['price'] = 'Price is required';
    elseif (!is_numeric($submittedData['price']))
        $errors['price'] = 'Price must be a number';
    elseif ((float) $submittedData['price'] <= 0)
        $errors['price'] = 'Price must be greater than 0';

    if ($submittedData['category'] === '')
        $errors['category'] = 'Category is required';
    elseif (!in_array($submittedData['category'], $categories, true))
        $errors['category'] = 'Invalid category';

    // success -> add
    if (empty($errors)) {
        $newId = $products ? (1 + max(array_column($products, 'id'))) : 1;
        $products[] = [
            'id' => $newId,
            'name' => $submittedData['name'],
            'description' => $submittedData['description'],
            'price' => round((float) $submittedData['price'], 2),
            'category' => $submittedData['category'],
        ];
        $_SESSION['products'] = $products;
        flash_set('flash_success', "Product added successfully (ID: {$newId}).");
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?')); // PRG
        exit;
    }
}

// Flash (for display)
$flash_success = flash_get('flash_success');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>PHP Product Inventory</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        crossorigin="anonymous">
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
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
    <div class="container py-4">
        <div class="row g-4">
            <div class="col-12">
                <h1 class="h3 fw-bold mb-3">PHP Product Inventory</h1>
            </div>

            <!-- Alerts -->
            <div class="col-12">
                <?php if (!empty($flash_success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= e($flash_success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= e($errors['general']) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors) && empty($errors['general'])): ?>
                    <div class="alert alert-danger" role="alert">
                        Please fix the errors below and submit again.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Table -->
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
                                        <th style="width:1%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($products)): ?>
                                        <?php foreach ($products as $p): ?>
                                            <tr>
                                                <td><?= e($p['id']) ?></td>
                                                <td><?= e($p['name']) ?></td>
                                                <td><?= e($p['description']) ?></td>
                                                <td class="text-end price-cell"><?= number_format((float) $p['price'], 2) ?></td>
                                                <td><?= e($p['category']) ?></td>
                                                <td>
                                                    <form method="post" class="d-inline"
                                                        onsubmit="return confirm('Delete this product?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                                                        <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>">
                                                        <button type="submit"
                                                            class="btn btn-sm btn-outline-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">No products yet.</td>
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

            <!-- Add Product Form -->
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
                                    <div class="invalid-feedback"><?= e($errors['name']) ?></div><?php endif; ?>
                            </div>

                            <!-- Description -->
                            <div class="mb-3">
                                <label class="form-label" for="description">Description</label>
                                <textarea id="description" name="description" rows="3"
                                    class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                                    placeholder="Short details..."><?= old('description') ?></textarea>
                                <?php if (isset($errors['description'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['description']) ?></div><?php endif; ?>
                            </div>

                            <!-- Price -->
                            <div class="mb-3">
                                <label class="form-label" for="price">Price</label>
                                <input type="number" step="0.01" min="0.01" id="price" name="price"
                                    class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>"
                                    value="<?= old('price') ?>" placeholder="e.g., 9.99">
                                <?php if (isset($errors['price'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['price']) ?></div><?php endif; ?>
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
                                    <div class="invalid-feedback"><?= e($errors['category']) ?></div><?php endif; ?>
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="action" value="add" class="btn btn-primary">Add
                                    Product</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-muted small">
                        * Server-side validation with Bootstrap styles.
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>