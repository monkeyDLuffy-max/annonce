<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Rediriger si non connecté
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];
$active_conversation = isset($_GET['with']) ? intval($_GET['with']) : null;

// Récupérer la liste des conversations
$stmt = $conn->prepare("
    SELECT DISTINCT 
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id
            ELSE m.sender_id 
        END as other_user_id,
        u.username as other_username,
        a.id as annonce_id,
        a.title as annonce_title,
        MAX(m.created_at) as last_message_date,
        COUNT(CASE WHEN m.is_read = 0 AND m.receiver_id = ? THEN 1 END) as unread_count
    FROM messages m
    JOIN users u ON (
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id = u.id
            ELSE m.sender_id = u.id 
        END
    )
    JOIN annonces a ON m.annonce_id = a.id
    WHERE m.sender_id = ? OR m.receiver_id = ?
    GROUP BY 
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id
            ELSE m.sender_id 
        END,
        u.username,
        a.id,
        a.title
    ORDER BY last_message_date DESC
");
$stmt->bind_param("iiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Si une conversation est sélectionnée, récupérer les messages
$messages = [];
if ($active_conversation) {
    // Marquer les messages comme lus
    $stmt = $conn->prepare("
        UPDATE messages 
        SET is_read = 1 
        WHERE sender_id = ? AND receiver_id = ? AND is_read = 0
    ");
    $stmt->bind_param("ii", $active_conversation, $user_id);
    $stmt->execute();

    // Récupérer les messages
    $stmt = $conn->prepare("
        SELECT m.*, u.username, a.title as annonce_title
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        JOIN annonces a ON m.annonce_id = a.id
        WHERE (m.sender_id = ? AND m.receiver_id = ?) 
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
    ");
    $stmt->bind_param("iiii", $user_id, $active_conversation, $active_conversation, $user_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Gérer l'envoi d'un nouveau message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message']) && $active_conversation) {
    $message = cleanInput($_POST['message']);
    $annonce_id = intval($_POST['annonce_id']);
    
    if (!empty($message)) {
        $stmt = $conn->prepare("
            INSERT INTO messages (sender_id, receiver_id, annonce_id, message) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiis", $user_id, $active_conversation, $annonce_id, $message);
        $stmt->execute();
        
        // Rediriger pour éviter la soumission multiple du formulaire
        header("Location: messages.php?with=" . $active_conversation);
        exit();
    }
}

include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Liste des conversations -->
        <div class="col-md-4 col-lg-3">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0">Conversations</h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php if (empty($conversations)): ?>
                        <div class="list-group-item text-muted">
                            Aucune conversation
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): ?>
                            <a href="?with=<?php echo $conv['other_user_id']; ?>" 
                               class="list-group-item list-group-item-action <?php echo ($active_conversation == $conv['other_user_id']) ? 'active' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($conv['other_username']); ?></h6>
                                    <?php if ($conv['unread_count'] > 0): ?>
                                        <span class="badge bg-primary rounded-pill"><?php echo $conv['unread_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <small class="d-block text-truncate">
                                    À propos de : <?php echo htmlspecialchars($conv['annonce_title']); ?>
                                </small>
                                <small class="text-muted">
                                    <?php echo date('d/m/Y H:i', strtotime($conv['last_message_date'])); ?>
                                </small>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Messages de la conversation active -->
        <div class="col-md-8 col-lg-9">
            <?php if ($active_conversation): ?>
                <div class="card shadow-sm">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            Conversation avec <?php 
                                foreach ($conversations as $conv) {
                                    if ($conv['other_user_id'] == $active_conversation) {
                                        echo htmlspecialchars($conv['other_username']);
                                        break;
                                    }
                                }
                            ?>
                        </h5>
                    </div>
                    <div class="card-body" style="height: 400px; overflow-y: auto;">
                        <?php foreach ($messages as $message): ?>
                            <div class="mb-3 <?php echo ($message['sender_id'] == $user_id) ? 'text-end' : ''; ?>">
                                <div class="d-inline-block p-2 rounded <?php echo ($message['sender_id'] == $user_id) ? 'bg-primary text-white' : 'bg-light'; ?>" style="max-width: 75%;">
                                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                    <div class="mt-1">
                                        <small class="<?php echo ($message['sender_id'] == $user_id) ? 'text-white-50' : 'text-muted'; ?>">
                                            <?php echo date('d/m/Y H:i', strtotime($message['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-footer">
                        <form method="POST" action="" class="d-flex">
                            <input type="hidden" name="annonce_id" value="<?php echo $messages[0]['annonce_id']; ?>">
                            <textarea class="form-control me-2" name="message" rows="1" placeholder="Votre message..." required></textarea>
                            <button type="submit" class="btn btn-primary">Envoyer</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="card shadow-sm">
                    <div class="card-body text-center py-5">
                        <h5 class="text-muted">Sélectionnez une conversation pour afficher les messages</h5>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Faire défiler automatiquement vers le bas des messages
function scrollToBottom() {
    const messageContainer = document.querySelector('.card-body');
    if (messageContainer) {
        messageContainer.scrollTop = messageContainer.scrollHeight;
    }
}

// Appeler la fonction au chargement de la page
document.addEventListener('DOMContentLoaded', scrollToBottom);
</script>

<?php include 'includes/footer.php'; ?>
