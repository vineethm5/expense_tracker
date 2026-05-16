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

/** * FORM HANDLERS 
 */

// 1. Handle Budget Submission
if (isset($_POST['set_budget'])) {
    $budget_amount = $_POST['budget_amount'];
    $category = $_POST['category'];
    $budget_month = $_POST['budget_month']."-01";

    $stmt1 = $conn->prepare("INSERT INTO budgets (user_id, category, amount_limit, month_year) VALUES (?, ?, ?, ?)");
    $stmt1->bind_param("isds", $user_id, $category, $budget_amount, $budget_month);
    
    if($stmt1->execute()) {
        header("Location: dashboard.php");
        exit();
    }
}

// 2. Handle Expense Submission
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

// 3. Handle Income Submission
if (isset($_POST['add_income'])) {
    $income_amount = $_POST['income_amount'];
    $source = $_POST['source'];
    $income_date = $_POST['income_date']."-01";
    $description = "incomes";

    $stmt2 = $conn->prepare("INSERT INTO income (user_id, amount, source, description, income_date) VALUES (?, ?, ?, ?, ?)");
    $stmt2->bind_param("idsss", $user_id, $income_amount, $source, $description, $income_date);
    
    if($stmt2->execute()) {
        header("Location: dashboard.php");
        exit();
    }
}

/** * DATA FETCHING 
 */

// Monthly Totals
$inc_sum = $conn->query("SELECT SUM(amount) as total FROM income WHERE user_id = $user_id AND income_date BETWEEN '$first_day_month' AND '$last_day_month'");
$totalIncome = $inc_sum->fetch_assoc()['total'] ?? 0;

$exp_sum = $conn->query("SELECT SUM(amount) as total FROM expenses WHERE user_id = $user_id AND expense_date BETWEEN '$first_day_month' AND '$last_day_month'");
$totalSpentThisMonth = $exp_sum->fetch_assoc()['total'] ?? 0;
$remainingBalance = $totalIncome - $totalSpentThisMonth;

// Chart Data
$chartData = $conn->query("SELECT category, SUM(amount) as total FROM expenses WHERE user_id = $user_id AND expense_date BETWEEN '$first_day_month' AND '$last_day_month' GROUP BY category");
$categories = []; $totals = [];
while($row = $chartData->fetch_assoc()) {
    $categories[] = $row['category'];
    $totals[] = (float)$row['total'];
}

