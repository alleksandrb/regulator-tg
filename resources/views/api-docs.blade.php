<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Regulator TG API Docs</title>
    <style>
        body { margin: 0; padding: 0; }
        .redoc-container { height: 100vh; }
        .topbar { display: none; }
    </style>
    <link rel="icon" href="/favicon.ico" />
    <script src="https://cdn.redoc.ly/redoc/latest/bundles/redoc.standalone.js"></script>
</head>
<body>
    <div id="redoc-container" class="redoc-container"></div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Redoc.init('/openapi.yaml', {
                hideDownloadButton: false,
                expandResponses: '200,201,4xx',
                hideHostname: true,
            }, document.getElementById('redoc-container'));
        });
    </script>
</body>
</html>

