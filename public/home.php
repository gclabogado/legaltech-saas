<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tu Estudio Juridico - Marketplace Legal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; } h1 { font-family: 'Playfair Display', serif; }</style>
</head>
<body class="bg-white text-gray-900 h-screen flex flex-col">

    <nav class="flex justify-between items-center p-6 pb-2">
        <div class="text-2xl font-bold tracking-tighter">Tu Estudio Juridico</div>
    </nav>

    <main class="flex-grow flex flex-col items-center justify-center px-6 text-center space-y-8 max-w-md mx-auto w-full">

        <div class="space-y-2">
            <h1 class="text-4xl leading-tight">Justicia,<br><span class="text-blue-900">simplificada.</span></h1>
            <p class="text-gray-500">¿Eres profesional o necesitas uno?</p>
        </div>

        <div class="w-full space-y-4">
            <a href="/login-google?role=cliente" class="group block w-full border-2 border-blue-900 rounded-xl p-4 transition hover:bg-blue-50">
                <div class="flex items-center justify-between">
                    <div class="text-left">
                        <span class="block text-xs text-gray-500 uppercase tracking-widest font-bold">Cliente</span>
                        <span class="text-xl font-bold text-blue-900">Busco Abogado</span>
                    </div>
                    <svg class="w-6 h-6 text-blue-900" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </a>

            <a href="/login-google?role=abogado" class="block w-full bg-blue-900 text-white rounded-xl p-4 shadow-lg hover:bg-blue-800 transition transform active:scale-95">
                <div class="flex items-center justify-between">
                    <div class="text-left">
                        <span class="block text-xs text-blue-200 uppercase tracking-widest font-bold">Profesional</span>
                        <span class="text-xl font-bold flex items-center gap-2">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12.545,10.239v3.821h5.445c-0.712,2.315-2.647,3.972-5.445,3.972c-3.332,0-6.033-2.701-6.033-6.032s2.701-6.032,6.033-6.032c1.498,0,2.866,0.549,3.921,1.453l2.814-2.814C17.503,2.988,15.139,2,12.545,2C7.021,2,2.543,6.477,2.543,12s4.478,10,10.002,10c8.396,0,10.249-7.85,9.426-11.748L1>
                            Soy Abogado
                        </span>
                    </div>
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </div>
            </a>
        </div>
    </main>

    <footer class="p-6 text-center text-xs text-gray-400">&copy; 2026 Tu Estudio Juridico</footer>
</body>
</html>
