<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - <?= config('app.name') ?></title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 2rem; }
        .container { max-width: 1200px; margin: auto; background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .btn { display: inline-block; padding: 0.6rem 1.2rem; border-radius: 5px; text-decoration: none; color: #fff; font-weight: 500; transition: background-color 0.3s; }
        .btn-primary { background-color: #007bff; }
        .btn-primary:hover { background-color: #0056b3; }
        .btn-warning { background-color: #ffc107; color: #212529; }
        .btn-warning:hover { background-color: #e0a800; }
        .btn-danger { background-color: #dc3545; border: none; cursor: pointer; font-family: inherit; font-size: inherit; }
        .btn-danger:hover { background-color: #c82333; }
        table { width: 100%; border-collapse: collapse; margin-top: 2rem; }
        th, td { padding: 1rem; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
        .actions form { display: inline-block; margin-left: 0.5rem; }
        .header { display: flex; justify-content: space-between; align-items: center; }
        .header-nav a { margin-left: 1rem; color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Manage Courses</h1>
            <div>
                <a href="/courses/create" class="btn btn-primary">Create New Course</a>
                <a href="/logout" class="header-nav">Logout</a>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($courses)): ?>
                    <?php foreach ($courses as $course): ?>
                        <tr>
                            <td><?= htmlspecialchars($course->title) ?></td>
                            <td><?= htmlspecialchars(substr($course->description, 0, 100)) . '...' ?></td>
                            <td><?= $course->category() ? htmlspecialchars($course->category()->name) : 'N/A' ?></td>
                            <td class="actions">
                                <a href="/courses/edit/<?= $course->id ?>" class="btn btn-warning">Edit</a>
                                <form action="/courses/delete/<?= $course->id ?>" method="POST">
                                    <input type="hidden" name="_method" value="POST">
                                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this course?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No courses found. <a href="/courses/create">Create one now</a>.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>