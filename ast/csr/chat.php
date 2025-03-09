<?php
include '../components/connect.php';

session_start();

if(!isset($_SESSION['csr_id'])) {
   header('location:csr_login.php');
   exit();
}

$csr_id = $_SESSION['csr_id'];

// Fetch active chats
$active_chats = $conn->prepare("
    SELECT c.*, 
           CONCAT(cu.first_name, ' ', cu.last_name) as customer_name,
           cu.email as customer_email
    FROM chat_sessions c
    JOIN customers cu ON c.customer_id = cu.customer_id
    WHERE c.csr_id = ? AND c.status = 'active'
    ORDER BY c.last_message_at DESC
");
$active_chats->execute([$csr_id]);

// Fetch waiting customers
$waiting_customers = $conn->prepare("
    SELECT c.*, 
           CONCAT(cu.first_name, ' ', cu.last_name) as customer_name,
           cu.email as customer_email,
           TIMESTAMPDIFF(MINUTE, c.created_at, NOW()) as wait_time
    FROM chat_sessions c
    JOIN customers cu ON c.customer_id = cu.customer_id
    WHERE c.status = 'waiting'
    ORDER BY c.created_at ASC
");
$waiting_customers->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>AstroShop | Live Chat Support</title>

   <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>

<?php include '../components/csr_header.php'; ?>

<section class="chat-section mt-5">
    <div class="container-fluid">
        <div class="row">
            <!-- Chat List Sidebar -->
            <div class="col-md-4 col-lg-3">
                <div class="content-card h-100">
                    <!-- Active Chats -->
                    <div class="chat-list-section">
                        <h5 class="section-title">Active Chats</h5>
                        <div class="chat-list">
                            <?php if($active_chats->rowCount() > 0): ?>
                                <?php while($chat = $active_chats->fetch(PDO::FETCH_ASSOC)): ?>
                                    <div class="chat-item" onclick="loadChat(<?= $chat['session_id'] ?>)">
                                        <div class="chat-item-avatar">
                                            <?= strtoupper(substr($chat['customer_name'], 0, 1)) ?>
                                        </div>
                                        <div class="chat-item-info">
                                            <div class="chat-item-name"><?= htmlspecialchars($chat['customer_name']) ?></div>
                                            <div class="chat-item-preview"><?= htmlspecialchars($chat['customer_email']) ?></div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-comments fa-2x mb-2"></i>
                                    <p>No active chats</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Waiting Customers -->
                    <div class="chat-list-section mt-4">
                        <h5 class="section-title">Waiting Queue</h5>
                        <div class="chat-list">
                            <?php if($waiting_customers->rowCount() > 0): ?>
                                <?php while($customer = $waiting_customers->fetch(PDO::FETCH_ASSOC)): ?>
                                    <div class="chat-item waiting">
                                        <div class="chat-item-avatar bg-warning">
                                            <?= strtoupper(substr($customer['customer_name'], 0, 1)) ?>
                                        </div>
                                        <div class="chat-item-info">
                                            <div class="chat-item-name"><?= htmlspecialchars($customer['customer_name']) ?></div>
                                            <div class="chat-item-preview">
                                                Waiting for <?= $customer['wait_time'] ?> min
                                            </div>
                                        </div>
                                        <button class="btn btn-sm btn-primary" onclick="acceptChat(<?= $customer['session_id'] ?>)">
                                            Accept
                                        </button>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-user-clock fa-2x mb-2"></i>
                                    <p>No customers waiting</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat Window -->
            <div class="col-md-8 col-lg-9">
                <div class="content-card chat-window">
                    <!-- Default State -->
                    <div class="chat-default-state" id="defaultState">
                        <div class="text-center py-5">
                            <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                            <h4>Welcome to Live Chat Support</h4>
                            <p class="text-muted">Select a chat from the sidebar or accept a new customer from the queue</p>
                        </div>
                    </div>

                    <!-- Active Chat Window -->
                    <div class="chat-active-state d-none" id="chatState">
                        <!-- Chat Header -->
                        <div class="chat-header">
                            <div class="customer-info">
                                <div class="customer-avatar" id="customerAvatar"></div>
                                <div class="customer-details">
                                    <h5 id="customerName"></h5>
                                    <small id="customerEmail" class="text-muted"></small>
                                </div>
                            </div>
                            <div class="chat-actions">
                                <button class="btn btn-outline-primary btn-sm" onclick="transferChat()">
                                    <i class="fas fa-exchange-alt"></i> Transfer
                                </button>
                                <button class="btn btn-outline-danger btn-sm" onclick="endChat()">
                                    <i class="fas fa-times"></i> End Chat
                                </button>
                            </div>
                        </div>

                        <!-- Chat Messages -->
                        <div class="chat-messages" id="chatMessages"></div>

                        <!-- Chat Input -->
                        <div class="chat-input">
                            <form id="messageForm" onsubmit="sendMessage(event)">
                                <div class="input-group">
                                    <textarea class="form-control" id="messageInput" rows="1" placeholder="Type your message..."></textarea>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.content-card {
    background: white;
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
}

.section-title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #2b3452;
}

.chat-list {
    max-height: calc(100vh - 300px);
    overflow-y: auto;
}

.chat-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
    gap: 1rem;
}

