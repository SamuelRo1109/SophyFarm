document.getElementById('login-form').addEventListener('submit', function(event) {
    event.preventDefault();

    // Obtener los valores del formulario
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;

    // Hacer la solicitud POST al backend
    fetch('http://localhost/proyecto_cotizaciones_microservicios/backend/login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            username: username,
            password: password,
        }),
    })
    .then(response => {
        console.log('Respuesta del servidor:', response);

        if (!response.ok) {
            throw new Error('Error en la solicitud: ' + response.statusText);
        }

        return response.json();  // Intentamos convertir la respuesta en JSON
    })
    .then(data => {
        console.log('Datos recibidos:', data);

        // Verificar si el login fue exitoso
        if (data.message === 'Login exitoso') {
            // Redirigir al dashboard o página principal
            window.location.href = '/dashboard.html';  // O la página que desees
        } else {
            alert(data.message);  // Mostrar el mensaje de error
        }
    })
    .catch(error => {
        console.error('Error en la solicitud:', error);
        alert('Hubo un problema con la solicitud de login. Intenta de nuevo.');
    });
});
