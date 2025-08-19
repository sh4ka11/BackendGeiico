<!DOCTYPE html>
<html>
<head>
    <title>Procesando autenticación...</title>
</head>
<body>
<script>
    // Envía el JSON al opener (ventana principal)
    window.opener.postMessage(@json($authData), 'https://backendgeiico-production-26e5.up.railway.app');
    window.close();
</script>
</body>
</html>