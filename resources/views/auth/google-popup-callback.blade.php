<!DOCTYPE html>
<html>
<head>
    <title>Procesando autenticación...</title>
</head>
<body>
<script>
    // Envía el JSON al opener (ventana principal) solo si existe
    const authData = @json($authData);
    if (window.opener) {
        window.opener.postMessage(authData, 'https://backendgeiico-production-26e5.up.railway.app');
        window.close();
    } else {
        document.body.innerText = "Autenticación completada. Puedes cerrar esta ventana.";
    }
</script>
</body>
</html>