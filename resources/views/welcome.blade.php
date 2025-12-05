<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>EEC - Learning Management System</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased font-sans bg-gray-50 min-h-screen flex flex-col">

    <!-- Main Content -->
    <div class="flex-1 flex flex-col lg:flex-row items-center justify-center lg:justify-between">

        <!-- Left: Text & Buttons -->
        <div class="px-8 lg:px-24 py-20 text-center lg:text-left max-w-3xl lg:max-w-2xl z-10">
            <h1 class="text-5xl lg:text-7xl font-bold text-gray-900 leading-tight">
                Welcome to <br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-600 to-orange-500">
                    EEC Learning
                </span>
            </h1>
            <p class="mt-6 text-xl lg:text-2xl text-gray-700 font-medium">
                Empowering Education Through Excellence & Innovation
            </p>
            <p class="mt-4 text-lg text-gray-600 max-w-2xl">
                A modern Learning Management System designed for institutions, teachers, and students to collaborate, learn, and grow — seamlessly.
            </p>

            <div class="mt-12 flex flex-col sm:flex-row gap-10 justify-center lg:justify-start">
                <a href="{{ route('login') }}"
                   class="inline-flex items-center justify-center px-10 py-5 bg-orange-600 hover:bg-orange-700 text-white font-semibold text-lg rounded-xl shadow-lg hover:shadow-xl transform hover:scale-105 transition-all duration-300">
                    Login to Dashboard
                </a>

                <a href="#about"
                   class="inline-flex items-center justify-center px-12 py-5 border-2 border-orange-600 text-orange-600 hover:bg-orange-600 hover:text-white font-semibold text-lg rounded-xl transition-all duration-300">
                    Learn More About EEC
                </a>
            </div>
        </div>

        <!-- Right: Beautiful Orange Geometric Art (Kept from your original) -->
        <div class="hidden lg:flex lg:w-1/2 xl:w-3/5 h-screen items-center justify-center relative overflow-hidden bg-gradient-to-br from-orange-50 to-amber-50">
            <div class="absolute inset-0 flex items-center justify-center">
                <svg class="w-full max-w-3xl h-auto" viewBox="0 0 450 450" xmlns="http://www.w3.org/2000/svg">
                    <g style="mix-blend-mode: hard-light">
                        <!-- Your original beautiful orange paths (kept exactly) -->
                        <path d="M464.198 304.708L435.375 254.789L377.307 355.366L406.13 405.285L464.198 304.708Z" stroke="#FF750F" stroke-width="1.5"/>
                        <path d="M353.209 254.787C366.68 242.548 376.618 232.22 383.023 223.805L324.955 324.382C318.55 332.797 308.612 343.124 295.141 355.364L353.209 254.787Z" stroke="#FF750F" stroke-width="1.5"/>
                        <path d="M435.37 254.787L353.212 254.784L295.144 355.361L377.302 355.364L435.37 254.787Z" stroke="#FF750F" stroke-width="1.5"/>
                        <path d="M183.921 154.947L248.521 154.95L190.453 255.527L125.853 255.524L183.921 154.947Z" stroke="#FF750F" stroke-width="1.5"/>
                        <path d="M246.544 254.79L246.875 254.79C253.722 247.905 264.046 238.82 277.849 227.537C291.21 216.253 301.259 207.264 307.995 200.57C314.62 193.685 319.147 186.418 321.577 178.768C324.006 171.117 322.846 163.18 318.097 154.956C312.796 145.775 305.342 138.412 295.735 132.865C286.238 127.127 276.189 124.258 265.588 124.257C255.208 124.257 248.416 127.03 245.214 132.576C241.902 137.931 243.006 145.39 248.528 154.953L183.928 154.951C174.652 138.885 170.676 124.541 172 111.918C173.546 99.2946 179.84 89.5408 190.882 82.6559C202.035 75.5798 216.887 72.0421 235.439 72.0428C254.874 72.0435 274.144 75.5825 293.248 82.6598C312.242 89.5457 329.579 99.3005 345.261 111.924C360.942 124.548 373.421 138.892 382.697 154.958C391.311 169.877 395.121 182.978 394.128 194.262C393.355 205.546 389.656 215.396 383.031 223.811C376.627 232.226 366.688 242.554 353.217 254.794L435.375 254.797L464.198 304.716L275.367 304.709L246.544 254.79Z" fill="#FF750F" opacity="0.15"/>
                        <path d="M67.41 125.402L44.5515 125.401L15.5625 75.1953L101.364 75.1985L233.886 304.712L170.942 304.71L67.41 125.402Z" fill="#FF750F" opacity="0.2"/>
                        <!-- Add more paths if you want fuller design -->
                    </g>
                </svg>
            </div>
        </div>
    </div>

    <!-- About Section (Scroll Target) -->
    <section id="about" class="py-20 px-8 bg-white">
        <div class="max-w-4xl mx-auto text-center">
            <h2 class="text-4xl lg:text-5xl font-bold text-gray-900 mb-8">About EEC Learning</h2>
            <p class="text-lg lg:text-xl text-gray-700 leading-relaxed">
                <strong>EEC Learning Management System</strong> is a powerful, intuitive platform built for modern education.
                From course management and student tracking to interactive quizzes and progress analytics — we provide everything institutions need to deliver exceptional learning experiences.
            </p>
            <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-8 text-left">
                <div class="bg-orange-50 p-6 rounded-2xl">
                    <h3 class="text-xl font-semibold text-orange-700">For Teachers</h3>
                    <p class="mt-3 text-gray-700">Create courses, assign tasks, track progress, and engage students seamlessly.</p>
                </div>
                <div class="bg-orange-50 p-6 rounded-2xl">
                    <h3 class="text-xl font-semibold text-orange-700">For Students</h3>
                    <p class="mt-3 text-gray-700">Access lessons, submit assignments, view grades, and learn at your own pace.</p>
                </div>
                <div class="bg-orange-50 p-6 rounded-2xl">
                    <h3 class="text-xl font-semibold text-orange-700">For Admins</h3>
                    <p class="mt-3 text-gray-700">Manage users, generate reports, and ensure smooth operations across the board.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-10 text-center">
        <p class="text-lg">&copy; {{ date('Y') }} EEC Learning Management System. All rights reserved.</p>
        <p class="mt-2 text-gray-400">Built with Laravel & Love</p>
    </footer>

</body>
</html>