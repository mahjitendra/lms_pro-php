<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course - <?= config('app.name') ?></title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 2rem; }
        .container { max-width: 800px; margin: auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .form-group { margin-bottom: 1.5rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        input[type="text"], textarea, select { width: 100%; padding: 0.8rem; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; font-family: inherit; }
        textarea { resize: vertical; min-height: 120px; }
        .btn { display: inline-block; padding: 0.8rem 1.5rem; border-radius: 5px; text-decoration: none; color: #fff; border: none; cursor: pointer; font-weight: 500; }
        .btn-primary { background-color: #007bff; }
        .btn-primary:hover { background-color: #0056b3; }
        .btn-secondary { background-color: #6c757d; text-align: center; }
        .btn-secondary:hover { background-color: #5a6268; }
        .form-actions { margin-top: 2rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Course: <?= htmlspecialchars($course->title) ?></h1>

        <form action="/courses/update/<?= $course->id ?>" method="POST">
            <div class="form-group">
                <label for="title">Course Title</label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($course->title) ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Course Description</label>
                <textarea id="description" name="description" required><?= htmlspecialchars($course->description) ?></textarea>
            </div>

            <div class="form-group">
                <label for="category_id">Category</label>
                <select id="category_id" name="category_id">
                    <option value="">Select a category</option>
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category->id ?>" <?= ($course->category_id == $category->id) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category->name) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Update Course</button>
                <a href="/courses" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>