// Recent History (Activity Flow Section Data)
$result = $conn->query("
    (SELECT expense_date as date, category, description, amount, 'expense' as type 
     FROM expenses WHERE user_id = $user_id)
    UNION ALL
    (SELECT income_date as date, source as category, description, amount, 'income' as type 
     FROM income WHERE user_id = $user_id)
    ORDER BY date DESC LIMIT 10
");

// Budget Progress & Existing Limits Configurations
$budget_query = $conn->query("
    SELECT b.category, b.amount_limit, IFNULL(SUM(e.amount), 0) as spent
    FROM budgets b
    LEFT JOIN expenses e ON b.category = e.category 
        AND e.user_id = b.user_id 
        AND e.expense_date BETWEEN '$first_day_month' AND '$last_day_month'
    WHERE b.user_id = $user_id AND b.month_year = '" . date('Y-m-01') . "'
    GROUP BY b.category ORDER BY b.amount_limit DESC
");

// Build JS Budget Limit Maps for Live Validations
$js_budgets = [];
$total_allocated_budget = 0;
if ($budget_query->num_rows > 0) {
    while($b = $budget_query->fetch_assoc()) {
        $total_allocated_budget += (float)$b['amount_limit'];
        $js_budgets[$b['category']] = [
            'limit' => (float)$b['amount_limit'],
            'remaining' => (float)($b['amount_limit'] - $b['spent'])
        ];
    }
}
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
    <style>
        .modal-overlay { background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
    </style>
</head>
<body class="bg-[#f8fafc] font-sans antialiased text-slate-900">

    <div class="flex h-screen overflow-hidden">
        <div class="flex-1 flex flex-col overflow-hidden">
            <main class="flex-1 overflow-y-auto p-8">

                <div class="flex flex-col lg:flex-row lg:items-center justify-between mb-10 gap-6">
                    <div>
                        <h1 class="text-3xl font-bold text-slate-900">Hello, <?php echo htmlspecialchars($username); ?>!</h1>
                        <p class="text-slate-500 mt-1">Summary for <?php echo date('F Y'); ?>.</p>
                    </div>
                    
                    <div class="flex flex-wrap items-center gap-3">
                        <button onclick="openModal('budget-modal')" class="bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 px-5 py-2.5 rounded-xl font-bold shadow-sm flex items-center gap-2 transition-all">
                            <i class="fa-solid fa-wallet text-indigo-600"></i> Set Budget
                        </button>
                        <button onclick="openModal('income-modal')" class="bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-xl font-bold shadow-md flex items-center gap-2 transition-all">
                            <i class="fa-solid fa-arrow-trend-up"></i> Add Income
                        </button>
                        <button onclick="openModal('modal')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg flex items-center gap-2 transition-all">
                            <i class="fa-solid fa-plus"></i> Add Expense
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    
                    <div class="lg:col-span-2 space-y-8">
                        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
                            <h3 class="font-bold text-slate-800 mb-6">Spending Analysis</h3>
                            <div class="relative h-[300px] w-full">
                                <canvas id="expenseChart"></canvas>
                            </div>
                        </div>

                        <div class="bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden w-full">
                            <div class="p-8 border-b border-slate-50 flex justify-between items-center bg-white">
                                <h3 class="font-bold text-slate-800 text-lg">Recent History</h3>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest bg-slate-50 px-3 py-1 rounded-full">Combined Flow</span>
                            </div>
                            
                            <div class="w-full overflow-x-auto">
                                <table class="w-full table-fixed border-collapse">
                                    <thead class="bg-slate-50/50">
                                        <tr>
                                            <th class="w-[20%] px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-left">Date</th>
                                            <th class="w-[55%] px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-left">Category / Source</th>
                                            <th class="w-[25%] px-8 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-50">
                                        <?php if ($result->num_rows > 0): ?>
                                            <?php $result->data_seek(0); while($row = $result->fetch_assoc()): $isIncome = ($row['type'] == 'income'); ?>
                                            <tr class="hover:bg-slate-50/80 transition-colors group">
                                                <td class="px-8 py-5">
                                                    <div class="flex flex-col">
                                                        <span class="text-sm font-bold text-slate-700"><?php echo date('M d', strtotime($row['date'])); ?></span>
                                                        <span class="text-[10px] text-slate-400 uppercase"><?php echo date('Y', strtotime($row['date'])); ?></span>
                                                    </div>
                                                </td>
                                                <td class="px-8 py-5">
                                                    <div class="flex items-center gap-4">
                                                        <div class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 <?php echo $isIncome ? 'bg-emerald-100 text-emerald-600' : 'bg-indigo-100 text-indigo-600'; ?>">
                                                            <i class="fa-solid <?php echo $isIncome ? 'fa-arrow-down' : 'fa-arrow-up'; ?> text-xs"></i>
                                                        </div>
                                                        <div class="overflow-hidden">
                                                            <span class="block text-sm font-bold text-slate-800 capitalize truncate"><?php echo htmlspecialchars($row['category']); ?></span>
                                                            <span class="block text-[11px] text-slate-400 truncate"><?php echo htmlspecialchars($row['description']); ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-8 py-5 text-right">
                                                    <span class="text-base font-black <?php echo $isIncome ? 'text-emerald-600' : 'text-slate-900'; ?> whitespace-nowrap">
                                                        <?php echo $isIncome ? '+' : '-'; ?> ₹<?php echo number_format($row['amount'], 2); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="3" class="px-8 py-16 text-center text-slate-400 italic">No activity yet.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
                            <h3 class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-1">Available Balance</h3>
                            <p class="text-4xl font-black <?php echo $remainingBalance >= 0 ? 'text-emerald-600' : 'text-red-600'; ?>">
                                ₹<?php echo number_format($remainingBalance, 2); ?>
                            </p>
                            <div class="mt-4 flex items-center gap-2 text-xs font-bold text-slate-400 uppercase">
                                <span>In: <span class="text-emerald-500">₹<?php echo number_format($totalIncome); ?></span></span>
                                <span class="text-slate-200">|</span>
                                <span>Out: <span class="text-indigo-500">₹<?php echo number_format($totalSpentThisMonth); ?></span></span>
                            </div>
                        </div>

                        <div class="bg-indigo-600 p-8 rounded-3xl shadow-xl text-white">
                            <h3 class="text-indigo-100 text-sm font-semibold uppercase tracking-wider">Total Expenses</h3>
                            <p class="text-4xl font-extrabold mt-4">₹<?php echo number_format($totalSpentThisMonth, 2); ?></p>
                        </div>

                        <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                            <h3 class="text-slate-500 text-xs font-bold uppercase tracking-widest mb-6">Top Budgets</h3>
                            <div class="space-y-6">
                                <?php if ($budget_query->num_rows > 0): $budget_query->data_seek(0); while($b = $budget_query->fetch_assoc()): 
                                    $percent = ($b['amount_limit'] > 0) ? ($b['spent'] / $b['amount_limit']) * 100 : 0;
                                    $is_over = $b['spent'] > $b['amount_limit'];
                                ?>
                                <div>
                                    <div class="flex justify-between items-end mb-2">
                                        <div>
                                            <span class="block text-sm font-bold text-slate-800"><?php echo htmlspecialchars($b['category']); ?></span>
                                            <span class="text-[10px] text-slate-400 font-bold uppercase">₹<?php echo number_format($b['spent']); ?> of ₹<?php echo number_format($b['amount_limit']); ?></span>
                                        </div>
                                        <span class="text-xs font-black <?php echo $is_over ? 'text-red-500' : 'text-indigo-600'; ?>"><?php echo round($percent); ?>%</span>
                                    </div>
                                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full <?php echo $is_over ? 'bg-red-500' : 'bg-indigo-500'; ?> transition-all duration-500" style="width: <?php echo min($percent, 100); ?>%"></div>
                                    </div>
                                </div>
                                <?php endwhile; else: ?>
                                <p class="text-slate-400 text-sm italic text-center">No budgets set.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100">
                            <h3 class="text-slate-500 text-sm font-semibold uppercase tracking-wider">Activity Items</h3>
                            <p class="text-3xl font-extrabold text-slate-800 mt-2"><?php echo $result->num_rows; ?></p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div id="modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 modal-overlay">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-200">
            <div class="p-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">Add Expense</h2>
                    <button onclick="closeModal('modal')"><i class="fa-solid fa-xmark text-slate-400"></i></button>
                </div>
                
                <div id="modal-alert" class="hidden mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-xs font-bold rounded-xl flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span id="modal-alert-text"></span>
                </div>

                <form method="POST" class="space-y-4">
                    <select id="expense-category" name="category" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-indigo-500">
                        <option value="Food">Food</option>
                        <option value="Transport">Transport</option>
                        <option value="Shopping">Shopping</option>
                        <option value="Bills">Bills</option>
                        <option value="Entertainment">Entertainment</option>
                    </select>
                    <input id="expense-amount" type="number" name="amount" step="0.01" required class="w-full px-4 py-3 rounded-xl border border-slate-200 focus:outline-indigo-500" placeholder="Amount (₹)">
                    <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required class="w-full px-4 py-3 rounded-xl border border-slate-200">
                    <input type="text" name="description" class="w-full px-4 py-3 rounded-xl border border-slate-200" placeholder="Description">
                    <button type="submit" name="add_expense" class="w-full bg-indigo-600 text-white font-bold py-4 rounded-2xl">Save Expense</button>
                </form>
            </div>
        </div>
    </div>

    <div id="budget-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 modal-overlay">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden">
            <div class="p-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">Set Budget</h2>
                    <button onclick="closeModal('budget-modal')"><i class="fa-solid fa-xmark text-slate-400"></i></button>
                </div>

                <div id="budget-modal-alert" class="hidden mb-4 p-3 bg-amber-50 border border-amber-200 text-amber-800 text-xs font-bold rounded-xl flex items-center gap-2">
                    <i class="fa-solid fa-scale-unbalanced"></i>
                    <span id="budget-modal-alert-text"></span>
                </div>

                <form method="POST" class="space-y-4">
                    <select id="budget-category" name="category" required class="w-full px-4 py-3 rounded-xl border border-slate-200">
                        <option value="Food">Food</option>
                        <option value="Transport">Transport</option>
                        <option value="Shopping">Shopping</option>
                        <option value="Bills">Bills</option>
                        <option value="Entertainment">Entertainment</option>
                    </select>
                    <input id="budget-amount" type="number" name="budget_amount" required class="w-full px-4 py-3 rounded-xl border border-slate-200" placeholder="Limit (₹)">
                    <input type="month" name="budget_month" value="<?php echo date('Y-m'); ?>" required class="w-full px-4 py-3 rounded-xl border border-slate-200">
                    <button type="submit" name="set_budget" class="w-full bg-slate-900 text-white font-bold py-4 rounded-2xl">Set Limit</button>
                </form>
            </div>
        </div>
    </div>

    <div id="income-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 modal-overlay">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden">
            <div class="p-8">
                <div class="flex justify-between items-center mb-6"><h2 class="text-2xl font-bold">Add Income</h2><button onclick="closeModal('income-modal')"><i class="fa-solid fa-xmark text-slate-400"></i></button></div>
                <form method="POST" class="space-y-4">
                    <input type="number" name="income_amount" required class="w-full px-4 py-3 rounded-xl border border-slate-200" placeholder="Amount (₹)">
                    <input type="text" name="source" required class="w-full px-4 py-3 rounded-xl border border-slate-200" placeholder="Source (e.g. Salary)">
                    <input type="month" name="income_date" value="<?php echo date('Y-m'); ?>" required class="w-full px-4 py-3 rounded-xl border border-slate-200">
                    <button type="submit" name="add_income" class="w-full bg-emerald-500 text-white font-bold py-4 rounded-2xl">Save Income</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Global system references injected from backend calculations
        const monthlyBudgets = <?php echo json_encode($js_budgets); ?>;
        const totalIncome = <?php echo (float)$totalIncome; ?>;
        const totalAllocatedBudget = <?php echo (float)$total_allocated_budget; ?>;

        // 1. LIVE CHECK FOR EXPENSES MODAL
        const amountInput = document.getElementById('expense-amount');
        const categoryInput = document.getElementById('expense-category');
        const modalAlert = document.getElementById('modal-alert');
        const modalAlertText = document.getElementById('modal-alert-text');

        function checkLiveBudget() {
            const chosenCategory = categoryInput.value;
            const typedAmount = parseFloat(amountInput.value) || 0;

            if (monthlyBudgets[chosenCategory]) {
                const limitDetails = monthlyBudgets[chosenCategory];
                if (typedAmount > limitDetails.remaining) {
                    modalAlertText.innerText = `Warning: This exceeds your remaining budget for ${chosenCategory} (Available: ₹${limitDetails.remaining.toFixed(2)})!`;
                    modalAlert.classList.remove('hidden');
                } else {
                    modalAlert.classList.add('hidden');
                }
            } else {
                modalAlertText.innerText = `Notice: No budget configured for ${chosenCategory} this month.`;
                modalAlert.classList.remove('hidden');
            }
        }
        amountInput.addEventListener('input', checkLiveBudget);
        categoryInput.addEventListener('change', checkLiveBudget);


        // 2. LIVE CHECK FOR SET BUDGET MODAL
        const budgetAmountInput = document.getElementById('budget-amount');
        const budgetCategoryInput = document.getElementById('budget-category');
        const budgetAlert = document.getElementById('budget-modal-alert');
        const budgetAlertText = document.getElementById('budget-modal-alert-text');

        function checkLiveIncomeLimit() {
            const typedBudget = parseFloat(budgetAmountInput.value) || 0;
            const categorySelected = budgetCategoryInput.value;

            // Find out if we are updating an old category allocation layout
            const oldCategoryLimit = monthlyBudgets[categorySelected] ? monthlyBudgets[categorySelected].limit : 0;
            
            // Expected future layout calculation simulation
            const projectedTotalBudgets = (totalAllocatedBudget - oldCategoryLimit) + typedBudget;

            if (projectedTotalBudgets > totalIncome) {
                budgetAlertText.innerText = `Warning: Total budgets (₹${projectedTotalBudgets.toFixed(2)}) will exceed your monthly income (₹${totalIncome.toFixed(2)})!`;
                budgetAlert.classList.remove('hidden');
            } else {
                budgetAlert.classList.add('hidden');
            }
        }
        budgetAmountInput.addEventListener('input', checkLiveIncomeLimit);
        budgetCategoryInput.addEventListener('change', checkLiveIncomeLimit);


        // Modal Layout Controls
        function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
        function closeModal(id) { 
            document.getElementById(id).classList.add('hidden'); 
            if(id === 'modal') { modalAlert.classList.add('hidden'); amountInput.value = ''; }
            if(id === 'budget-modal') { budgetAlert.classList.add('hidden'); budgetAmountInput.value = ''; }
        }

        // Chart.js Configuration Layout
        const ctx = document.getElementById('expenseChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($categories); ?>,
                datasets: [{
                    data: <?php echo json_encode($totals); ?>,
                    backgroundColor: '#6366f1',
                    borderRadius: 10,
                    barThickness: 30
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, border: { display: false } },
                    x: { grid: { display: false }, border: { display: false } }
                }
            }
        });

        // Close on background layout click
        window.onclick = function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                closeModal(e.target.id);
            }
        }
    </script>
</body>
</html>
