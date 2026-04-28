<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: auth.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM expenses WHERE id = $id AND user_id = $user_id");
    header("Location: transactions.php");
    exit();
}

// Handle Edit (Update) Submission
if (isset($_POST['edit_expense'])) {
    $id = intval($_POST['expense_id']);
    $amount = $_POST['amount'];
    $category = $_POST['category'];
    $date = $_POST['date'];
    $desc = $conn->real_escape_string($_POST['description']);

    $stmt = $conn->prepare("UPDATE expenses SET amount=?, category=?, expense_date=?, description=? WHERE id=? AND user_id=?");
    $stmt->bind_param("dsssii", $amount, $category, $date, $desc, $id, $user_id);
    
    if($stmt->execute()) {
        header("Location: transactions.php?success=1");
        exit();
    }
}

$result = $conn->query("SELECT * FROM expenses WHERE user_id = $user_id ORDER BY expense_date DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Transactions | TrackIt</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-[#f8fafc] font-sans antialiased">
    <div class="flex h-screen overflow-hidden">
        
       

        <main class="flex-1 p-8 overflow-y-auto">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold text-slate-800">History</h2>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-8 py-4 text-xs font-bold text-slate-500 uppercase">Date</th>
                            <th class="px-8 py-4 text-xs font-bold text-slate-500 uppercase">Category</th>
                            <th class="px-8 py-4 text-xs font-bold text-slate-500 uppercase">Description</th>
                            <th class="px-8 py-4 text-xs font-bold text-slate-500 uppercase">Amount</th>
                            <th class="px-8 py-4 text-xs font-bold text-slate-500 uppercase text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-8 py-5 text-sm text-slate-600"><?php echo $row['expense_date']; ?></td>
                            <td class="px-8 py-5">
                                <span class="px-3 py-1 text-[10px] font-black rounded-full bg-indigo-100 text-indigo-700 uppercase">
                                    <?php echo $row['category']; ?>
                                </span>
                            </td>
                            <td class="px-8 py-5 text-sm text-slate-500"><?php echo htmlspecialchars($row['description']); ?></td>
                            <td class="px-8 py-5 text-sm font-bold">₹<?php echo number_format($row['amount'], 2); ?></td>
                            <td class="px-8 py-5 text-right space-x-3">
                                <button onclick='openEditModal(<?php echo json_encode($row); ?>)' class="text-indigo-600 hover:bg-indigo-50 p-2 rounded-lg transition">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                
                                <button onclick="openDeleteModal(<?php echo $row['id']; ?>)" class="text-red-400 hover:bg-red-50 p-2 rounded-lg transition">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <div id="editModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl p-10 w-full max-w-md">
            <h3 class="text-2xl font-bold text-slate-800 mb-6 text-center">Edit Transaction</h3>
            <form method="POST">
                <input type="hidden" name="expense_id" id="edit_id">
                
                <div class="space-y-5">
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase ml-1">Amount</label>
                        <input type="number" step="0.01" name="amount" id="edit_amount" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl outline-none focus:ring-2 focus:ring-indigo-500 font-bold" required>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase ml-1">Category</label>
                        <select name="category" id="edit_category" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl outline-none focus:ring-2 focus:ring-indigo-500">
                            <option>Food</option>
                            <option>Travel</option>
                            <option>Bills</option>
                            <option>Shopping</option>
                            <option>Entertainment</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase ml-1">Date</label>
                        <input type="date" name="date" id="edit_date" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl outline-none focus:ring-2 focus:ring-indigo-500" required>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-slate-400 uppercase ml-1">Description</label>
                        <textarea name="description" id="edit_description" class="w-full p-4 bg-slate-50 border border-slate-200 rounded-2xl outline-none focus:ring-2 focus:ring-indigo-500 h-24"></textarea>
                    </div>
                </div>
                
                <div class="flex gap-4 mt-8">
                    <button type="button" onclick="closeEditModal()" class="flex-1 py-4 text-slate-500 font-bold">Cancel</button>
                    <button type="submit" name="edit_expense" class="flex-1 py-4 bg-indigo-600 text-white rounded-2xl font-bold shadow-lg shadow-indigo-100">Update</button>
                </div>
            </form>
        </div>
    </div>

    <div id="deleteModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center z-[60] p-4">
        <div class="bg-white rounded-[2rem] shadow-2xl p-8 w-full max-w-sm text-center animate-in fade-in zoom-in duration-200">
            <div class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-trash-can text-2xl"></i>
            </div>
            <h3 class="text-xl font-bold text-slate-800 mb-2">Delete Transaction?</h3>
            <p class="text-slate-500 mb-8 text-sm">This action cannot be undone. This will permanently remove the record from your history.</p>
            
            <div class="flex gap-3">
                <button onclick="closeDeleteModal()" class="flex-1 py-3 text-slate-500 font-bold hover:bg-slate-50 rounded-xl transition">
                    Keep it
                </button>
                <a id="confirmDeleteBtn" href="#" class="flex-1 py-3 bg-red-500 text-white rounded-xl font-bold shadow-lg shadow-red-100 hover:bg-red-600 transition">
                    Delete
                </a>
            </div>
        </div>
    </div>

    <script>
        // Existing Edit Modal Functions
        function openEditModal(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_amount').value = data.amount;
            document.getElementById('edit_category').value = data.category;
            document.getElementById('edit_date').value = data.expense_date;
            document.getElementById('edit_description').value = data.description;
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        // --- NEW DELETE MODAL FUNCTIONS ---
        function openDeleteModal(id) {
            // Update the link in the modal to point to the delete URL with the correct ID
            const deleteUrl = "transactions.php?delete=" + id;
            document.getElementById('confirmDeleteBtn').setAttribute('href', deleteUrl);
            
            // Show the modal
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Close modals if clicking outside the white box
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target == editModal) closeEditModal();
            if (event.target == deleteModal) closeDeleteModal();
        }
    </script>

    <script>
        function openEditModal(data) {
            // Fill the modal fields with the row data
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_amount').value = data.amount;
            document.getElementById('edit_category').value = data.category;
            document.getElementById('edit_date').value = data.expense_date;
            document.getElementById('edit_description').value = data.description;
            
            // Show the modal
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
    </script>
</body>
</html>