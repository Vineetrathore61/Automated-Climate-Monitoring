<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-r from-green-400 to-teal-500 min-h-screen flex items-center justify-center">

    <div class="bg-white p-8 rounded-lg shadow-lg w-96">
        <h2 class="text-2xl font-bold mb-6 text-center">Register</h2>

        @if ($errors->any())
            <div class="mb-4 text-red-600 text-center">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div class="mb-4">
                <input type="text" name="name" placeholder="Name" required
                    class="w-full p-3 rounded border focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>

            <div class="mb-4">
                <input type="email" name="email" placeholder="Email" required
                    class="w-full p-3 rounded border focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>

            <div class="mb-4">
                <input type="password" name="password" placeholder="Password" required
                    class="w-full p-3 rounded border focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>

            <div class="mb-6">
                <input type="password" name="password_confirmation" placeholder="Confirm Password" required
                    class="w-full p-3 rounded border focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>

            <button type="submit"
                class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold p-3 rounded transition duration-300">
                Register
            </button>
        </form>

        <p class="text-center text-sm text-gray-600 mt-4">
            Already have an account?
            <a href="{{ route('login') }}" class="text-green-500 hover:underline">Login</a>
        </p>
    </div>

</body>
</html>
