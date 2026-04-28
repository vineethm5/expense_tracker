<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_query = $conn->query("SELECT username FROM users WHERE id = $user_id");
$user_data = $user_query->fetch_assoc();
$username = $user_data['username'] ?? 'User';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrackIt | Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Smooth transition for sidebar width */
        #sidebar { transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .nav-text { transition: opacity 0.2s ease; }
        .collapsed .nav-text { display: none; opacity: 0; }
        .collapsed .logo-text { display: none; }
        .collapsed { width: 80px !important; }
    </style>
</head>
<body class="bg-[#f8fafc] font-sans antialiased text-slate-900 overflow-hidden">

    <div class="flex h-screen">
        
        <aside id="sidebar" class="w-64 bg-slate-900 text-white hidden md:flex flex-col border-r border-slate-800 relative">
            
            <button onclick="toggleSidebar()" class="absolute -right-3 top-20 bg-indigo-600 text-white w-6 h-6 rounded-full flex items-center justify-center border-2 border-white shadow-sm z-50 hover:bg-indigo-700 transition-colors">
                <i id="toggleIcon" class="fa-solid fa-chevron-left text-[10px]"></i>
            </button>

            <div class="p-6 overflow-hidden">
                <div class="flex items-center gap-2.5 group cursor-pointer min-w-[200px]">
                    <div class="relative w-7 h-7 flex-shrink-0">
                        <div class="absolute inset-1 bg-gradient-to-tr from-indigo-600 to-indigo-400 rounded-md rotate-3 opacity-80"></div>
                        <div class="relative w-7 h-7 bg-white rounded-md flex items-center justify-center border border-slate-200">
                            <svg viewBox="0 0 24 24" fill="none" class="w-4 h-4" xmlns="http://www.w3.org/2000/svg">
                                <path d="M6 7h12v3H6z" fill="#4338ca"/>
                                <path d="M10 10h4v8h-4z" fill="#6366f1"/>
                            </svg>
                        </div>
                    </div>
                    <div class="flex flex-col leading-none logo-text">
                        <span class="text-base font-black text-white tracking-tight uppercase">Track<span class="text-indigo-400">it</span></span>
                        <span class="text-[7px] font-bold text-slate-500 uppercase tracking-[0.4em] mt-0.5">Premium</span>
                    </div>
                </div>
            </div>

            <nav class="flex-1 px-4 space-y-2 mt-4 overflow-hidden">
                <a href="dashboard.php" target="contentFrame" onclick="setActive(this)" class="nav-link flex items-center gap-4 px-4 py-3 bg-indigo-600 rounded-xl text-white font-medium transition-all">
                    <i class="fa-solid fa-house flex-shrink-0 w-5"></i> 
                    <span class="nav-text whitespace-nowrap">Dashboard</span>
                </a>
                <a href="transactions.php" target="contentFrame" onclick="setActive(this)" class="nav-link flex items-center gap-4 px-4 py-3 text-slate-400 hover:bg-slate-800 hover:text-white rounded-xl transition-all">
                    <i class="fa-solid fa-list-ul flex-shrink-0 w-5"></i> 
                    <span class="nav-text whitespace-nowrap">Transactions</span>
                </a>
            </nav>

            <div class="p-4 border-t border-slate-800 overflow-hidden">
                <a href="logout.php" class="flex items-center gap-4 px-4 py-3 text-red-400 hover:bg-red-500/10 rounded-xl transition font-medium">
                    <i class="fa-solid fa-arrow-right-from-bracket flex-shrink-0 w-5"></i> 
                    <span class="nav-text">Logout</span>
                </a>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-8 z-10">
                <div class="flex items-center gap-4">
                    <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-widest">Financial Portal</h2>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-bold text-slate-800 leading-none"><?php echo htmlspecialchars($username); ?></p>
                        <span class="text-[10px] text-green-500 font-bold uppercase tracking-tighter">Active Account</span>
                    </div>
                    <div class="h-9 w-9 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm">
                        <?php echo strtoupper(substr($username, 0, 1)); ?>
                    </div>
                </div>
            </header>

            <iframe name="contentFrame" src="dashboard.php" class="w-full h-full border-none no-scrollbar"></iframe>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const icon = document.getElementById('toggleIcon');
            
            sidebar.classList.toggle('collapsed');
            
            // Flip the chevron icon
            if(sidebar.classList.contains('collapsed')) {
                icon.classList.replace('fa-chevron-left', 'fa-chevron-right');
            } else {
                icon.classList.replace('fa-chevron-right', 'fa-chevron-left');
            }
        }

        function setActive(el) {
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('bg-indigo-600', 'text-white');
                link.classList.add('text-slate-400', 'hover:bg-slate-800');
            });
            el.classList.add('bg-indigo-600', 'text-white');
            el.classList.remove('text-slate-400', 'hover:bg-slate-800');
        }
    </script>
</body>
</html>