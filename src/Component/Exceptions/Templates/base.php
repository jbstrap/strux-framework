<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <meta http-equiv="x-ua-compatible" content="ie=edge"/>
    <title>Error | <?= $data['title'] ?? 'An error occurred' ?></title>
    <link rel="icon" href="<?= asset("images/logo.webp") ?>" type="image/x-icon"/>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="<?= asset("css/bootstrap.css") ?>"/>
    <link rel="stylesheet" href="<?= asset("css/styles.css") ?>"/>
</head>
<body data-bs-theme="dark">
<?= $content ?>
<script src="<?= asset("js/bootstrap.bundle.js") ?>"></script>
</body>
</html>