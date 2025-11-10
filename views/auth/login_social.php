<div class="mb-6 text-center">
    <p class="text-gray-600 text-sm mb-2">Puedes iniciar sesi贸n o registrarte con tu cuenta de Google o Facebook:</p>
    <a href="<?= url('/googleauth/login') ?>"
       class="flex items-center justify-center gap-2 w-full bg-white border border-gray-300 hover:bg-gray-100 text-gray-800 font-semibold py-2 px-4 rounded shadow transition duration-150 mb-2">
        <img src="https://developers.google.com/identity/images/g-logo.png" alt="Google" class="h-5 w-5">
        <span>Continuar con Google</span>
    </a>
    <a href="<?= url('/facebookauth/login') ?>"
       class="flex items-center justify-center gap-2 w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded shadow transition duration-150 mb-2">
        <img src="https://upload.wikimedia.org/wikipedia/commons/0/05/Facebook_Logo_%282019%29.png" alt="Facebook" class="h-5 w-5 bg-white rounded-full p-0.5">
        <span>Continuar con Facebook</span>
    </a>
</div>
<!-- 🔽 AGREGA ESTE BLOQUE DE SEPARADOR AQUÍ -->
<div class="flex items-center justify-center my-4">
    <hr class="flex-grow border-gray-300">
    <span class="mx-3 text-gray-500 text-sm">o</span>
    <hr class="flex-grow border-gray-300">
</div>