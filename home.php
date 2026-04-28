<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrackIt | Smart Expense Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Custom smooth animation for the infinite scroll instead of old marquee */
        @keyframes scroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .animate-scroll {
            display: flex;
            width: 200%;
            animation: scroll 30s linear infinite;
        }
        .hero-gradient {
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.15), transparent),
                        radial-gradient(circle at bottom left, rgba(79, 70, 229, 0.1), transparent);
        }
    </style>
</head>
<body class="bg-slate-950 text-white font-sans antialiased hero-gradient min-h-screen">

    <nav class="flex items-center justify-between px-8 py-6 max-w-7xl mx-auto">
        <div class="flex items-center gap-2.5 group cursor-pointer">
            <div class="relative w-8 h-8 flex-shrink-0">
                <div class="absolute inset-1 bg-gradient-to-tr from-indigo-600 to-indigo-400 rounded-md rotate-3 group-hover:rotate-6 transition-transform duration-300 opacity-80"></div>
                <div class="relative w-8 h-8 bg-white rounded-md flex items-center justify-center border border-slate-200 shadow-sm">
                    <svg viewBox="0 0 24 24" fill="none" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 7h12v3H6z" fill="#4338ca"/>
                        <path d="M10 10h4v8h-4z" fill="#6366f1"/>
                    </svg>
                </div>
            </div>
            <div class="flex flex-col leading-none">
                <span class="text-lg font-black text-white tracking-tight uppercase">Track<span class="text-indigo-400">it</span></span>
                <span class="text-[7px] font-bold text-slate-500 uppercase tracking-[0.4em] mt-0.5">Track you</span>
            </div>
        </div>

        <div class="hidden md:flex items-center gap-8 text-sm font-medium text-slate-400">
           <!--  <a href="#" class="hover:text-indigo-400 transition">Solutions</a>
            <a href="#" class="hover:text-indigo-400 transition">Features</a>
            <a href="#" class="hover:text-indigo-400 transition">Pricing</a> -->
            <a href="auth.php" class="text-white bg-indigo-600 px-5 py-2.5 rounded-xl font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-500/20">
                Login / Register
            </a>
        </div>
    </nav>

    <section class="max-w-7xl mx-auto px-8 py-20 text-center">
        
        <h1 class="text-5xl md:text-7xl font-black mb-6 tracking-tight">
            Smart Expense <br>
            <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-cyan-400">Tracker for Pros.</span>
        </h1>
        <p class="text-slate-400 text-lg max-w-2xl mx-auto mb-10 leading-relaxed">
            Track your daily spending, analyze financial habits, and manage your money with a beautiful, high-performance dashboard.
        </p>
        <div class="flex flex-wrap justify-center gap-4">
            <a href="auth.php" class="px-8 py-4 bg-white text-slate-950 rounded-2xl font-black text-lg hover:bg-indigo-50 transition-colors shadow-xl">
                Get Started Here
            </a>
            
        </div>
    </section>

    <div class="overflow-hidden relative py-10">
        <div class="animate-scroll gap-6">
            <img src="https://images.unsplash.com/photo-1554224154-26032ffc0d07" class="h-48 w-80 object-cover rounded-3xl border border-slate-800 opacity-60 hover:opacity-100 transition grayscale hover:grayscale-0">
            <img src="https://images.unsplash.com/photo-1563013544-824ae1b704d3" class="h-48 w-80 object-cover rounded-3xl border border-slate-800 opacity-60 hover:opacity-100 transition grayscale hover:grayscale-0">
            <img src="https://images.unsplash.com/photo-1554224155-8d04cb21cd6c" class="h-48 w-80 object-cover rounded-3xl border border-slate-800 opacity-60 hover:opacity-100 transition grayscale hover:grayscale-0">
            <img src="https://images.unsplash.com/photo-1554224154-22dec7ec8818" class="h-48 w-80 object-cover rounded-3xl border border-slate-800 opacity-60 hover:opacity-100 transition grayscale hover:grayscale-0">
            <img src="https://images.unsplash.com/photo-1554224154-26032ffc0d07" class="h-48 w-80 object-cover rounded-3xl border border-slate-800 opacity-60 hover:opacity-100 transition grayscale hover:grayscale-0">
            <img src="https://images.unsplash.com/photo-1563013544-824ae1b704d3" class="h-48 w-80 object-cover rounded-3xl border border-slate-800 opacity-60 hover:opacity-100 transition grayscale hover:grayscale-0">
        </div>
        <div class="absolute inset-y-0 left-0 w-32 bg-gradient-to-r from-slate-950 to-transparent z-10"></div>
        <div class="absolute inset-y-0 right-0 w-32 bg-gradient-to-l from-slate-950 to-transparent z-10"></div>
    </div>

    <section class="max-w-7xl mx-auto px-8 py-24 grid grid-cols-1 md:grid-cols-2 gap-8">
    
    <div class="p-8 bg-slate-900/50 border border-slate-800 rounded-[2.5rem] hover:border-indigo-500/50 transition-all group">
        <div class="w-12 h-12 bg-indigo-500/10 rounded-2xl flex items-center justify-center text-indigo-400 mb-6 group-hover:bg-indigo-500 group-hover:text-white transition-all shadow-inner">
            <i class="fa-solid fa-chart-pie text-xl"></i>
        </div>
        <h3 class="text-xl font-bold mb-3">Real-time Analytics</h3>
        <p class="text-slate-400 text-sm leading-relaxed">
            Get instant clarity on your net worth. Visualize spending patterns with interactive 
            breakdowns that help you save more every month.
        </p>
    </div>

    <div class="p-8 bg-slate-900/50 border border-slate-100/10 rounded-[2.5rem] hover:border-emerald-500/50 transition-all group relative overflow-hidden">
        <div class="absolute -right-4 -top-4 w-24 h-24 bg-emerald-500/10 blur-3xl group-hover:bg-emerald-500/20 transition-all"></div>
        
        <div class="w-12 h-12 bg-emerald-500/10 rounded-2xl flex items-center justify-center text-emerald-400 mb-6 group-hover:bg-emerald-500 group-hover:text-white transition-all">
            <i class="fa-solid fa-vault text-xl"></i>
        </div>
        <h3 class="text-xl font-bold mb-3 text-white">Bank-Grade Security</h3>
        <p class="text-slate-400 text-sm leading-relaxed">
            Your financial privacy is our priority. We use 256-bit encryption to ensure 
            your transaction data remains for your eyes only.
        </p>
    </div>

    

</section>

    <footer class="border-t border-slate-900 py-10 text-center text-slate-500 text-sm">
        <p>&copy; 2026 TrackIt. All rights reserved.</p>
    </footer>

</body>
</html>	