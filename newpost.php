<?php
// Configuration
$password_hash = "5f4dcc3b5aa765d61d8327deb882cf99"; // Default: "password"
$posts_dir = "./posts/";

// Initialize variables
$message = "";
$authorized = false;

// Create posts directory if it doesn't exist
if (!is_dir($posts_dir)) {
    mkdir($posts_dir, 0755, true);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Password check
    if (isset($_POST['password'])) {
        if (md5($_POST['password']) === $password_hash) {
            $authorized = true;
        } else {
            $message = "Invalid password";
        }
    }
    
    // Handle post submission
    if ($authorized && isset($_POST['content'])) {
        $today = date("Ymd");
        $filename = $posts_dir . $today . ".md";
        $content = trim($_POST['content']);
        
        if (!empty($content)) {
            // Check if file exists and handle accordingly
            if (file_exists($filename)) {
                // Append to existing file with a separator
                $content = "\n\n---\n\n" . $content;
                if (file_put_contents($filename, $content, FILE_APPEND)) {
                    $message = "Content appended to today's post successfully";
                } else {
                    $message = "Error appending to file";
                }
            } else {
                // Create new file
                if (file_put_contents($filename, $content)) {
                    $message = "New post created successfully";
                } else {
                    $message = "Error creating file";
                }
            }
        } else {
            $message = "Content cannot be empty";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Blog Post</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: #f5f5f5;
        }
        
        .container {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .message {
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
            background: #f0f0f0;
            color: #333;
        }
        
        .error {
            background: #fee;
            color: #c00;
        }
        
        .success {
            background: #efe;
            color: #0c0;
        }
        
        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        label {
            font-weight: bold;
        }
        
        input[type="password"] {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        textarea {
            min-height: 300px;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.9rem;
        }
        
        button {
            padding: 0.5rem 1rem;
            background: #333;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }
        
        button:hover {
            background: #444;
        }
        
        .help {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>post weblog</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'Error') !== false ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$authorized): ?>
            <form method="POST">
                <div>
                    <label for="password">auth:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div>
                    <button type="submit">login</button>
                </div>
            </form>
        <?php else: ?>
            <form method="POST">
                <input type="hidden" name="password" value="<?php echo htmlspecialchars($_POST['password']); ?>">
                <div>
                    <textarea id="content" name="content" required></textarea><br />
                </div>
                <div>
                    <button type="submit">post</button>
                </div>
            </form>
            
            <div class="help">
                <h4>markdown help</h4>
                <ul>
                    <li><code># Header 1</code> through <code>###### Header 6</code> for headers</li>
                    <li><code>**bold**</code> for <strong>bold text</strong></li>
                    <li><code>*italic*</code> for <em>italic text</em></li>
                    <li><code>__underline__</code> for <u>underlined text</u></li>
                    <li><code>* item</code> or <code>- item</code> for bullet lists</li>
                    <li><code>1. item</code> for numbered lists</li>
                    <li><code>```</code> for code blocks</li>
                    <li><code>`code`</code> for inline code</li>
                    <li><code>> text</code> for blockquotes</li>
                </ul>
            </div>

            <hr>

            Go back to <a href="https://timnetworks.net/blog">the main page</a>.
        <?php endif; ?>
    </div>
</body>
</html>
