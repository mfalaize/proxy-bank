<!-- HTML for static distribution bundle build -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ProxyBank API</title>
    <link rel="stylesheet" type="text/css"
          href="/assets/swagger-ui/swagger-ui.css">
    <link rel="icon" type="image/png" href="/assets/swagger-ui/favicon-32x32.png" sizes="32x32"/>
    <link rel="icon" type="image/png" href="/assets/swagger-ui/favicon-16x16.png" sizes="16x16"/>
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }

        *,
        *:before,
        *:after {
            box-sizing: inherit;
        }

        body {
            margin: 0;
            background: #fafafa;
        }
    </style>
</head>

<body>
<div id="swagger-ui"></div>

<script src="/assets/swagger-ui/swagger-ui-bundle.js"></script>
<script src="/assets/swagger-ui/swagger-ui-standalone-preset.js"></script>
<script>
  window.onload = function () {
    // Begin Swagger UI call region
    const ui = SwaggerUIBundle({
      url: "<?=$swaggerUrl?>",
      dom_id: '#swagger-ui',
      deepLinking: true,
      presets: [
        SwaggerUIBundle.presets.apis,
        SwaggerUIStandalonePreset
      ],
      plugins: [
        SwaggerUIBundle.plugins.DownloadUrl
      ],
      layout: 'BaseLayout'
    });
    // End Swagger UI call region

    window.ui = ui;
  };
</script>
</body>
</html>
