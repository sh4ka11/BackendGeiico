<!-- filepath: c:\xampp\htdocs\BDGeiico\resources\views\auth\native-callback.blade.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Autenticación Google</title>
</head>
<body>
<script>
    // Envía los datos al proceso principal de NativePHP o maneja la redirección
    window.onload = function() {
        const authData = @json($authData);
        if (authData.token) {
            // Ejemplo: guardar en localStorage y redirigir
            localStorage.setItem('auth_token', authData.token);
            window.location.href = '/dashboard';
        } else if (authData.error) {
            alert(authData.error);
            window.location.href = '/login';
        }
        // Si usas NativePHP, aquí puedes comunicar con el proceso principal
    };
</script>
</body>
</html>