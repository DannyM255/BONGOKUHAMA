<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Result</title>
    <!-- Include any necessary CSS and JS -->
</head>
<body>
    <div class="container">
        <h1>Payment Result</h1>
        <div class="result">
            <?php if (isset($result)): ?>
                <p><?php echo $result; ?></p>
            <?php else: ?>
                <p>No result available.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
