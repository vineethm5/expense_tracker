<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get the first and last day of the current month
$first_day_month = date('Y-m-01 00:00:00');
$last_day_month = date('Y-m-t 23:59:59');

// Fetch user details
$user_query = $conn->query("SELECT username FROM users WHERE id = $user_id");
$user_data = $user_query->fetch_assoc();
$username = $user_data['username'] ?? 'User';

// Handle Form Submission
if (isset($_POST['add_expense'])) {
    $amount = $_POST['amount'];
    $category = $_POST['category'];
    $date = $_POST['date'];
    $desc = $conn->real_escape_string($_POST['description']);

    $stmt = $conn->prepare("INSERT INTO expenses (user_id, amount, category, expense_date, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("idsss", $user_id, $amount, $category, $date, $desc);
    
    if($stmt->execute()) {
        header("Location: dashboard.php");
        exit();
    }
}

// 1. Fetch Expenses for Table (Filtered by Current Month)
$result = $conn->query("SELECT * FROM expenses 
                        WHERE user_id = $user_id 
                        AND expense_date BETWEEN '$first_day_month' AND '$last_day_month' 
                        ORDER BY expense_date DESC LIMIT 3");


$result1 = $conn->query("SELECT * FROM expenses 
                        WHERE user_id = $user_id 
                        AND expense_date BETWEEN '$first_day_month' AND '$last_day_month' 
                        ORDER BY expense_date ");

// 2. Fetch Data for Chart (Filtered by Current Month)
$chartData = $conn->query("SELECT category, SUM(amount) as total FROM expenses 
                           WHERE user_id = $user_id 
                           AND expense_date BETWEEN '$first_day_month' AND '$last_day_month' 
                           GROUP BY category");

$categories = [];
$totals = [];
while($row = $chartData->fetch_assoc()) {
    $categories[] = $row['category'];
    $totals[] = (float)$row['total'];
}

// 3. Monthly total for the Indigo Card
$totalSpentThisMonth = !empty($totals) ? array_sum($totals) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TrackIt | Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#f8fafc] font-sans antialiased text-slate-900">

    <div class="flex h-screen overflow-hidden">
        <div class="flex-1 flex flex-col overflow-hidden">
            
            <main class="flex-1 overflow-y-auto p-8">
                
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-10 gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900">Hello, <?php echo htmlspecialchars($username); ?>!</h1>
                        <p class="text-slate-500 mt-1">Summary for <?php echo date('F Y'); ?>.</p>
                    </div>
                    <button onclick="document.getElementById('modal').classList.remove('hidden')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-xl font-bold shadow-lg transition-all flex items-center gap-2">
                        <i class="fa-solid fa-plus text-sm"></i> Add New Expense
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-10">
                    <div class="lg:col-span-2 bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="font-bold text-slate-800">Spending Analysis</h3>
                            <span class="text-xs font-bold text-slate-400 bg-slate-50 px-3 py-1 rounded-full uppercase">Current Month</span>
                        </div>
                        <div class="relative h-[300px] w-full">
                            <canvas id="expenseChart"></canvas>
                        </div>
                    </div>

                    <div class="flex flex-col gap-6">
                        <div class="bg-indigo-600 p-8 rounded-3xl shadow-xl text-white">
                            <h3 class="text-indigo-100 text-sm font-semibold uppercase tracking-wider">Total Expenses</h3>
                            <p class="text-4xl font-extrabold mt-4">₹<?php echo number_format($totalSpentThisMonth, 2); ?></p>
                        </div>
                        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
                            <h3 class="text-slate-500 text-sm font-semibold uppercase tracking-wider">Month Transactions</h3>
                            <p class="text-3xl font-extrabold text-slate-800 mt-2"><?php echo $result1->num_rows; ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                    <div class="p-6 border-b border-slate-50">
                        <h3 class="font-bold text-slate-800 text-lg">Recent History</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-8 py-4 text-xs font-bold text-slate-500 uppercase">Date</th>
                                    <th class="px-8 py-4 text-xs font-bold text-slate-500 uppercase">Category</th>
                                    <th class="px-8 py-4 text-xs font-bold text-slate-500 uppercase">Description</th>
                                    <th class="px-8 py-4 text-xs font-bold text-slate-500 uppercase text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php if ($result->num_rows > 0): ?>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr class="hover:bg-indigo-50/30 transition">
                                        <td class="px-8 py-5 text-sm text-slate-600"><?php echo date('M d, Y', strtotime($row['expense_date'])); ?></td>
                                        <td class="px-8 py-5">
                                            <span class="px-3 py-1 text-[10px] font-black rounded-full bg-indigo-100 text-indigo-700 uppercase">
                                                <?php echo $row['category']; ?>
                                            </span>
                                        </td>
                                        <td class="px-8 py-5 text-sm text-slate-500"><?php echo htmlspecialchars($row['description']); ?></td>
                                        <td class="px-8 py-5 text-sm font-bold text-slate-900 text-right">₹<?php echo number_format($row['amount'], 2); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="px-8 py-10 text-center text-slate-400">
                                            No transactions recorded for <?php echo date('F'); ?>.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div id="modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl p-10 w-full max-w-md animate-in fade-in zoom-in duration-200">
            <h3 class="text-2xl font-bold text-slate-800 mb-6 text-center">New Expense</h3>
            <form method="POST">
                <div class="space-y-5">
                    <input type="number" step="0.01" name="amount" placeholder="Amount (₹)" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl outline-none focus:ring-2 focus:ring-indigo-500 font-bold" required>
                    <select name="category" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl outline-none focus:ring-2 focus:ring-indigo-500">
                        <option>Food</option>
                        <option>Travel</option>
                        <option>Bills</option>
                        <option>Shopping</option>
                        <option>Entertainment</option>
                    </select>
                    <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl outline-none focus:ring-2 focus:ring-indigo-500" required>
                    <textarea name="description" placeholder="Description" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl outline-none focus:ring-2 focus:ring-indigo-500 h-24"></textarea>
                </div>
                <div class="flex gap-4 mt-8">
                    <button type="button" onclick="document.getElementById('modal').classList.add('hidden')" class="flex-1 py-4 text-slate-500 font-bold">Cancel</button>
                    <button type="submit" name="add_expense" class="flex-1 py-4 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let myChart;
        const ctx = document.getElementById('expenseChart').getContext('2d');
        const categories = <?php echo json_encode($categories); ?>;
        const totals = <?php echo json_encode($totals); ?>;

        function renderChart() {
            if (myChart) { myChart.destroy(); }

            // Handle empty state visual
            const hasData = categories.length > 0;
            const chartLabels = hasData ? categories : ['No Data'];
            const chartData = hasData ? totals : [0];
            const barColor = hasData ? '#6366f1' : '#f1f5f9';

            myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Total Spent',
                        data: chartData,
                        backgroundColor: barColor,
                        borderRadius: 12,
                        barThickness: 30
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { 
                        legend: { display: false },
                        tooltip: { enabled: hasData }
                    },
                    scales: {
                        y: { 
                            beginAtZero: true, 
                            max: hasData ? null : 1000,
                            grid: { color: '#f1f5f9' }, 
                            border: { display: false } 
                        },
                        x: { grid: { display: false }, border: { display: false } }
                    }
                }
            });
        }

        renderChart();
    </script>
</body>
</html>