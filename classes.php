class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function registerUser($username, $email, $password): bool {
        if (empty($username) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 9) {
            return false;
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $this->pdo->prepare("INSERT INTO Users (username, email, password) VALUES (?, ?, ?)");
            return $stmt->execute([$username, $email, $hashedPassword]);
        } catch (PDOException $e) {
            return false;
        }
    }

    public function authenticateUser($username, $password): bool {
        $stmt = $this->pdo->prepare("SELECT password FROM Users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user && password_verify($password, $user['password']);
    }
}

class Topic {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function createTopic($userId, $title, $description): bool {
        $stmt = $this->pdo->prepare("INSERT INTO Topics (user_id, title, description) VALUES (?, ?, ?)");
        return $stmt->execute([$userId, $title, $description]);
    }

    public function getTopics(): array {
        $stmt = $this->pdo->query("SELECT id, title, description FROM Topics");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCreatedTopics($userId): array {
        $stmt = $this->pdo->prepare("SELECT id, title, description FROM Topics WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

class Vote {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function vote($userId, $topicId, $voteType): bool {
        if ($this->hasVoted($topicId, $userId)) {
            return false;
        }

        $stmt = $this->pdo->prepare("INSERT INTO Votes (user_id, topic_id, vote_type) VALUES (?, ?, ?)");
        return $stmt->execute([$userId, $topicId, $voteType]);
    }

    public function hasVoted($topicId, $userId): bool {
        $stmt = $this->pdo->prepare("SELECT 1 FROM Votes WHERE topic_id = ? AND user_id = ?");
        $stmt->execute([$topicId, $userId]);
        return $stmt->fetchColumn() !== false;
    }

    public function getUserVoteHistory($userId): array {
        $stmt = $this->pdo->prepare("SELECT topic_id, vote_type, voted_at FROM Votes WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

class Comment {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function addComment($userId, $topicId, $comment): bool {
        $stmt = $this->pdo->prepare("INSERT INTO Comments (user_id, topic_id, comment) VALUES (?, ?, ?)");
        return $stmt->execute([$userId, $topicId, $comment]);
    }

    public function getComments($topicId): array {
        $stmt = $this->pdo->prepare("SELECT user_id, comment, commented_at FROM Comments WHERE topic_id = ?");
        $stmt->execute([$topicId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

class TimeFormatter {
    public static function formatTimestamp(int $timestamp): string {
        $difference = time() - $timestamp;

        if ($difference < 60) {
            return "$difference seconds ago";
        } elseif ($difference < 3600) {
            return floor($difference / 60) . " minutes ago";
        } elseif ($difference < 86400) {
            return floor($difference / 3600) . " hours ago";
        } elseif ($difference < 2592000) {
            return floor($difference / 86400) . " days ago";
        } elseif ($difference < 31536000) {
            return floor($difference / 2592000) . " months ago";
        } else {
            return date("M d, Y", $timestamp);
        }
    }
}