.chat-item:hover {
    background-color: #f8f9fa;
}

.chat-item-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.chat-item-info {
    flex: 1;
}

.chat-item-name {
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.chat-item-preview {
    font-size: 0.875rem;
    color: #6c757d;
}

.chat-window {
    height: calc(100vh - 200px);
    display: flex;
    flex-direction: column;
}

.chat-header {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.customer-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 1rem;
}

.chat-input {
    padding: 1rem;
    border-top: 1px solid #e9ecef;
}

.message {
    margin-bottom: 1rem;
    max-width: 70%;
}

.message-sent {
    margin-left: auto;
    background: var(--primary-color);
    color: white;
    border-radius: 15px 15px 0 15px;
    padding: 0.75rem 1rem;
}

.message-received {
    background: #f8f9fa;
    border-radius: 15px 15px 15px 0;
    padding: 0.75rem 1rem;
}

@media (max-width: 768px) {
    .chat-window {
        height: calc(100vh - 300px);
    }
}
</style>

<script>
let currentChatId = null;

function loadChat(sessionId) {
    currentChatId = sessionId;
    document.getElementById('defaultState').classList.add('d-none');
    document.getElementById('chatState').classList.remove('d-none');
    
    // Load chat messages
    fetchChatMessages(sessionId);
}

function fetchChatMessages(sessionId) {
    // Implement message fetching logic
    fetch(`get_messages.php?session_id=${sessionId}`)
        .then(response => response.json())
        .then(data => {
            displayMessages(data.messages);
        });
}

function displayMessages(messages) {
    const chatMessages = document.getElementById('chatMessages');
    chatMessages.innerHTML = messages.map(msg => `
        <div class="message ${msg.sender_type === 'csr' ? 'message-sent' : 'message-received'}">
            ${msg.message}
            <small class="text-muted d-block mt-1">${msg.sent_at}</small>
        </div>
    `).join('');
    
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function sendMessage(event) {
    event.preventDefault();
    const input = document.getElementById('messageInput');
    const message = input.value.trim();
    
    if (message && currentChatId) {
        fetch('send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                session_id: currentChatId,
                message: message
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                input.value = '';
                fetchChatMessages(currentChatId);
            }
        });
    }
}

function acceptChat(sessionId) {
    fetch('accept_chat.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            session_id: sessionId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadChat(sessionId);
            // Refresh the waiting list
            location.reload();
        }
    });
}

// Poll for new messages every 5 seconds
setInterval(() => {
    if (currentChatId) {
        fetchChatMessages(currentChatId);
    }
}, 5000);
</script>

</body>
</html>